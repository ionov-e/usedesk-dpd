<?php
$dataArrays = json_decode(file_get_contents("bd.json"), true);

foreach ($dataArrays as $key => $ticketArray) {
    $dataArrays[$key] = [$dataArrays[$key]];
}

file_put_contents("bd-o2.json", json_encode($dataArrays, JSON_UNESCAPED_UNICODE));