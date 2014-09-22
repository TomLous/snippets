<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2014 Tom Lous
 * @package     package
 * Datetime:     19/09/14 14:35
 */
include('config.inc.php5');

$mysqli = connectToNextDB($mysqli);

$queryNonDigitalHousnumbers = "SELECT  b_huisnr
              FROM eindverbruiker
              WHERE NOT b_huisnr REGEXP '^[0-9]+$'
              ORDER BY klantnr ASC";

// open a resultset, and retrieve all outlets (quickly, so locking won't occur for too long)
$resultSet = $mysqli->query($queryNonDigitalHousnumbers);

$outlets = array();

while ($outletObj = $resultSet->fetch_object()) {

    $outletObj->b_huisnr_clean = cleanHuisnr($outletObj->b_huisnr);

//    if($outletObj->b_huisnr_clean == $outletObj->b_huisnr){
        $outlets[] = $outletObj;
//    }

}

$resultSet->close();


function cleanHuisnr($huisnr){
    $find =array(
        '`(t/o|boven|naast|nabij)`is', // remove aanduiding
        '`(\d+)-?\s*(hs|bg|ong|bis|bov|sout|werf|sous|magazijn|lnks|wink|wkl|huis|waterzijde|achter|keld|I{1,3})`is', // remove verdieping
        '`(Noordzijde|Zuidzijde|Oostzijde|westzijde|noord|oost|zuid|west|no|nw|zo|zw|noo|zui|NZ|ZZ|OZ|WZ)`is', // remove richting
        '`^(0|on|niet|-|x).*$`is', // clean empty
        '`(^\d+)[\s\-]*([a-z]{1,2})(\s|$)`is', // bv 1-a
        '`(\d+(\s*[a-z]+)?)\s*(-|t.m)\s*\d+(\s*[a-z]+)?`is', // bv 42-44,
        '`^(\d+\w*).*$`is', // bv alles achter 1a,


    );

    $replace = array(
        '', // t\o
        '\\1', // verdieping aanduidingen
        '', // remove richting
        '',
        '\\1\\2', // 1-a -> 1a
        '\\1', // 42-44 -> 42
        '\\1',

    );

    $huisnr = preg_replace($find, $replace, $huisnr);

    $huisnr =  trim($huisnr);

    return $huisnr;
}

print_r($outlets);