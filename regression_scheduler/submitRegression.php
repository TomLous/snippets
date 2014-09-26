<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     26/09/14 14:44
 */
include_once('inc.php');

$dbname = $mysqli->escape_string($_POST['dbname']);
$key = $mysqli->escape_string($_POST['key']);

$insertQuery = "INSERT INTO `reg_scheduler`.`queue`  (`dbname`, `key`) VALUES('$dbname','$key');";
$mysqli->query($insertQuery);

header('Location: '.$_SERVER['HTTP_REFERER']);
exit();