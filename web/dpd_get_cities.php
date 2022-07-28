<?php
require_once "../common.php"; // Файл с константами и общими функциями

#TODO Переделать для сохранения всех городов в один файл.
#TODO log
#TODO Повесить CRON-задачу


//responseArray findCity(196050161);
//responseArray findCity(1960501610420942390); // Неверный айди города

//Пример запроса
//$city = ‘Калуга’;
//$findcity = findCity($city); //так мы запишем номер города из DPD в нашу переменную.

function findCity($cityId)
{
    $client = new SoapClient (URL_GEOGRAPHY);

    $arData['auth'] = array(
        'clientNumber' => CLIENT_NUMBER,
        'clientKey' => CLIENT_KEY);
    $arRequest['request'] = $arData; //помещаем наш массив авторизации в массив запроса request.
    $ret = $client->getCitiesCashPay($arRequest); //обращаемся к функции getCitiesCashPay и получаем список городов.

    $mass = stdToArray($ret); //вызываем эту самую функцию для того чтобы можно было перебрать массив

    foreach ($mass as $key => $key1) {
        foreach ($key1 as $cityid => $city) {
            if (in_array($cityId, $city)) {
                return $city['cityId']; // если мы находим этот город в массиве (который мы искали) - возвращаем
            }
        }
    }

    return 0; // Значит не найден такой город
}