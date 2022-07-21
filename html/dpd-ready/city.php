<?php
function findCity($idcity) { //делаем функцию по поиску ключа города в DPD передавая город. (Пример - Калуга)
    include "setings.php";
    $client = new SoapClient ("$server[0]geography2?wsdl");

    $arData['auth'] = array(
        'clientNumber' => $MY_NUMBER,
        'clientKey' => $MY_KEY);
    $arRequest['request'] = $arData; //помещаем наш масив авторизации в масив запроса request.
    $ret = $client->getCitiesCashPay($arRequest); //обращаемся к функции getCitiesCashPay  и получаем список городов.

    function stdToArray($obj){
        $rc = (array)$obj;
        foreach($rc as $key=>$item){
            $rc[$key]= (array)$item;
            foreach($rc[$key] as $keys=>$items){
                $rc[$key][$keys]= (array)$items;
            }
        }
        return $rc;
    }
//функция отвечает за преобразования объекта в масив


    $mass = stdToArray($ret); //вызываем эту самую функцию для того чтобы можно было перебрать масив

    foreach($mass AS $key => $key1){
        foreach($key1 AS $cityid => $city){
            if (in_array($idcity,$city)) {$id = $city['cityId']; return $id;}// если мы находим этот город в масиве (который мы искали) мы заносим его в переменную $ID и возвращаем наш ответ.
        }
    }
}

//Пример запроса
//$city = ‘Калуга’;
//$findcity = findCity($city); //так мы запишем номер города из DPD в нашу переменную.