<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     26/09/14 14:44
 */
include_once('inc.php5');
include_once('digitalocean.inc.php5');

$dbname = $mysqli->escape_string($_POST['dbname']);
$hash = $mysqli->escape_string($_POST['hash']);

$insertQuery = "INSERT INTO `reg_scheduler`.`queue`  (`dbname`, `hash`) VALUES('$dbname','$hash');";
$mysqli->query($insertQuery);

$dropletActive = checkOrCreateDroplet();

header('Location: '.$_SERVER['HTTP_REFERER']);
exit();