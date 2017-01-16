<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2017 Tom Lous
 * @package     package
 * Datetime:     09/01/17 09:56
 */
date_default_timezone_set("Europe/Amsterdam");

$token = "BEE9Q5A7Q98Q";
$lookup = "https://datlinq.kanbantool.com/api/v1/time_trackers.json?api_token=$token&active=1";


$json =json_decode( file_get_contents($lookup));


if(is_array($json) && count($json) > 0){

    $timerId = $json[0]->time_tracker->id;

    $put = "https://datlinq.kanbantool.com/api/v1/time_trackers/$timerId.json?api_token=$token";


    $data = array("time_tracker[ended_at]" => date("c"));
    $ch = curl_init($put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));

    $response = curl_exec($ch);

    if (!$response)
    {
        print -1;
    }else{
        print $response;
    }


}else{
    print 0;
}

