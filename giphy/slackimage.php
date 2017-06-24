<?php
error_reporting(0);
$tag = urlencode($_REQUEST['tag']);
$data = json_decode(file_get_contents("https://api.giphy.com/v1/gifs/random?api_key=727244774dce47e0ad80d39847d8ac27&tag=$tag&rating=R&fmt=json"));
header("Location: ".$data->data->image_original_url);