<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     26/09/14 14:44
 */
include_once('inc.php5');

$id = $mysqli->escape_string($_GET['id']);


$deleteQuery = "DELETE FROM `reg_scheduler`.`queue`  WHERE id=$id;";
$mysqli->query($deleteQuery);

header('Location: '.$_SERVER['HTTP_REFERER']);
exit();