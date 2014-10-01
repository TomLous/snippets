<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     26/09/14 14:32
 */

include_once('inc.php5');
include_once('digitalocean.inc.php5');
$queryPossibleDatabases = "SELECT `table_schema` AS db_name FROM `information_schema`.`tables` WHERE `table_name` = 'reg_kbns'";
$rsPossibleDatabases = $mysqli->query($queryPossibleDatabases);

$possibleDatabases = array();
while($obj = $rsPossibleDatabases->fetch_object()){
    $possibleDatabases[] = $obj->db_name;
}

$querySchedule = "SELECT * FROM `reg_scheduler`.`queue` ORDER BY `timestamp` DESC";
$rsSchedule = $mysqli->query($querySchedule);

$schedule = array();
while($row = $rsSchedule->fetch_assoc()){
    $schedule[] = $row;
}

checkDroplet();
$queryDropletState = "SELECT * FROM `reg_scheduler`.`droplet`";
$rsDropletState = $mysqli->query($queryDropletState);
$dropletState = $rsDropletState->fetch_object();


if($dropletState->status == 'processing'){
    checkOrCreateDroplet();
    $rsDropletState = $mysqli->query($queryDropletState);
    $dropletState = $rsDropletState->fetch_object();
}

$url = 'http://'.$dropletState->ip.'/lastrun.potentshell';
$lastrun = 0;
$lastrun =  (int) @file_get_contents($url);


$diff = 0;

if($lastrun > 0){
   $endtime = $lastrun + $powerofftime;
   $diff =  $endtime - time();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="favicon.ico" />
    <meta http-equiv="refresh" content="10">

    <!-- concatenate and minify for production -->
    <link rel="stylesheet" href="../assets/css/reset.css" />
    <link rel="stylesheet" href="../assets/css/style.css" />
    <title>Regression scheduler</title>
</head>

<body>
<h2>Run a regression</h2>
<p style="height: 40px"></p>
<form method="post" action="submitRegression.php5">
    <input type="hidden" value="<?php print md5(time().rand(0,1000));?>" name="hash">
    <label for="dbname">database</label>
    <select name="dbname" id="dbname" required="required">
        <option disabled selected>
        <?php foreach($possibleDatabases as $dbName){ ?>
        <option value="<?php print $dbName;?>"><?php print $dbName;?></option>
        <?php } ?>
    </select>

    <input type="submit" value="Run">

</form>
</p>
<hr>
<table>
    <tr>
        <th>id</th>
        <th>db</th>
        <th>hash</th>
        <th>planned</th>
        <th>status</th>
        <th>log</th>
        <th>action</th>
    </tr>
    <?php foreach($schedule as $row){ ?>
        <tr>
            <?php foreach($row as $cell){ ?>
                <td><?php print $cell;?></td>
            <?php } ?>
            <td><a href="http://<?php print $dropletState->ip;?>/regressie-results/runlog.<?php print $row['dbname'];?>.<?php print $row['hash'];?>.txt" target="_blank">show log</a></td>
            <td><a href="deleteRegression.php5?id=<?php print $row['id'];?>">delete</a></td>
        </tr>
    <?php } ?>
</table>

<p style="height: 40px"></p>
<hr>
<h3>Digital Ocean Droplet status</h3>

<table>
    <tr><td>name</td><td><?php print $dropletState->name;?></td></tr>
    <tr><td>droplet</td><td><a href="https://cloud.digitalocean.com/droplets/<?php print $dropletState->id;?>" target="_blank"><?php print $dropletState->id;?></a></td></tr>
    <tr><td>status</td><td><?php print $dropletState->status;?></td></tr>
    <tr><td>ip</td><td><a href="http://<?php print $dropletState->ip;?>:8787/" target="_blank"><?php print $dropletState->ip;?></a></td></tr>
    <tr><td>shutdown in </td><td><?php print gmdate("H \h: i \m", $diff);?></td></tr>
</table>
<p style="height: 40px"></p>
<h3>Digital Ocean Droplet acties (manual)</h3>
<br />
<i>Only use these if you know what you are doing :-)</i>
<br />
<br />
<a href="startDigitalocean.php5">Start Server</a><br />
<a href="snapshotDigitalocean.php5">Power off, Snapshot Server, Destroy</a> (takes a long while (5-7 min), please don't close window)<br />
<a href="stopDigitalocean.php5">Stop (&destroy) Server</a>
</body>

</html>