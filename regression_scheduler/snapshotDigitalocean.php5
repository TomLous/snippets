<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     30/09/14 09:17
 */

include_once('inc.php5');
include_once('digitalocean.inc.php5');


$snapshotQueued = snapshotDroplet();

print "Snapshot queued? (This process takes a while to complete. Please be patient)";
print $snapshotQueued?'Y':'N';

sleep(60 * 5);

$snapshotsCleanedup = snapshotCleanup();

print "Clean up old snapshots";
print $snapshotsCleanedup?'Y':'N';

sleep(60);

$dropletDestroyed = destroyDroplet();

print "Server stopped & destoyed? ";
print $dropletDestroyed?'Y':'N';


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
<!--<br />-->
<!--<br />-->
<!--Also check <a href="https://cloud.digitalocean.com/images">snapshot</a> to see if you can delete som old snapshots-->
<br />
<br />
<a href="<?php print $_SERVER['HTTP_REFERER'];?>" >&raquo; terug</a>



