<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     30/09/14 11:50
 */

include_once('inc.php5');
include_once('digitalocean.inc.php5');


$dropletDestroyed = destroyDroplet();

print "Server stopped & destoyed? ";
print $dropletDestroyed?'Y':'N';

?>
<br />
<br />
<a href="<?php print $_SERVER['HTTP_REFERER'];?>" >&raquo; terug</a>