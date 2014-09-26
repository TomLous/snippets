<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     05/09/14 11:25
 */

include('config.inc.php5');

$remaining = MAX_OUTLETS_PER_RUN;
$finished = false;
$accuracyTreshold = 0;

$ouletsToGeocode = array();
$ouletsGeocoded = array();
$ouletsNotGeocoded = array();

$updateOutletParameters = array();

$mysqli = null;


// loop all databases until treshold is exceeded
while ($remaining > 0 && !$finished) {
    global $currentDB;

    // loop through databases
    $mysqli = connectToNextDB($mysqli);

    // if there isn't a connection
    if ($mysqli === false) {

        // restart the loop with a larger treshold
        if ($remaining > 0 && $accuracyTreshold < 9) {
            $currentDB = null;
            $accuracyTreshold++;
            continue;
        } else { // or quit
            $finished = true;
        }

        // if there is a connection however....
    } else {
        // retrieve all ungeocoded addresses, or ones with a bad accuracy
        $queryUnGeocodedOutlets = "SELECT klantnr, b_huisnr, b_adres, b_woonplaats, b_latitude, b_longitude, b_accuracy, land, status
              FROM eindverbruiker
              WHERE (b_latitude IS NULL OR b_accuracy < {$accuracyTreshold})
              AND status > 0
              ORDER BY b_accuracy ASC, klantnr ASC
              LIMIT 0, {$remaining}";


//        $queryUnGeocodedOutlets = "SELECT klantnr, b_huisnr, b_adres, b_woonplaats, b_latitude, b_longitude, b_accuracy, land, status
//              FROM eindverbruiker
//              WHERE new_lat IS NULL AND b_latitude IS NOT NULL
//              AND status > 0
//              ORDER BY klantnr ASC
//              LIMIT 0, {$remaining}";

        // open a resultset, and retrieve all outlets (quickly, so locking won't occur for too long)
        $resultSet = $mysqli->query($queryUnGeocodedOutlets);

        while ($outletObj = $resultSet->fetch_object()) {
            $outletObj->fromDB = $currentDB;
            $outletObj->b_huisnr_clean = cleanHuisnr($outletObj->b_huisnr);
            $ouletsToGeocode[] = $outletObj;
            $remaining--;
        }

        $resultSet->close();

        // create an array for each database
        $updateOutletParameters[$currentDB] = array();
    }
}


// loop outlets to geocode
foreach ($ouletsToGeocode as $outletObj) {
    // create a API request
    $addressString = str_replace("/","",implode(' ', array($outletObj->b_adres, $outletObj->b_huisnr_clean, $outletObj->b_woonplaats, $outletObj->land)));
    $geocodeFarmForwardCodingUrl = GEOCODEFARM_API_FORWARD_CODING_URL . urlencode($addressString) . '/';

    // retrieve & parse data
    $data = file_get_contents($geocodeFarmForwardCodingUrl);
    if(!$data){
        throw new Exception("Could not open URL ". $geocodeFarmForwardCodingUrl);
    }

    $jsonResult = json_decode($data, true);
    $jsonResult = $jsonResult['geocoding_results'];

    // if some error occured (over max, invalid api key, etc etc: QUIT)
    if ($jsonResult['STATUS']['access'] != 'KEY_VALID, ACCESS_GRANTED') {
        throw new Exception(implode("\n", $jsonResult['STATUS']));
    }

    // if the result was succesful (eg some geocode found, add it to the ok-list)
    if ($jsonResult['STATUS']['status'] == "SUCCESS") {
        $outletObj->geoCodeFarmData = $jsonResult;
        $ouletsGeocoded[] = $outletObj;
    } else { // add it to the not ok-list
        $ouletsNotGeocoded[] = $outletObj;
    }
}

// loop outlets that were succesfully geocoded
foreach ($ouletsGeocoded as $outletObj) {

//    print_r($outletObj);

    // translage geocodefarm accuracy to DO accuracy
    $accuracy = $outletObj->geoCodeFarmData['ADDRESS']['accuracy'];
    $doAccuracy = 0;
    switch ($accuracy) {
        case 'VERY ACCURATE':
            $doAccuracy = 9;
            break;
        case 'GOOD ACCURACY':
            $doAccuracy = 8;
            break;
        case 'ACCURATE':
            $doAccuracy = 5;
            break;
        case 'UNKNOWN ACCURACY':
            $doAccuracy = 1;
            break;
    }

    // only when new lat/long is better then previous one
    if($outletObj->b_accuracy <=  $doAccuracy || !$outletObj->b_latitude ){

        // create update params
        $updateOutletParameters[$outletObj->fromDB][] = array(
            'latitude' => $outletObj->geoCodeFarmData['COORDINATES']['latitude'],
            'longitude' => $outletObj->geoCodeFarmData['COORDINATES']['longitude'],
            'accuracy' => $doAccuracy,
            'klantnr' => $outletObj->klantnr);
    }
}

// loop outlets that were not  geocoded
foreach ($ouletsNotGeocoded as $outletObj) {
    // create update params
    $updateOutletParameters[$outletObj->fromDB][] = array(
        'latitude' => 0,
        'longitude' => null,
        'accuracy' => null,
        'klantnr' => $outletObj->klantnr);
}


print_r($updateOutletParameters);

// loop queries
foreach($updateOutletParameters as $dbName => $updateParameters){
    $mysqli = connectToDB($dbName);

    $updateOutletQuery = "UPDATE eindverbruiker SET b_latitude=?, b_longitude=?, b_accuracy=? WHERE klantnr=?";
//    $updateOutletQuery = "UPDATE eindverbruiker SET new_lat=?, new_long=?, new_acc=? WHERE klantnr=?";

    $updateOutletStatement = $mysqli->prepare($updateOutletQuery);
    $updateOutletStatement->bind_param('ddis', $latitude, $longitude, $accuracy, $klantnr); // use string for klantnr, evethough most new publics use int (i)

    foreach($updateParameters as $params){
        $latitude = $params['latitude'];
        $longitude = $params['longitude'];
        $accuracy = $params['accuracy'];
        $klantnr = $params['klantnr'];
        $updateOutletStatement->execute();
    }

    $updateOutletStatement->close();

    $mysqli->close();
}


/**
 * Functions
 */

function cleanHuisnr($huisnr){
    $find =array(
        '`(t/o|boven|naast|nabij)`is', // remove aanduiding
        '`(\d+)-?\s*(hs|bg|ong|bis|bov|sout|werf|sous|magazijn|lnks|wink|wkl|huis|waterzijde|achter|keld|I{1,3})`is', // remove verdieping
        '`\b(Noordzijde|Zuidzijde|Oostzijde|westzijde|NZ|ZZ|OZ|WZ|N\.Z\.|Z\.Z\.|O\.Z\.|W\.Z\.|noord|oost|zuid|west|no|nw|zo|zw|noo|zui|nrd)\b`is', // remove richting
        '`^(0|on|niet|-|x).*$`is', // clean empty
        '`(^\d+)[\s\-]*([a-z]{1,2})(\s|$)`is', // bv 1-a
        '`(\d+(\s*[a-z]+)?)\s*(-|t.m)\s*\d+(\s*[a-z]+)?`is', // bv 42-44,
        '`^(\d+\w*).*$`is', // bv alles achter 1a,


    );

    $replace = array(
        '', // t\o
        '\\1', // verdieping aanduidingen
        '', // remove richting
        '',
        '\\1\\2', // 1-a -> 1a
        '\\1', // 42-44 -> 42
        '\\1',

    );

    $huisnr = preg_replace($find, $replace, $huisnr);

    $huisnr =  trim($huisnr);

    $huisnr = $huisnr == 0 ? '' : $huisnr;

    return $huisnr;
}





