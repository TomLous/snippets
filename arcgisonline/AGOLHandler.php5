<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 09:50
 */

namespace arcgisonline\lib;

/**
 * Class AGOLHandler
 * ArcGIS Online handler class.
 * -Generates and keeps tokens
 * -template JSON feature objects for point
 * @package arcgisonline\lib
 *
 */
class AGOLHandler
{

    private $username;
    private $password;
    private $serviceName;
    private $token;
    private $ssl;
    private $expires;
    private $featureServerUrl;

    private $debug = true;

    const referer = "http://www.arcgis.com/";
    const apiGenerateTokenUrl = "https://www.arcgis.com/sharing/rest/generateToken";
    const apiGenerateAdminTokenUrl = "https://www.arcgis.com/admin/generateToken";


    function __construct($username, $password, $serviceName, $featureServerUrl)
    {
        $this->username = $username;
        $this->password = $password;
        $this->serviceName = $serviceName;
        $this->featureServerUrl = $featureServerUrl;
        $this->getToken();
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * getToken
     * Generates a token.
     * @param int $expiration
     * @param bool $admin
     * @throws \Exception
     */
    private function getToken($expiration = 60, $admin=false)
    {

        $query_dict = array('username' => $this->username,
            'password' => $this->password,
            'referer' => self::referer,
            'expiration' =>$expiration );


        $data = $this->sendAGOLRequest(self::apiGenerateTokenUrl . '?f=json', $query_dict, "JSON");

        if (!isset($data['token'])) {
            throw new \Exception('Missing AGOL token');
        }

        $this->token = $data['token'];
        $this->expires = $data['expires'];
        $this->ssl = $data['ssl'];

    }

    /**
     * jsonPoint
     * Customized JSON point object for ISS schema
     * @param $X
     * @param $Y
     * @param $ptTime
     * @return array
     */
    public static function jsonPoint($X, $Y, $ptTime)
    {
        return array(
            "attributes" => array(
                "OBJECTID" => 1,
                "TextDate" => strftime('%m/%d/%Y %H:%MZ', $ptTime),
                "Long" => $X,
                "Lat" => $Y
            ),
            "geometry" => array(
                "x" => $X,
                "y" => $Y
            ));
    }

    /**
     * This function queries the service layer end points to ensure there is geometry as the
     * script does an update on existing geometry.
     * If there are no features, a dummy point is entered.
     * @param int $layer
     * @throws \Exception
     */
    public function removeGEO($layer=0)
    {
        if ($this->debug) {
            print ("Removing all features from " . $this->featureServerUrl. PHP_EOL);
        }

        $ptURL = $this->featureServerUrl . "/" . $layer . "/deleteFeatures";

        $query_dict = array(
            "f" => "json",
            "where" => "1=1",
            "token" => $this->token
        );

        if (!$this->sendAGOLRequest($ptURL, $query_dict, 1)) {
            throw new \Exception("Error deleting features");
        }

        if ($this->debug) {
            print("All features from service " . $this->featureServerUrl . " deleted". PHP_EOL);
        }
    }


    public function updateLayerDefinition($data, $layer=0){
        $layerInfo = $this->getLayerInfo($layer);
        $currentFields = $layerInfo['fields']; // array of field obejcts

        $neededFieldNames = array_keys($data);
        $neededFieldNames[] = 'FID'; //unique keys

        $fieldsToRemove = array();
        $fieldsToAdd = array();

        foreach($data as $key=>$value){
            if(is_scalar($value)){
                if(is_float($value) || is_double($value)){
                    $fieldsToAdd[$key] = array(
                        'name' => $key,
                        'type' =>  'esriFieldTypeDouble',
                        'actualType' =>  'float',
                        'alias' =>  $key,
                        'sqlType' =>  'sqlTypeFloat',
                        'nullable' =>  1,
                        'editable' =>  1,
                        'domain' =>  null,
                        'defaultValue' =>  null,
                    );
                }
                elseif(is_int($value)){
                    $fieldsToAdd[$key] = array(
                        'name' => $key,
                        'type' =>  'esriFieldTypeInteger',
                        'actualType' =>  'int',
                        'alias' =>  $key,
                        'sqlType' =>  'sqlTypeInteger',
                        'nullable' =>  1,
                        'editable' =>  1,
                        'domain' =>  null,
                        'defaultValue' =>  null,
                    );
                }else{
                    $fieldsToAdd[$key] = array(
                        'name' => $key,
                        'type' =>  'esriFieldTypeString',
                        'actualType' =>  'nvarchar',
                        'alias' =>  $key,
                        'sqlType' =>  'sqlTypeInteger',
                        'length' => strlen($value) > 256 ? max(strlen($value),1024) : 256,
                        'nullable' =>  1,
                        'editable' =>  1,
                        'domain' =>  null,
                        'defaultValue' =>  null,
                    );
                }
            }
        }


        foreach($currentFields as $num=>$field){
            if(!in_array($field['name'], $neededFieldNames)){
                $fieldsToRemove[]['name'] = $field['name'];
            }elseif(isset($fieldsToAdd[$field['name']])){
                unset($fieldsToAdd[$field['name']]);
            }
        }

        $fieldsToAdd = array("fields" => array_values($fieldsToAdd));
        print_r($fieldsToAdd);


        $ptURL = $this->featureServerUrl .   "/" . $layer . "/addToDefinition" ;

        print_r($ptURL);

        $query_dict = array(
            "f" => "json",
            "addToDefinition" => json_encode($fieldsToAdd),
            "token" => $this->token
        );

        print_r($query_dict);


        $result = $this->sendAGOLRequest($ptURL , $query_dict, "JSON");

        print_r($result);



        print_r($fieldsToRemove);


        $ptURL = $this->featureServerUrl .   "/" . $layer . "/deleteFromDefinition" ;

        print_r($ptURL);

        $query_dict = array(
            "f" => "json",
            "fields" => json_encode($fieldsToRemove),
            "token" => $this->token
        );

        print_r($query_dict);


        $result = $this->sendAGOLRequest($ptURL , $query_dict, "JSON");

        print_r($result);



    }

    public function getLayerInfo($layer=0)
    {
        $ptURL = $this->featureServerUrl .   "/" . $layer;

        $query_dict = array(
            "f" => "json",
            "token" => $this->token
        );


        $layerInfo = $this->sendAGOLRequest($ptURL . '?f=json', $query_dict, "JSON");




        return $layerInfo;
    }

    /**
     * Use a URL and X/Y values to update an existing point.
     * @param $data
     * @param int $layer
     * @throws \Exception
     * @return boolean
     */
    public function addPoint($data, $layer=0)
    {
        $feature = json_encode($data);

        if ($this->debug) {
            $id = current($data['attributes']);
            $lat = $data['geometry']['y'];
            $long = $data['geometry']['x'];
            print ("Adding feature point $id @ [$lat, $long]". PHP_EOL);
        }

        $ptURL = $this->featureServerUrl . "/" . $layer . "/addFeatures";

        $submitData = array(
            "features" => $feature,
            "f" => "json",
            "token" => $this->token
        );



        if (!$this->sendAGOLRequest($ptURL, $submitData, 1)) {
            $this->logError($data);
            throw new \Exception("Error adding point: " . $data);
        }


        return true;
    }

    /**
     *  Helper function which takes a URL and a dictionary and sends the request.
     * returnType values =
     * False : make sure the geometry was updated properly
     * "JSON" : simply return the raw response from the request, it will be parsed by the calling function
     * else (number) : a numeric value will be used to ensure that number of features exist in the response JSON
     * @param $URL
     * @param $query_dict
     * @param bool $returnType
     * @throws \Exception
     * @return mixed
     */
    private function sendAGOLRequest($URL, $query_dict, $returnType = false)
    {
        $query_string = http_build_query($query_dict);

        $options =
            array("http" =>
                array(
                    "method" => "POST",
                    "header" => "Accept-language: nl\r\n" .
                        "Content-type: application/x-www-form-urlencoded\r\n"
                        . "Content-Length: " . strlen($query_string) . "\r\n",
                    "content" => $query_string,
                    "follow_location" => false
                )
            );

        $context = stream_context_create($options);
        $jsonResponse = file_get_contents($URL, false, $context);

        /*if($this->debug){
            print($URL . PHP_EOL . var_export($options, true) . PHP_EOL . $jsonResponse . PHP_EOL );
        }*/

        $jsonOuput = json_decode($jsonResponse, TRUE);


        if ($returnType == "JSON") {
            return $jsonOuput;
        } elseif (!$returnType) {
            if (isset($jsonOuput['addResults'])) {
                foreach ($jsonOuput['addResults'] as $item) {
                    if ($item['success']) {
                        if ($this->debug) {
                            print("request submitted successfully". PHP_EOL);
                        }
                    } else {
                        throw new \Exception("Error: {0} " . $jsonResponse);
                    }
                }
            }
            if (isset($jsonOuput['deleteResults'])) {
                foreach ($jsonOuput['deleteResults'] as $item) {
                    if ($item['success']) {
                        if ($this->debug) {
                            print("request submitted successfully". PHP_EOL);
                        }
                    } else {
                        throw new \Exception("Error: {0} " . $jsonResponse);
                    }
                }
            }
        }


        #else:  # Check that the proper number of features exist in a layer
        #    if len(jsonOuput['features']) != returnType:
        #        print("FS layer needs seed values")
        #        return False

        return true;
    }

    /**
     * @todo fill code
     * fillEmptyGeo
     */
    public function fillEmptyGeo(){

    }

    /**
     * updatePoint
     * @todo fill code
     * @param $X longitude
     * @param $Y latitude
     * @param $timestamp
     */
    public function updatePoint($X, $Y, $timestamp){

    }

    /**
     * logError
     * @todo wriet to file??
     * @param $data
     */
    private function logError($data){

        print("logerror " . $data. PHP_EOL);
    }
}