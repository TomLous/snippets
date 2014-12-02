<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 10:29
 */

include_once("lib\AGOLHandler.php5");

$mysqli = connectToDB();

// init AGOL Handler
$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_SERVER);
$agolHandler->setDebug($_ENV['DEBUG']);

// Query addresses
$query = "SELECT
              naam as `name`,
              b_adres as street,
              b_huisnr as houseNumber,
              b_postcode as postalCode,
              b_woonplaats as place,
              land as countryCode,
              b_latitude as latitude,
              b_longitude as longitude
          FROM eindverbruiker
          WHERE b_latitude IS NOT NULL
          and b_longitude > 8.6 and b_longitude < 9.1
          and b_latitude > 49.6 and b_latitude < 50.1
          LIMIT 0,100";

$resultSet = $mysqli->query($query);

// Remove Existing GEO's
$agolHandler->removeGEO();


// Loop addresses
$cnt = 0;
while ($recordObj = $resultSet->fetch_object()) {


    $jsonData = array(
        "attributes" => array(
            "Land" => $recordObj->countryCode,
            "Straat" => $recordObj->street,
            "Nummer" => $recordObj->houseNumber,
            "Long" => $recordObj->longitude,
            "Lat" => $recordObj->latitude,
            "Postcode" => $recordObj->postalCode,
            "Opdrachtgever" => $recordObj->name,
            "Plaats" => $recordObj->place,
        ),
        "geometry" => array(
            "x" => $recordObj->longitude,
            "y" => $recordObj->latitude,
            "spatialReference" => array("wkid" => 4326)
        ));

    $agolHandler->addPoint($jsonData);
    if($_ENV['DEBUG']){
        print ($cnt+1 . " Adding feature ".$recordObj->name );
    }
    $cnt++;

}

$resultSet->close();