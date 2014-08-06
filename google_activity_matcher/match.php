<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     06/08/14 10:33
 */

include('config.inc.php');
//$db001->query("TRUNCATE TABLE eindverbruiker_type_match");
// Fill / update eindverbruiker_type_match with unmatched eindevruikers
$db001->query("INSERT INTO eindverbruiker_type_match (klantnr, naam, google_activity )
               SELECT e.klantnr, e.naam, e.google_activity  FROM eindverbruiker e WHERE e.google_activity > '' AND e.hoofdtype =''
               ON DUPLICATE KEY UPDATE naam=e.naam, google_activity=e.google_activity");


// searchTables
$asciiRecords = array();
$cleanedRecords = array();
$soundexRecords = array();
$metaphoneRecords = array();

$hoofdTypes = array();

// Load all possible hoofdactiviteiten in array
$hoofdActiviteitenRs = $dbDefinition->query("SELECT
                                                SUBSTR(`code`,1,6) as 'hoofddtype',
                                                CONVERT(waarde_english USING utf8) as 'en',
                                                CONVERT(waarde_dutch USING utf8) as 'nl',
                                                CONVERT(waarde_deutsch USING utf8) as 'de',
                                                CONVERT(waarde_french USING utf8) as 'fr',
                                                CONVERT(waarde_polish USING utf8) as 'pl',
                                                CONVERT(waarde_czech USING utf8) as 'cz',
                                                CONVERT(waarde_hungarian USING utf8) as 'hu',
                                                CONVERT(waarde_italian USING utf8) as 'it',
                                                CONVERT(waarde_portuguese USING utf8) as 'pt',
                                                CONVERT(waarde_spanish USING utf8) as 'es'
                                              FROM kenmerken_taal
                                              WHERE tabel='type'");
//$hoofdActiviteitenLanguages = array();
while ($record = $hoofdActiviteitenRs->fetch_assoc()) {
    $hoofdtype = array_shift($record);

    $hoofdTypes[$hoofdtype] = $record;

    // loop all translations and put them in seperate dataframe
    foreach ($record as $key => $value) {
        // convert to ascii to generate soundex, metaphone and finally the levensthein distance
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $cleaned = trim(preg_replace('/\s+/', '', preg_replace('/[^a-z ]+/is', ' ', strtolower($ascii))));
        $soundex = soundex($cleaned);
        $metaphone = metaphone($cleaned);

        $asciiRecords[$ascii][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $key, 'source' => 'kenmerken_taal', 'type' => 'ascii');
        $cleanedRecords[$cleaned][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $key, 'source' => 'kenmerken_taal', 'type' => 'cleaned');
        $soundexRecords[$soundex][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $key, 'source' => 'kenmerken_taal', 'type' => 'soundex');
        $metaphoneRecords[$metaphone][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $key, 'source' => 'kenmerken_taal', 'type' => 'metaphone');

//        $hoofdActiviteitenLanguages[$key][] = array(
//            'hoofdtype' => $hoofdtype,
//            'value' => $value,
//            'ascii' => $ascii,
//            'cleaned' => $cleaned,
//            'soundex' => $soundex,
//            'metaphone' => $metaphone,
//        );
    }
}

// load all predefined google activities (only DE, GB, IT, PL, PT) so far
$mappedActivitiesRs = $dbMaster->query("SELECT
                                            (CASE country WHEN 'GB' THEN 'en' ELSE LCASE(country) END) as `language`,
                                            activity as google_activity,
                                            hoofdtype
                                        FROM activity_category
                                        WHERE source='Google'");
//$mappedActivitiesLanguages = array();
while ($record = $mappedActivitiesRs->fetch_assoc()) {
    $hoofdtype = $record['hoofdtype'];
    $language = $record['language'];
    $value = $record['google_activity'];
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $cleaned = trim(preg_replace('/\s+/', '', preg_replace('/[^a-z ]+/is', ' ', strtolower($ascii))));
    $soundex = soundex($cleaned);
    $metaphone = metaphone($cleaned);

    $asciiRecords[$ascii][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $language, 'source' => 'activity_category', 'type' => 'ascii');
    $cleanedRecords[$cleaned][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $language, 'source' => 'activity_category', 'type' => 'cleaned');
    $soundexRecords[$soundex][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $language, 'source' => 'activity_category', 'type' => 'soundex');
    $metaphoneRecords[$metaphone][] = array('hoofdtype' => $hoofdtype, 'value' => $value, 'language' => $language, 'source' => 'activity_category', 'type' => 'metaphone');

//    $mappedActivitiesLanguages[$language][] = array(
//        'hoofdtype' => $hoofdtype,
//        'value' => $value,
//        'ascii' => $ascii,
//        'cleaned' => $cleaned,
//        'soundex' => $soundex,
//        'metaphone' => $metaphone,
//    );
}



// load all unmatched google_activities

$unmatchedActivitiesRs = $db001->query("SELECT DISTINCT(google_activity) as google_activity
                                        FROM eindverbruiker_type_match
                                        WHERE `status` = 0
                                        LIMIT 0,100");
$matchedActivities = array();
while ($record = $unmatchedActivitiesRs->fetch_assoc()) {
    $value = current($record);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $cleaned = trim(preg_replace('/\s+/', '', preg_replace('/[^a-z ]+/is', ' ', strtolower($ascii))));
    $soundex = soundex($cleaned);
    $metaphone = metaphone($cleaned);

    $matches = array(
        'hoofdtype' => null,
        'value' => $value,
        'ascii' => $ascii,
        'cleaned' => $cleaned,
        'soundex' => $soundex,
        'metaphone' => $metaphone,
        'hoofdtypes' => array(),
        'matches' => array(
            'ascii' => null,
            'cleaned' => null,
            'soundex' => null,
            'metaphone' => null,

        ),

    );

    $matched = false;

    if (isset($asciiRecords[$ascii])) {
        foreach ($asciiRecords[$ascii] as $record) {
            $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
        }
        $matched = true;
    }

    if (isset($cleanedRecords[$cleaned])) {
//        $matches['matches']['cleaned'] = $cleanedRecords[$cleaned];
        foreach ($cleanedRecords[$cleaned] as $record) {
            $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
        }
        $matched = true;
    }

    if (isset($soundexRecords[$soundex])) {
//        $matches['matches']['soundex'] = $soundexRecords[$soundex];
        foreach ($soundexRecords[$soundex] as $record) {
            $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
        }
        $matched = true;
    }

    if (isset($metaphoneRecords[$metaphone])) {
//        $matches['matches']['metaphone'] = $metaphoneRecords[$metaphone];
        foreach ($metaphoneRecords[$metaphone] as $record) {
            $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
        }
        $matched = true;
    }

    foreach ($cleanedRecords as $cleanedKey => $records) {
        $levenstheinDistance = levenshtein($cleaned, $cleanedKey);
        $levenstheinMatchPercentage = 100 - (($levenstheinDistance / strlen($cleaned)) * 100);

        if ($levenstheinMatchPercentage > 70) {
            $matched = true;
            foreach ($records as $record) {
                $record['type'] = 'levensthein';
                $record['levenstheinDistance'] = $levenstheinDistance;
                $record['levenstheinMatchPercentage'] = $levenstheinMatchPercentage;
                $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
            }
        }

        $percentage = 0;
        similar_text($cleaned, $cleanedKey, $percentage);
        if ($percentage > 70) {
            $matched = true;
            foreach ($records as $record) {
                $record['type'] = 'similar_text';
                $record['similarTextMatchPercentage'] = $percentage;
                $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
            }
        }
    }

    foreach ($asciiRecords as $asciiKey => $records) {
        $parts = preg_split('/\W+/is',trim(strtolower($asciiKey)));



        $matchCount = 0;
        $maxParts = count($parts);
        $maxParts = $maxParts>1?$maxParts-1:$maxParts;

        foreach($parts as $part){
            if(strlen($part)>0 && strpos($cleaned, $part) !== false){
                $matchCount++;
            }
        }

        $percentage = $matchCount/$maxParts;
        if ($percentage > 30) {
            $matched = true;
            foreach ($records as $record) {
                $record['type'] = 'parts';
                $record['partsMatchPercentage'] = $percentage;
                $matches['hoofdtypes'][$record['hoofdtype']][] = $record;
            }

        }

    }

    if ($matched) {
        $hoofdtypeScores = array();
        $hoofdtypeCertainty = array();
        foreach ($matches['hoofdtypes'] as $_hoofdtype => $records) {
            $hoofdtypeScores[$_hoofdtype] = 0;
            $hoofdtypeCertainty[$_hoofdtype] = 0;

            foreach ($records as $record) {
                $maxScore = 500+10+5;
                $score = 5;
                switch ($record['type']) {
                    case 'ascii':
                        $score *= 100;
                        break;
                    case 'cleaned':
                        $score *= 90;
                        break;
                    case 'soundex':
                        $score *= 20;
                        break;
                    case 'metaphone':
                        $score *= 50;
                        break;
                    case 'levensthein':
                        $score *= $record['levenstheinMatchPercentage'];
                        break;
                    case 'similar_text':
                        $score *= $record['similarTextMatchPercentage'];
                        break;
                    case 'parts':
                        $score *= $record['partsMatchPercentage'];
                        break;

                }

                switch ($record['source']) {
                    case 'kenmerken_taal':
                        $score += 10;
                        break;
                    case 'activity_category':
                        $score += 5;
                        break;
                }

                switch ($record['language']) {
                    case CURRENT_LANGUAGE:
                        $score += 5;
                        break;
                    case 'en':
                        $score += 2;
                        break;
                }

                $hoofdtypeScores[$_hoofdtype] = max($hoofdtypeScores[$_hoofdtype], $score);
                $certainty = $score / $maxScore;
                $hoofdtypeCertainty[$_hoofdtype] = max($certainty,$hoofdtypeCertainty[$_hoofdtype]);
                if(max($hoofdtypeCertainty) == $certainty){
                    $matches['hoofdtypeMatchReason'] = $record;
                }

            }

            arsort($hoofdtypeScores);
            $matches['hoofdtypeScores'] = $hoofdtypeScores;
            arsort($hoofdtypeCertainty);
            $matches['hoofdtypeCertainty'] = $hoofdtypeCertainty;
        }

//        $matches['hoofdtypes'] = null;

        if(current($matches['hoofdtypeCertainty'])>0.5){
            $matches['hoofdtype'] = key($matches['hoofdtypeCertainty']);
            $matches['hoofdtypeRecord'] = $hoofdTypes[$matches['hoofdtype']];
        }


        $matchedActivities[] = $matches;
    }

}
$query = "UPDATE eindverbruiker_type_match SET hoofdtype=?, hoofdtype_name_en=?, kenmerk_match_info=?, kenmerk_match_zekerheid=?, status=? WHERE google_activity=?";
$statement = $db001->prepare($query);
$_ht = '';
$_htn = '';
$_km = '';
$_kmz = 0;
$_status = 1;
$_ga = '';

$statement->bind_param('sssdds', $_ht, $_htn, $_km, $_kmz, $_status, $_ga);

foreach ($matchedActivities as $matchedActivity) {
    if(isset($matchedActivity['hoofdtype'])){
        $_ht = $matchedActivity['hoofdtype'];
        $_htn =  $matchedActivity['hoofdtypeRecord']['en'];
        $_km = $matchedActivity['hoofdtypeMatchReason']['value'] .' ['.$matchedActivity['hoofdtypeMatchReason']['language'].'] '.$matchedActivity['hoofdtypeMatchReason']['type'] .' ('.$matchedActivity['hoofdtypeMatchReason']['source'].')';
        $_kmz = current($matchedActivity['hoofdtypeCertainty'])*100;
        $_status = 2;
        $_ga = $matchedActivity['value'];

        $statement->execute();

    }else{
        $_ht = '';
        $_htn = '';
        $_km = null;
        $_kmz = 0;
        $_status = 1;
        $_ga = $matchedActivity['value'];

        $statement->execute();
    }

}


echo '<pre>';

print_r($matchedActivities);