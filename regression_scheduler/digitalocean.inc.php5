<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     30/09/14 11:45
 */
define('DROPLET_NAME', 'rstudio');
define('DROPLET_REGION', 'ams3');
define('DROPLET_SIZE', '512mb');

define('DROPLET_SSH_KEY_TOM', '0a:ac:f2:e6:60:41:b5:50:98:78:dd:ef:31:5a:ed:de'); // fingerprint ( ssh-keygen -lf ~/.ssh/id_rsa.pub )

include_once('inc.php5');

function doDigitalOceanRequest($uri, $method='GET', $parameters=array()){
    $jsonData = json_encode($parameters);

    // not using CURL, since GAE won't support it
    $opts = array(
        'http'=>array(
            'method'=>$method,
            'header'=> "Content-type: application/json\r\n".
                "Connection: close\r\n" .
                "Content-length: " . strlen($jsonData) . "\r\n".
                "Authorization: Bearer ".DIGITAL_OCEAN_API_TOKEN,
            'content' => $jsonData,
            'protocol_version' => 1.1,
            'user_agent' => 'Regression Tool',
        )
    );

    /*print "<br>\n<br>\ndoDigitalOceanRequest<pre>";
    print_r('https://api.digitalocean.com/v2/'. $uri);
    print_r($method);
    print_r($opts);
    print '</pre>';*/

    $context = stream_context_create($opts);

// Open the file using the HTTP headers set above
    $data = file_get_contents('https://api.digitalocean.com/v2/'. $uri, false, $context);

    return json_decode($data, TRUE);
}

function getDropletInfo()
{
    $dropletInfo = null;

    $dropletResult = doDigitalOceanRequest('droplets');
    foreach ($dropletResult['droplets'] as $droplet) {
        if ($droplet['name'] == DROPLET_NAME) {
            $dropletInfo = $droplet;
        }
    }

    return $dropletInfo;
}

function getImageInfo()
{
    $imageInfo = null;

    $matchedImages = array();

    $imageResult = doDigitalOceanRequest('images');
    foreach ($imageResult['images'] as $image) {
        if (preg_match("`^" . DROPLET_NAME . "`is", $image['name'])) {
            $matchedImages[$image['created_at']] = $image;
        }
    }

    krsort($matchedImages);
    reset($matchedImages);

    $imageInfo = current($matchedImages);

    return $imageInfo;
}

function createNewDroplet($imageInfo)
{

    $data = array(
        'name' => DROPLET_NAME,
        'region' => DROPLET_REGION,
        'size' => DROPLET_SIZE,
        'image' => $imageInfo['id'],
        'ssh_keys' => array(DROPLET_SSH_KEY_TOM),
    );

    $resultData = doDigitalOceanRequest('droplets', 'POST', $data);

    return $resultData;

}

function turnOnDroplet($dropletId)
{

    $data = array(
        'type' => 'power_on',
    );

    $resultData = doDigitalOceanRequest('/droplets/' . $dropletId . '/actions', 'POST', $data);

    return $resultData;

}

function turnOffDroplet($dropletId)
{

    $data = array(
        'type' => 'power_off',
    );

    $resultData = doDigitalOceanRequest('/droplets/' . $dropletId . '/actions', 'POST', $data);

    return $resultData;

}

function snapshotDroplet(){
    $success = true;
    $rstudioDropletId = null;

    $rstudioDropletInfo = getDropletInfo();

    if ($rstudioDropletInfo) {
        $success = true;
        $rstudioDropletId = $rstudioDropletInfo['id'];

        turnOffDroplet($rstudioDropletId);
        sleep(30);

        $action = array(
            'type' => 'snapshot',
            'name' => DROPLET_NAME . " ".  date("YmdHis")
        );

        doDigitalOceanRequest('droplets/' . $rstudioDropletId .'/actions' , 'POST', $action);

        global $mysqli;
        $updateQuery = "UPDATE `reg_scheduler`.`droplet`  SET `id`=$rstudioDropletId, `name`='".DROPLET_NAME."', `status`='off', `ip`=NULL;";
        $mysqli->query($updateQuery);
    }

    return $success;
}

function snapshotCleanup(){
    $numCleanedup = 0;

    $matchedImages = array();

    $imageResult = doDigitalOceanRequest('images');
    foreach ($imageResult['images'] as $image) {
        if (preg_match("`^" . DROPLET_NAME . "`is", $image['name'])) {
            $matchedImages[$image['created_at']] = $image;
        }
    }

    krsort($matchedImages);
    reset($matchedImages);

    $mostRecent = array_shift($matchedImages);

    foreach($matchedImages as $matchedImage){

        doDigitalOceanRequest('images/' . $matchedImage['id'] , 'DELETE');
//        print 'delete '.$matchedImage['name'];
        $numCleanedup++;
    }

    return $numCleanedup;
}

function destroyDroplet(){
    $success = false;
    $rstudioDropletId = null;

    $rstudioDropletInfo = getDropletInfo();



    if ($rstudioDropletInfo) {
        $rstudioDropletId = $rstudioDropletInfo['id'];

        doDigitalOceanRequest('droplets/' . $rstudioDropletId , 'DELETE');

        global $mysqli;
        $updateQuery = "UPDATE `reg_scheduler`.`droplet`  SET `id`=$rstudioDropletId, `name`='".DROPLET_NAME."', `status`='archived', `ip`=NULL;";
        $mysqli->query($updateQuery);
    }

    return $success;
}

function checkDroplet(){
    $rstudioDropletInfo = getDropletInfo();
    global $mysqli;

    if ($rstudioDropletInfo) {
        $rstudioDropletId = $rstudioDropletInfo['id'];


        $rstudioDropletStatus = $rstudioDropletInfo['status'];

        if(isset($rstudioDropletInfo['networks']['v4'])){
            $rstudioDropletIp = $rstudioDropletInfo['networks']['v4'][0]['ip_address'];
        }



        $updateQuery = "UPDATE `reg_scheduler`.`droplet`  SET `id`=$rstudioDropletId, `name`='".DROPLET_NAME."', `status`='$rstudioDropletStatus', `ip`='$rstudioDropletIp';";
        $mysqli->query($updateQuery);
    }else{
        $updateQuery = "UPDATE `reg_scheduler`.`droplet`  SET `id`=NULL, `name`='".DROPLET_NAME."', `status`='archived', `ip`=NULL;";
        $mysqli->query($updateQuery);
    }

}


function checkOrCreateDroplet()
{
    $success = false;
    $rstudioDropletId = null;
    $rstudioDropletIp = null;
    $rstudioDropletStatus = '?';


    $rstudioDropletInfo = getDropletInfo();


    if (!$rstudioDropletInfo) {
        $rstudioImageInfo = getImageInfo();

        if ($rstudioImageInfo) {
            $resultData = createNewDroplet($rstudioImageInfo);

//            print_r($resultData);

            if ($resultData['droplet'] && $resultData['droplet']['id']) {
                $rstudioDropletInfo = getDropletInfo();
//                $rstudioDropletInfo = $resultData['droplet'];
                $rstudioDropletId = $rstudioDropletInfo['id'];
                $rstudioDropletIp = $rstudioDropletInfo['networks']['v4'][0]['ip_address'];
                $success = true;
                $rstudioDropletStatus = $rstudioDropletIp?'on':'processing';
//                if($rstudioDropletStatus )
            }
        }


    } else {
        $rstudioDropletId = $rstudioDropletInfo['id'];

        if ($rstudioDropletInfo['status'] == 'off') {
            $resultData = turnOnDroplet($rstudioDropletId);
        }

        $rstudioDropletStatus = 'on';

        $rstudioDropletIp = $rstudioDropletInfo['networks']['v4'][0]['ip_address'];


        $success = true;
    }

    global $mysqli;
    $updateQuery = "UPDATE `reg_scheduler`.`droplet`  SET `id`=$rstudioDropletId, `name`='".DROPLET_NAME."', `status`='$rstudioDropletStatus', `ip`='$rstudioDropletIp';";
    $mysqli->query($updateQuery);



//    print "\n<br>\n<br>" . DROPLET_NAME . " | ". $rstudioDropletId . " | " . $rstudioDropletIp . "\n<br>\n<br>";

    return $success;
}
