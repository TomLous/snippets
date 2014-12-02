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
    private $http;
    private $expires;
    private $featureServerUrl;

    private $debug = false;

    const referer = "http://www.arcgis.com/";
    const apiGenerateTokenUrl = "https://www.arcgis.com/sharing/rest/generateToken";
    const httpPrefix = "http://www.arcgis.com/sharing/rest";
    const httpsPrefix = "https://www.arcgis.com/sharing/rest";

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
     * @param int $exp
     * @throws \Exception
     */
    private function getToken($exp = 60)
    {

        $query_dict = array('username' => $this->username,
            'password' => $this->password,
            'referer' => self::referer);


        $data = $this->sendAGOLRequest(self::apiGenerateTokenUrl . '?f=json', $query_dict, "JSON");
//        $query_string = http_build_query($query_dict);
//
//        $options =
//            array("http" =>
//                array(
//                    "method" => "POST",
//                    "header" => "Accept-language: nl\r\n" .
//                        "Content-type: application/x-www-form-urlencoded\r\n"
//                        . "Content-Length: " . strlen($query_string) . "\r\n",
//                    "content" => $query_string,
//                    "follow_location" => false
//                )
//            );
//
//        $context = stream_context_create($options);
//        $result = file_get_contents(self::apiGenerateTokenUrl . '?f=json', false, $context);
//
//        $data = json_decode($result, TRUE);

        if (!isset($data['token'])) {
            throw new \Exception('Missing AGOL token');
        }

        $this->token = $data['token'];
        $this->expires = $data['expires'];
        $this->http = $data['ssl'] ? self::httpsPrefix : self::httpPrefix;

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
                "Long" => $Y,
                "Lat" => $X
            ),
            "geometry" => array(
                "x" => X,
                "y" => Y
            ));
    }

    /**
     * This function queries the service layer end points to ensure there is geometry as the
     * script does an update on existing geometry.
     * If there are no features, a dummy point is entered.
     */
    public function removeGEO()
    {
        if ($this->debug) {
            print ("Removing all features from " . $this->featureServerUrl. PHP_EOL);
        }

        $ptURL = $this->featureServerUrl . "/0/deleteFeatures";

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

    /**
     * Use a URL and X/Y values to update an existing point.
     * @param $data
     * @throws \Exception
     * @return boolean
     */
    public function addPoint($data)
    {
        if ($this->debug) {
            print ("Adding feature point". PHP_EOL);
        }

        $ptURL = $this->featureServerUrl . "/0/addFeatures";

        $submitData = array(
            "features" => $data,
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

        if($this->debug){
            print($URL . PHP_EOL . var_export($context, true) . PHP_EOL . $jsonResponse . PHP_EOL );
        }

        $jsonOuput = json_decode($jsonResponse, TRUE);


        if ($returnType == "JSON") {
            return jsonOuput;
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

    private function logError($data){
        print("logerror " . $data. PHP_EOL);
    }
}