<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     30/09/14 09:17
 */

include_once('inc.php5');
include_once('digitalocean.inc.php5');


$dropletActive = checkOrCreateDroplet();

print "Server active? ";
print $dropletActive?'Y':'N';

//header('Location: '.$_SERVER['HTTP_REFERER']);
//exit();
/*
print '<pre>';
print '<h2>Droplet:</h2>';
print_r($rstudioDropletInfo);
print '<h2>Image:</h2>';
print_r($rstudioImageInfo);
print '<h2>Create:</h2>';
print_r($resultData);*/

?>
<br />
<br />
<a href="<?php print $_SERVER['HTTP_REFERER'];?>" >&raquo; terug</a>



