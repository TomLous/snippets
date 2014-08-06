<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     06/08/14 10:33
 */

include('config.inc.php');

// Fill / update eindverbruiker_type_match with unmatched eindevruikers
$db001->query("INSERT INTO eindverbruiker_type_match (klantnr, naam, google_activity )
               SELECT e.klantnr, e.naam, e.google_activity  FROM eindverbruiker e WHERE e.google_activity > '' AND e.hoofdtype =''
               ON DUPLICATE KEY UPDATE naam=e.naam, google_activity=e.google_activity");


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
$hoofdActiviteitenLanguages = array();
while($record = $hoofdActiviteitenRs->fetch_assoc()){
    $hoofdtype = array_shift($record);

    // loop all translations and put them in seperate dataframe
    foreach($record as $key=>$value){
        // convert to ascii to generate soundex, metaphone and finally the levensthein distance
        $ascii =  iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $cleaned = trim(preg_replace('/\s+/', ' ',preg_replace('/[^a-z ]+/is', ' ', strtolower($ascii))));
        $soundex =  soundex($cleaned);
        $metaphone =  metaphone($cleaned);

        $hoofdActiviteitenLanguages[$key][$hoofdtype] = array(
            'hoofdtype' => $hoofdtype,
            'value' => $value,
            'ascii' => $ascii,
            'cleaned' => $cleaned,
            'soundex' => $soundex,
            'metaphone' => $metaphone,
        );
    }
}


echo '<pre>';

print_r($hoofdActiviteitenLanguages);