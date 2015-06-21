<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     21/06/15 15:23
 */
include('config.inc.php5');
$tableName = 'kenmerken_tags_solr';
$start = time();

$mysqli = connectToGlobalDB();

$langArray = ['english','dutch', 'deutsch', 'french', 'spanish', 'italian', 'czech', 'polish', 'portuguese'];

$q = "SELECT * FROM `kenmerken_taal` WHERE `tabel`='kenmerken' AND `segmentcode`='';";
$rs = $mysqli->query($q);
$finalTable = array(
    'fields'=>
        array('id'=>'`id` INT(11) NOT NULL AUTO_INCREMENT',
              'real_from'=>'`real_from` INT(11)',
              'real_to'=>'`real_to` INT(11)'
        ),
    'index' =>
        array('PRIMARY KEY (`id`)')

);



$distinctKennrs = array();
$lookupTable = array();
while($row = $rs->fetch_assoc()){
    $key = str_replace('K','kenmerk',$row['code']);


    $q2 = "SELECT DISTINCT `{$key}` as kennr FROM `eindverbruiker` WHERE `{$key}` LIKE '%ken%';";
//    print $q2.PHP_EOL;
    $rs2 = $mysqli->query($q2);
    $row['validKeys'] = array();
    $multiple = false;
    while($row2 = $rs2->fetch_assoc()){
        $kennrs = explode(',',trim($row2['kennr']));
        $multiple = $multiple || count($kennrs)>1;
        foreach($kennrs as $kennr){
            if($kennr && substr($kennr,0,3)=='ken'){
                $row['validKeys'][$kennr] = $kennr;
                $distinctKennrs[$kennr] = $kennr;
            }
        }
    }
    $row['multiple'] = $multiple;

    $finalTable['fields'][$key] = '`'.$key .'` VARCHAR(6)';
    $finalTable['index'][$key] = "KEY `$key` (`$key`)";

    $lookupTable[$key] = $row;
}

$codes = "'". implode("','", $distinctKennrs)."'";
$q3 = "SELECT * FROM  `kenmerken_taal` WHERE `tabel`='kennr' AND `code` IN ({$codes});";
//print $q3.PHP_EOL;
$rs3 = $mysqli->query($q3);
$lookupTableKennrs = array();
while($row3 = $rs3->fetch_assoc()){
    $kennr = trim($row3['code']);
    if($kennr){
        $lookupTableKennrs[$kennr] = $row3;
    }
}


$insertQueries = array();
foreach($lookupTable as $field=>$fieldData){
    foreach($fieldData['validKeys'] as $pos=>$kennr){
        if(isset($lookupTableKennrs[$kennr])){
            $insertFieldValues = array();


            $kennrInfo = $lookupTableKennrs[$kennr];
            $insertFieldValues[$field] = $kennr;
            foreach($langArray as $lang){
                $str = 'waarde_'.$lang;
                $parentVal = null;
                if(isset($fieldData[$str])){
                   $parentVal = $fieldData[$str];
                }else{
                   $parentVal = $fieldData['waarde_english'];
                }

                $childVal = null;
                if(isset($kennrInfo[$str])){
                    $childVal = $kennrInfo[$str];
                }else{
                    $childVal = $kennrInfo['waarde_english'];
                }
                $insertFieldValues[$str] = createTag($lang, $parentVal, $childVal, '⁄', '⁞');
            }

            if(preg_match('/^(<|>|less than|fewer than|more than|up to)\s*[\d\.\,]+(\s*(till|to))?(\s*[\d\.\,]+)?/is',$kennrInfo['waarde_english'])){
                $from = 'NULL';
                $to = 'NULL';

                $firstDigitTo = false;
                $range = false;

                if(preg_match('/^(<|less than|fewer than|up to)/is', $kennrInfo['waarde_english'])){
                    $from = 0;
                    $firstDigitTo = true;
                    $range = true;
                }elseif(preg_match('/(till|to)/is', $kennrInfo['waarde_english'])){
                    $range = true;
                }elseif(preg_match('/(>|more than|or more)/is', $kennrInfo['waarde_english'])){
                    $range = true;
                }

                preg_match('/[\d\.\,]+/is', $kennrInfo['waarde_english'], $matches);
                foreach($matches as $m=>$match){
                    $matches[$m] = intval(preg_replace('/\D+/','', $match));
                }

                if(isset($matches[0])){
                    if($firstDigitTo){
                        $to = $matches[0];
                    }else{
                        $from = $matches[0];
                    }
                }

                if(isset($matches[1]) && !$firstDigitTo){
                    $to = $matches[1];
                }elseif(!$range){
                    $to = $from;
                }

                $insertFieldValues['real_from'] = $from;
                $insertFieldValues['real_to'] = $from;
            }


            $fields = implode(', ',array_keys($insertFieldValues));
            $values = "'".implode("', '",array_map('mysql_real_escape_string', $insertFieldValues))."'";
            $values = str_replace("'NULL'", 'NULL',$values);

            $insertQueries[] = "INSERT INTO `$tableName` ($fields) VALUES($values)";

        }
    }
}

foreach($langArray as $lang){
    $finalTable['fields']['waarde_'.$lang] = '`waarde_'.$lang. "` VARCHAR(255) NOT NULL DEFAULT ''";
}

$queries = array(
        'DROP TABLE IF EXISTS `'.$tableName.'`;',
        'CREATE TABLE `'.$tableName.'` ('. implode(",\n",$finalTable['fields']) . ",\n".  implode(",\n",$finalTable['index']) .') ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT=\'Generated by generateKenmerkenTagsTable.php5. Do not change!\';'
);



function createTag($lang, $parent, $value, $sep, $masterSep){
    $prefix = 'Location';
    switch($lang){
        case 'english':
            $prefix = 'Location';
            break;
        case 'dutch':
            $prefix = 'Locatie';
            break;
        case 'deutsch':
            $prefix = 'Lage';
            break;
        case 'french':
            $prefix = 'Emplacement';
            break;
        case 'spanish':
            $prefix = 'Ubicación';
            break;
        case 'italian':
            $prefix = 'Posizione';
            break;
        case 'czech':
            $prefix = 'Umístění';
            break;
        case 'polish':
            $prefix = 'Lokalizacja';
            break;
        case 'portuguese':
            $prefix = 'Localização';
            break;

    }
    $sepQuoted = '/'. preg_quote($sep, '/') . '/is';
    $masterSepQuoted = '/'. preg_quote($masterSep, '/') . '/is';


    $parent = preg_replace($sepQuoted, '/', ucwords(trim($parent)));
    $value = preg_replace($sepQuoted, '/', ucwords(trim($value)));

    $combined = $prefix.$sep.$parent.$sep.$value;
    $combined = preg_replace($masterSepQuoted, '|', trim($combined));
    return $combined;
}

$queries = $queries + $insertQueries;

foreach($queries as $q4){
    print $q4.PHP_EOL;
    $mysqli->query($q4);
    print $mysqli->error.PHP_EOL;
}



//print (time() - $start).' sec'.PHP_EOL;
////print_r($distinctKennrs);
//print_r($lookupTable);
//print_r($lookupTableKennrs);
////print_r($finalTable);
//print_r($queries);