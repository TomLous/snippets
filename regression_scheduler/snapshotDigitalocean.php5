<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     30/09/14 09:17
 */

include_once('inc.php5');
include_once('digitalocean.inc.php5');
ini_set('memory_limit', '1024M');
set_time_limit(0);
ob_implicit_flush(true);
ob_end_flush();



print "Snapshot request created? : "; flush(); ob_flush();
$snapshotQueued = snapshotDroplet();
print $snapshotQueued?'Y':'N';
print PHP_EOL;flush(); ob_flush();

print "Waiting 30 min for snapshot"; flush(); ob_flush();
sleep(60 * 30);

print "Clean up old snapshots: ";flush(); ob_flush();
$snapshotsCleanedup = snapshotCleanup();
print $snapshotsCleanedup?'Y':'N';
print PHP_EOL;flush(); ob_flush();

sleep(60);

print "Server stopped & destoyed? ";flush(); ob_flush();
$dropletDestroyed = destroyDroplet();
print $dropletDestroyed?'Y':'N';
print PHP_EOL;flush(); ob_flush();


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
ob_end_flush();
?>
<!--<br />-->
<!--<br />-->
<!--Also check <a href="https://cloud.digitalocean.com/images">snapshot</a> to see if you can delete som old snapshots-->
<br />
<br />
<a href="<?php print $_SERVER['HTTP_REFERER'];?>" >&raquo; terug</a>



