#!/usr/local/php5-fcgi/bin/php
<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     21/06/15 15:23
 */
include('config.inc.php5');
$tableName = 'kenmerken_tags';
$start = time();
$tagSeparator = '⁄';
$tagsSeparator = '⁞';

$conn = connectToGlobalDB();

$langArray = ['english', 'dutch', 'deutsch', 'french', 'spanish', 'italian', 'czech', 'polish', 'portuguese', 'hungarian'];

$q = "SELECT * FROM `kenmerken_taal` WHERE `tabel`='kenmerken' AND `segmentcode`='';";
$rs = mysql_query($q, $conn);
if(mysql_error($conn)){
    print mysql_error($conn) . PHP_EOL;
}
$finalTable = array(
    'fields' =>
        array('id' => '`id` INT(11) NOT NULL AUTO_INCREMENT',
            'real_from' => '`real_from` INT(11)',
            'real_to' => '`real_to` INT(11)'
        ),
    'index' =>
        array('PRIMARY KEY (`id`)')

);


$distinctKennrs = array();
$lookupTable = array();
while ($row = mysql_fetch_assoc($rs)) {
    $key = str_replace('K', 'kenmerk', $row['code']);


    $q2 = "SELECT DISTINCT `{$key}` as kennr FROM `eindverbruiker`;";
//    print $q2.PHP_EOL;
    $rs2 = mysql_query($q2, $conn);
    if(mysql_error($conn)){
        print mysql_error($conn) . PHP_EOL;
    }
    $row['validKeys'] = array();
    $row['validValues'] = array();
    $multiple = false;
    while ($row2 = mysql_fetch_assoc($rs2)) {
        $kennrs = explode(',', trim($row2['kennr']));
        $multiple = $multiple || count($kennrs) > 1;
        foreach ($kennrs as $kennr) {
            if ($kennr) {
                if (substr($kennr, 0, 3) == 'ken' || substr($kennr, 0, 3) == 'PCL') {
                    $row['validKeys'][$kennr] = $kennr;
                    $distinctKennrs[$kennr] = $kennr;
                } else {
                    $row['validValues'][$kennr] = $kennr;
                }


            }
        }

    }
    $row['multiple'] = $multiple;

    foreach($langArray as $lang){
        if(isset($row['waarde_'.$lang])){
            $row['real_key_'.$lang] = sanitzeVal($lang, $row['waarde_'.$lang]);
        }
    }

    $finalTable['fields'][$key] = '`' . $key . '` VARCHAR(6)';
    $finalTable['index'][$key] = "KEY `$key` (`$key`)";

    $lookupTable[$key] = $row;
}

$codes = "'" . implode("','", $distinctKennrs) . "'";
$q3 = "SELECT * FROM  `kenmerken_taal` WHERE `tabel`='kennr' AND `code` IN ({$codes});";

//print $q3.PHP_EOL;
$rs3 = mysql_query($q3, $conn);
if(mysql_error($conn)){
    print mysql_error($conn) . PHP_EOL;
}

$lookupTableKennrs = array();
while ($row3 = mysql_fetch_assoc($rs3)) {
    $kennr = trim($row3['code']);
    if ($kennr) {
//        print PHP_EOL . $row3['waarde_english'];
        $from = null;
        $to = null;
        if (preg_match('/^(<|>|less than|fewer than|more than|up to)?\s*[\d\.\,]+(\s*(till|to))?(\s*[\d\.\,]+)?/is', $row3['waarde_english'])) {


            $firstDigitTo = false;
            $range = false;

            if (preg_match('/^(<|less than|fewer than|up to)/is', $row3['waarde_english'])) {
                $from = 0;
                $firstDigitTo = true;
                $range = true;
            } elseif (preg_match('/(till|to)/is', $row3['waarde_english'])) {
                $range = true;
            } elseif (preg_match('/(>|more than|or more)/is', $row3['waarde_english'])) {
                $range = true;
            }

            preg_match_all('/([\d\.\,]+)/is', $row3['waarde_english'], $matches);
            $foundNums = array();
            if (isset($matches[1])) {
                foreach ($matches[1] as $m => $match) {
                    $foundNums[$m] = intval(preg_replace('/\D+/', '', $match));
                }
            }


            if (isset($foundNums[0])) {
                if ($firstDigitTo) {
                    $to = $foundNums[0];
                } else {
                    $from = $foundNums[0];
                }
            }

            if (isset($foundNums[1]) && !$firstDigitTo) {
                $to = $foundNums[1];
            } elseif (!$range) {
                $to = $from;
            }


        }
        $row3['real_from'] = $from;
        $row3['real_to'] = $to;
        foreach($langArray as $lang){
            if(isset($row3['waarde_'.$lang])){
                $row3['real_val_'.$lang] = sanitzeVal($lang, $row3['waarde_'.$lang]);
            }
        }


        $lookupTableKennrs[$kennr] = $row3;
    }
}


$insertQueries = array();
foreach ($lookupTable as $field => $fieldData) {
    foreach ($fieldData['validKeys'] as $pos => $kennr) {
        if (isset($lookupTableKennrs[$kennr])) {
            $insertFieldValues = array();


            $kennrInfo = $lookupTableKennrs[$kennr];
            $insertFieldValues[$field] = $kennr;
            foreach ($langArray as $lang) {
                $str = 'waarde_' . $lang;
                $realkeyField = 'real_key_' . $lang;
                $realvalField = 'real_val_' . $lang;
                $parentVal = null;
                $realval = null;
                $realkey = null;
                if (isset($fieldData[$str])) {
                    $parentVal = $fieldData[$str];
                    $realkey = $fieldData[$realkeyField];
                } else {
                    $parentVal = $fieldData['waarde_english'];
                    $realkey = $fieldData['real_key_english'];
                }

                $childVal = null;
                if (isset($kennrInfo[$str])) {
                    $childVal = $kennrInfo[$str];
                    $realval = $kennrInfo[$realvalField];
                } else {
                    $childVal = $kennrInfo['waarde_english'];
                    $realval = $kennrInfo['real_val_english'];
                }



                $insertFieldValues[$str] = createTag($lang, $parentVal, $childVal, $tagSeparator, $tagsSeparator);
                $insertFieldValues[$realvalField] = $realval;
                $insertFieldValues[$realkeyField] = $realkey;
            }


            $insertFieldValues['real_from'] = $kennrInfo['real_from'];
            $insertFieldValues['real_to'] = $kennrInfo['real_to'];


            $fields = implode(', ', array_keys($insertFieldValues));
            $values = "'" . implode("', '", array_map('mysql_real_escape_string', $insertFieldValues)) . "'";
            $values = str_replace("''", 'NULL', $values);

            $insertQueries[] = "INSERT INTO `$tableName` ($fields) VALUES($values)";

        }
    }
}

foreach ($langArray as $lang) {
    $finalTable['fields']['waarde_' . $lang] = '`waarde_' . $lang . "` VARCHAR(255) NOT NULL DEFAULT ''";
    $finalTable['fields']['real_val_' . $lang] = '`real_val_' . $lang . "` VARCHAR(64)";
    $finalTable['fields']['real_key_' . $lang] = '`real_key_' . $lang . "` VARCHAR(32)";
    $finalTable['index']['real_key_' . $lang] = 'KEY `real_key_' . $lang .'` (`real_key_' . $lang .'`)';
}

$queries = array(
    'DROP TABLE IF EXISTS `' . $tableName . '`;',
    'CREATE TABLE `' . $tableName . '` (' . implode(",\n", $finalTable['fields']) . ",\n" . implode(",\n", $finalTable['index']) . ') ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT=\'Generated by generateKenmerkenTagsTable.php5. Do not change!\';'
);


function createTag($lang, $parent, $value, $sep, $masterSep)
{
    $prefix = 'Location';
    switch ($lang) {
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
        case 'hungarian':
            $prefix = 'Elhelyezkedés';
            break;

    }
    $sepQuoted = '/' . preg_quote($sep, '/') . '/is';
    $masterSepQuoted = '/' . preg_quote($masterSep, '/') . '/is';


    $parent = preg_replace($sepQuoted, '/', ucwords(trim($parent)));
    $value = preg_replace($sepQuoted, '/', ucwords(trim($value)));

    $combined = $prefix . $sep . $parent . $sep . $value;
    $combined = preg_replace($masterSepQuoted, '|', trim($combined));
    return $combined;
}

function sanitzeVal($lang, $val){
//    $val = strtolower($val);
    $findReplace = array();


    switch ($lang) {
        case 'english':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('No of ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('No. of ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' type','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (persons)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Kitchen-/restaurant','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Opening hours ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Opened on ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Manned/Unmanned ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Most important ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('for: ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('for: ','/')).'/is'] = '';
            $findReplace['/^yes$/is'] = '1';
            $findReplace['/^no$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' to ','/')).'/is'] = '-';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' till ','/')).'/is'] = '-';

            break;
        case 'dutch':
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' in m2','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Type ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Soort ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (personen)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(':','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (petrol) on ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Openingstijden ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('(evenement) ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Keuken- /Restaurant','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Geopend op ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Bemand / onbemand ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Belangrijkste ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Aantal ','/')).'/is'] = '';
            $findReplace['/^ja$/is'] = '1';
            $findReplace['/^nee$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' tot ','/')).'/is'] = '-';
            break;
        case 'deutsch':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Anzahl ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Art des ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (Tankstelle)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('-/Zelt','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Geöffnet am ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (Personen)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' und/oder ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Manned/Unmanned ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' für die:','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Öffnungszeiten ','/')).'/is'] = '';
            $findReplace['/^ja$/is'] = '1';
            $findReplace['/^nein$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' bis ','/')).'/is'] = '-';
            break;
        case 'french':
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' salles de seminaires','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Cuisine / ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Heures d\'ouverture ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' pour','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (manifestation)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Nombre d\'','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Nombre de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Ouvert le ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' principal/site secondaire','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Type de ','/')).'/is'] = '';
            $findReplace['/^oui$/is'] = '1';
            $findReplace['/^non$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' á ','/')).'/is'] = '-';
            break;
        case 'spanish':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Tipo de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' en m2','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Puesto de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(':','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('nº de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (acontecimiento)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' para cocinas/restaurantes','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Horas de apertura ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' con/sin personal','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (personas)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Abierto los ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Abastecimiento de ','/')).'/is'] = '';
            $findReplace['/^sí$/is'] = '1';
            $findReplace['/^no$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' a ','/')).'/is'] = '-';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('de ','/')).'/is'] = '';
            break;
        case 'italian':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Tipologia di ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Tipo di ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('/ superficie di vendita','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Principal ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Orari di ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('N°\s*di ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Numero di ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (eventi)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' per:','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' servito/ self service','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Cucina/ restaurant','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('di una stanza (persone)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Aperto il ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Angolo snack/ snack caldi','/')).'/is'] = 'snack';
            $findReplace['/^sì$/is'] = '1';
            $findReplace['/^no$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' a ','/')).'/is'] = '-';
            break;
        case 'czech':
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' / vaří','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Hotel - Počet ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' / haly','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' for:','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('No of ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Otevírací hodiny ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Otevřeno ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Počet ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' type','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Typ ','/')).'/is'] = '';
            $findReplace['/^ano$/is'] = '1';
            $findReplace['/^ne$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' až ','/')).'/is'] = '-';
            break;
        case 'polish':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Godziny otwarcia w ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Liczba ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' do:','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Otwarte w ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Rodzaj ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('kuchni/restauracji ','/')).'/is'] = '';
            $findReplace['/^tak$/is'] = '1';
            $findReplace['/^ni$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' do ','/')).'/is'] = '-';
            break;
        case 'portuguese':
            $findReplace['/'.str_replace(' ','\s+',preg_quote(':','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (pessoas)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('para: ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (evento)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Número de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Nº de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' com pessoal /sem pessoal','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Tipo de ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' a ','/')).'/is'] = '-';
            break;
        case 'hungarian':
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Étkezde ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' száma','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Felügyelt/Automatizált ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' (rendezvény)','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Konyha-/ ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Most important ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' típusa','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('No of ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Nyitott ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Nyitvatartási idő ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' type','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Počet ','/')).'/is'] = '';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Szoba/Hall Befogadókapacitás (fő)','/')).'/is'] = 'Befogadókapacitás';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('Počet ','/')).'/is'] = '';
            $findReplace['/^igen$/is'] = '1';
            $findReplace['/^nem$/is'] = '0';
            $findReplace['/'.str_replace(' ','\s+',preg_quote(' až ','/')).'/is'] = '-';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('-től ','/')).'/is'] = '-';
            $findReplace['/'.str_replace(' ','\s+',preg_quote('-ig','/')).'/is'] = '';
            break;

    }


    $val =  preg_replace(array_keys($findReplace), array_values($findReplace), $val);
    $val = ucwords(trim($val));
    return $val;

}

$queries = $queries + $insertQueries;

foreach ($queries as $q4) {
//    print $q4 . PHP_EOL;
    mysql_query($q4, $conn);
    if(mysql_error($conn)){
        print mysql_error($conn) . PHP_EOL;
    }
}


//print (time() - $start) . ' sec' . PHP_EOL;
//print_r($distinctKennrs);
//print_r($lookupTable);
//print_r($lookupTableKennrs);
//print_r($finalTable);
//print_r($queries);