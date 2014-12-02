<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 10:29
 */

include_once("config.inc.php5");
include_once("AGOLHandler.php5");

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
while ($record = $resultSet->fetch_array(MYSQLI_ASSOC)) {

    $latitude = $record['latitude'];
    $longitude = $record['longitude'];
    $wkid = 4326;

    $jsonData = array(
        "attributes" => $record,
        "geometry" => array(
            "x" => $latitude,
            "y" => $longitude,
            "spatialReference" => array("wkid" => $wkid)
        ));

    $agolHandler->addPoint($jsonData);

}

$resultSet->close();