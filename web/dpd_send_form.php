<?php
require_once "../common.php"; // Файл с константами и общими функциями
/** @var string $ticketId Получаемый айди тикета из UseDesk*/

logMsg("Тикет $ticketId: создаем запрос на создание заказа в DPD");

$senderInfo['name'] = 'Илья Отправитель';
$senderInfo['datePickup'] = '2022-07-29'; // 2016-07-26
$senderInfo['pickupTimePeriod'] = '9-18';
$senderInfo['city'] = 'Люберцы'; // Люберцы // 196050161  ???
$senderInfo['region'] = 'Московская обл.';
$senderInfo['street'] = 'Авиаторов';
$senderInfo['streetAbbr'] = 'ул';
$senderInfo['house'] = '1';
//$senderInfo['houseKorpus'] = ''; // Корпус, например: А
//$senderInfo['str'] = ''; // Строение, например: 1
//$senderInfo['office'] = ''; // Офис, например: 12Б
//$senderInfo['flat'] = ''; // Номер квартиры, например: 144А
$senderInfo['contactFio'] = 'Смирнов Игорь Николаевич';
$senderInfo['contactPhone'] = '89165555555';


$form['orderNumberInternal'] = '220620-1853372';
$form['serviceCode'] = 'PCL';
$form['cargoNumPack'] = '1';
$form['cargoWeight'] = '60';
$form['cargoVolume'] = '5';
$form['cargoValue'] = '60000';
$form['cargoCategory'] = 'Товары';

$form['name'] = 'ООО "ФИРМЕННЫЕ РЕШЕНИЯ"';
$form['city'] = 'Петро-Славянка';
$form['region'] = 'Санкт-Петербург';
$form['street'] = 'Софийская';
$form['streetAbbr'] = 'ул';
$form['house'] = '118';
$form['contactFio'] = 'Сотрудник склада';
$form['contactPhone'] = '244 68 04';
$form['houseKorpus'] = '5';
//$form['str'] = '';
//$form['office'] = '';
//$form['flat'] = '';



$arData = array();

$arData['auth'] = array(
    'clientNumber' => CLIENT_NUMBER,
    'clientKey' => CLIENT_KEY
); // данные авторизации

$arData['header'] = array( //отправитель
    'datePickup' => $senderInfo['datePickup'], //дата того когда вашу посылку заберут
    'pickupTimePeriod' => $senderInfo['pickupTimePeriod'], //время для курьера: 9-18, 9-13, 13-18
    'senderAddress' => array(
        'name' => $senderInfo['name'],
        'countryName' => 'Россия',
        'city' => $senderInfo['city'],
        'region' => $senderInfo['region'],
        'street' => $senderInfo['street'],
        'house' => $senderInfo['house'],
        'contactFio' => $senderInfo['contactFio'],
        'contactPhone' => $senderInfo['contactPhone']
    )
);

if (isset($senderInfo['streetAbbr'])) {
    $arData['header']['senderAddress']['streetAbbr'] = $senderInfo['streetAbbr'];  // Например: ул
}

if (isset($senderInfo['houseKorpus'])) {
    $arData['header']['senderAddress']['houseKorpus'] = $senderInfo['houseKorpus'];  // Корпус, например: А
}

if (isset($senderInfo['str'])) {
    $arData['header']['senderAddress']['str'] = $senderInfo['str'];// Строение, например: 1
}

if (isset($senderInfo['office'])) {
    $arData['header']['senderAddress']['office'] = $senderInfo['office'];// Офис, например: 12Б
}

if (isset($senderInfo['flat'])) {
    $arData['header']['senderAddress']['flat'] = $senderInfo['flat'];// Номер квартиры, например: 144А
}


$arData['order'] = array(
    'orderNumberInternal' => $form['orderNumberInternal'], // ваш личный код (я использую код из таблицы заказов ID)
    'serviceCode' => $form['serviceCode'], // тариф. 3-ех буквенный. PCL - то что нужно (DPD OPTIMUM)
    'serviceVariant' => 'ДД', // вариант доставки ДД - дверь-дверь
    'cargoNumPack' => $form['cargoNumPack'], //количество мест
    'cargoWeight' => $form['cargoWeight'],// вес посылок Пример: 0.05 (после точки не более 2-х знаков)
    'cargoVolume' => $form['cargoVolume'], // объём посылок
    'cargoValue' => $form['cargoValue'], // оценочная стоимость
    'cargoCategory' => $form['cargoCategory'], // Пример: Одежда
    'receiverAddress' => array(
        'name' => $form['name'],
        'countryName' => 'Россия',
        'city' => $form['city'],
        'region' => $form['region'],
        'street' => $form['street'],
        'house' => $form['house'],
        'contactFio' => $form['contactFio'],
        'contactPhone' => $form['contactPhone']
        #TODO спросить про необходимость почты?
    ),
    'cargoRegistered' => false
);

if (isset($form['streetAbbr'])) {
    $arData['order']['receiverAddress']['streetAbbr'] = $form['streetAbbr']; // Например: ул
}

if (isset($form['houseKorpus'])) {
    $arData['order']['receiverAddress']['houseKorpus'] = $form['houseKorpus'];  // Корпус, например: А
}

if (isset($form['str'])) {
    $arData['order']['receiverAddress']['str'] = $form['str'];// Строение, например: 1
}

if (isset($form['office'])) {
    $arData['order']['receiverAddress']['office'] = $form['office'];// Офис, например: 12Б
}

if (isset($form['flat'])) {
    $arData['order']['receiverAddress']['flat'] = $form['flat'];// Номер квартиры, например: 144А
}

//$arData['order']['extraService'][0] = array('esCode' => 'EML', 'param' => array('name' => 'email', 'value' => $select["email"]));
//$arData['order']['extraService'][1] = array('esCode' => 'НПП', 'param' => array('name' => 'sum_npp', 'value' => $select["cena"]));
//$arData['order']['extraService'][2] = array('esCode' => 'ОЖД', 'param' => array('name' => 'reason_delay', 'value' => 'СООТ')); // пример нескольких опций

$arRequest['orders'] = $arData; // помещаем запрос в orders

$client = new SoapClient (URL_ORDER); #TODO try catch?
$responseStd = $client->createOrder($arRequest); //делаем запрос в DPD


$return = $responseStd['return'];

$responseArray = stdToArray($responseStd); //функция из объекта в массив #TODO избавиться
//$responseArray = $responseStd;


if ($responseArray['return']['errorMessage'][0] == '') { #TODO Status использовать
    logMsg("Тикет $ticketId: Успешно создали заказ в DPD. Ответ: " . json_encode($responseArray, JSON_UNESCAPED_UNICODE));
    echo "Успешно создано! Ваш ТТН: " . $responseArray['return']['orderNum'][0];
    #TODO внести в JSON файл
} else {
    $responseStatus = $responseArray['return']['status'][0];
    $responseErrorMsg = $responseArray['return']['errorMessage'][0];
    logMsg("Тикет $ticketId: ОШИБКА. Ответ: " . json_encode($responseArray, JSON_UNESCAPED_UNICODE));
    print_r($responseArray['return']['errorMessage'][0]); //выводим ошибки
}
#TODO вести отдельный JSON файл с тикетами и ттн для внутренней доработки ТК. На каждый Пост-запрос отправлять статус-чек
