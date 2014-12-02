<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     02/12/14 15:55
 */
include_once("config.inc.php5");
include_once("AGOLHandler.php5");

$mysqli = connectToDB();

// init AGOL Handler
$agolHandler = new \arcgisonline\lib\AGOLHandler(AGOL_USERNAME, AGOL_PASSWORD, AGOL_SERVICE, AGOL_FEATURE_SERVER);
$agolHandler->setDebug($_ENV['DEBUG']);


echo "<pre>";
$info = $agolHandler->getLayerInfo();
print_r($info);