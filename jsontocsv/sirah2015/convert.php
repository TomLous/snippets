<?php
/**
 * @author      Tom Lous <tomlous@gmail.com>
 * @copyright   2015 Tom Lous
 * @package     package
 * Datetime:     12/02/15 10:23
 */

$PARSE_RECORD = true;


if($PARSE_RECORD){
    $sqlite3conn = new SQLite3('source.db');
    $rs =  $sqlite3conn->query("SELECT `value` FROM ItemTable WHERE key = 'db_eng' OR key='db_fre'");
    $data = $rs->fetchArray();
    $jsonRecord = current($data);
//    $jsonRecord =  iconv("UTF-8", "UTF-8//IGNORE", $jsonRecord);
    $jsonRecord =  utf8_encode($jsonRecord);

    file_put_contents('source.json', $jsonRecord);

    $newJson = "";
    $level = 0;
    $levelCounters = array();
    $filePointers = array();
    $levelParts=array();
    for($i=0; $i<mb_strlen($jsonRecord); $i++){
        $c = mb_substr($jsonRecord, $i, 1);
        if(ord($c) == 0){
            continue;
        }
        //

       /* if($c == '" '|| $c == '"'){
            $c = '"';
        }*/


        if($c == "{" || $c == "["){
            $level++;
            if(!isset($levelCounters[$level])){
                $levelCounters[$level] = 0;
            }
            if($level < 3){
                $filePointers[$level] = fopen('parts/source_'.$level.'_'.$levelCounters[$level].'.json', 'wb');
            }

            $c .= "\n".str_repeat("\t",$level);
        }
        if($c == ","){
            $c .= "\n".str_repeat("\t",$level);

        }

        foreach($filePointers as $fp){
            fwrite($fp, $c);
        }

        if($c == "}" || $c == "]"){
            if(isset($filePointers[$level])){
                fclose($filePointers[$level]);
                unset($filePointers[$level]);
            }
            $levelCounters[$level]++;

            $level--;
            $c = "\n".str_repeat("\t",$level).$c;
        }



        $newJson .= $c;
    }


    file_put_contents('converted.json', $newJson);
}

if ($handle = opendir('parts')) {
    /* This is the correct way to loop over the directory. */
    while (false !== ($entry = readdir($handle)) ) {
        if ($entry != "." && $entry != "..") {
            $jsonData = file_get_contents('parts/'.$entry);
            $json = json_decode($jsonData);

            echo PHP_EOL . $entry . ' ';

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    echo ' - No errors';
                    break;
                case JSON_ERROR_DEPTH:
                    echo ' - Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    echo ' - Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    echo ' - Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    echo ' - Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    echo ' - Unknown error';
                    break;
            }
        }
    }

    closedir($handle);
}






$json = json_decode($jsonRecord, TRUE);


print_r(array_keys($json));

switch (json_last_error()) {
    case JSON_ERROR_NONE:
        echo ' - No errors';
        break;
    case JSON_ERROR_DEPTH:
        echo ' - Maximum stack depth exceeded';
        break;
    case JSON_ERROR_STATE_MISMATCH:
        echo ' - Underflow or the modes mismatch';
        break;
    case JSON_ERROR_CTRL_CHAR:
        echo ' - Unexpected control character found';
        break;
    case JSON_ERROR_SYNTAX:
        echo ' - Syntax error, malformed JSON';
        break;
    case JSON_ERROR_UTF8:
        echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
    default:
        echo ' - Unknown error';
        break;
}

echo PHP_EOL;

$json = json_decode($newJson, TRUE);


print_r(array_keys($json));

switch (json_last_error()) {
    case JSON_ERROR_NONE:
        echo ' - No errors';
        break;
    case JSON_ERROR_DEPTH:
        echo ' - Maximum stack depth exceeded';
        break;
    case JSON_ERROR_STATE_MISMATCH:
        echo ' - Underflow or the modes mismatch';
        break;
    case JSON_ERROR_CTRL_CHAR:
        echo ' - Unexpected control character found';
        break;
    case JSON_ERROR_SYNTAX:
        echo ' - Syntax error, malformed JSON';
        break;
    case JSON_ERROR_UTF8:
        echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
    default:
        echo ' - Unknown error';
        break;
}

echo PHP_EOL;
