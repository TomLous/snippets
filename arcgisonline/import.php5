<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 10:29
 */

include_once("config.inc.php5");
include_once("AGOLHandler.php5");

// init DB
$mysqli = connectToDB();

// init AGOL Handler
$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_SERVER);
$agolHandler->setDebug($_ENV['DEBUG']);


// Query addresses
// Let op dat de fieldnames meoten corresponderen met de namen in de ARCGIS layer.
$query = "SELECT
              naam as `Opdrachtgever`,
              b_adres as `Straat`,
              b_huisnr as `Nummer1`,
              b_postcode as `Postcode`,
              b_woonplaats as `Plaats`,
              land as `Land`,
              b_latitude as `Lat`,
              b_longitude as `Long`
          FROM eindverbruiker
          WHERE b_latitude IS NOT NULL
          and b_longitude > 4.6 and b_longitude < 9.1
          and b_latitude > 49.6 and b_latitude < 50.1
          LIMIT 0,10";

// fields
$latitudeField = 'Lat';
$longitudeField = 'Long';
$wkid = 4326; // well known ID voor spatial reference

// do query
$resultSet = $mysqli->query($query);

// Remove Existing GEO's
$agolHandler->removeGEO();

// Loop addresses
$cnt = 0;

while ($record = $resultSet->fetch_array(MYSQLI_ASSOC)) {

    if($cnt == 0){
        print_r($record);
        $agolHandler->updateLayerDefinition($record);
    }

    $latitude = $record[$latitudeField];
    $longitude = $record[$longitudeField];


    $jsonData = array(
        "attributes" => $record,
        "geometry" => array(
            "x" => $record[$longitudeField],
            "y" => $record[$latitudeField],
            "spatialReference" => array("wkid" => $wkid)
        ));

    $agolHandler->addPoint($jsonData);

    $cnt++;
}

$resultSet->close();

if($_ENV['DEBUG']){
    print('Done publishing all features' . PHP_EOL);
}