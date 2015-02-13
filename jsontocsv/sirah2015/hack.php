<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     12/02/15 16:37
 */

function getData($data, $findStart, $findEnd, &$offset){
    $findLen = strlen($findStart);


    $startSub = strpos($data,$findStart, $offset);

    $endSub = strpos($data,$findEnd, $startSub + $findLen);

    if(!$startSub || !$endSub){
        return false;
    }

    $substr = substr($data, $startSub+ $findLen, $endSub-$startSub-$findLen);
    $offset = $endSub + strlen($findEnd);
    return $substr;
}

function csvFormat($str){

    $str = str_replace("\n","",str_replace(",\n",";",str_replace("\t","", $str))) . "\n";
    return $str;

}

$data = file_get_contents('parts/source_2_4.json');
$offset = 0;
$headerStr = csvFormat(getData($data, '"order":[', "],\n", $offset));

$offset = strpos($data, '"data":{', $offset);

print $offset;

$count = 0;

$outp = "";
$outp .= $headerStr;
while($str=getData($data, ":[\n",']',$offset)){
//    if($count > 15){
//        break;
//    }
//    print_r(csvFormat($str));
    $outp .= csvFormat($str);
    $count++;
}

file_put_contents('comps.csv',$outp);



