<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     26/09/14 14:32
 */

include_once('inc.php');
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
<form method="post" action="submitRegression.php">
    <input type="hidden" value="<?php print md5(time().rand(0,1000));?>" name="key">
    <label for="dbname">database</label>
    <select name="dbname" id="dbname" required="required">
        <option disabled selected>
        <?php foreach($possibleDatabases as $dbName){ ?>
        <option value="<?php print $dbName;?>"><?php print $dbName;?></option>
        <?}?>
    </select>

    <input type="submit" value="Run">

</form>
</p>
<hr>
<table>
    <?php foreach($schedule as $row){ ?>
        <tr>
            <?php foreach($row as $cell){ ?>
                <td><?php print $cell;?></td>
            <? } ?>
            <td><a href="deleteRegression.php?id=<?php print $row['id'];?>">delete</a></td>
        </tr>
    <?}?>
</table>
</body>

</html>