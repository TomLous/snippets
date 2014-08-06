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


$hoofActiviteitenRs = $dbDefinition->query("SELECT SUBSTR(`code`,1,6) as 'hoofddtype', waarde_english as 'en', waarde_dutch as 'nl', waarde_deutsch as 'de', waarde_french as 'fr', waarde_polish as 'pl', waarde_czech as 'cz', waarde_hungarian as 'hu', waarde_italian as 'it', waarde_portuguese as 'pt', waarde_spanish as 'es' FROM kenmerken_taal WHERE tabel='type'");
$hoofActiviteiten = array();
while($record = $hoofActiviteitenRs->fetch_assoc()){
    $hoofActiviteiten[] = $record;
}

print_r($hoofActiviteiten);