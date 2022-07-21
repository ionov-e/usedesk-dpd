<?php
include "../bd.php";


$id = preg_replace('/[^0-9]/', '', $_POST['id']);

$select = mysql_query("SELECT * FROM customers WHERE id='$id'");
$select = mysql_fetch_assoc($select);

$uchetki[] = array("0" => 'кл.номер', "1" =>'код');
$uchet = ключ;


foreach ($uchetki as $key => $val) {
    if ($val[0] == $uchet){ $result = $key; break; }
}

$keys = $uchetki[$result][1];

include "setings.php";
include "city.php";
include "../bd.php";
$citys = explode(',' , $select['adres']);
$city = $citys[0];
$region = $citys[1];
$tarif = $select['tarif'];
$sposob = $select['sposob'];
$mesto = explode('#', $select['mesto']);
for ($j = 0; $j <= count($mesto)-1; $j++){
    for ($sk = 0; $sk <=count(explode(';',$mesto[$j]))-1;$sk++){
        $sk = explode(';',$mesto[$j]);
        $width[] = $sk[0];
        $height[] = $sk[1];
        $weight[] = $sk[2];
        $length[] = $sk[3];
        $cou[] = $sk[4];
    }
}
$u = array();
for ($t = 0; $t <= count($width)-1;$t++){
    $widths[$t] = explode(',', $width[$t]);
    $heights[$t] = explode(',', $height[$t]);
    $weights[$t] = explode(',', $weight[$t]);
    $lengths[$t] = explode(',', $length[$t]);

    for ($s=0;$s <= count($widths[$t])-1; $s++){
        $widths[$t][$s] = $widths[$t][$s]/100;
        $heights[$t][$s] = $heights[$t][$s]/100;
        $lengths[$t][$s] = $lengths[$t][$s]/100;
        $weights[$t][$s] = $weights[$t][$s]*$cou[$t];
        $gab[] = round($widths[$t][$s]*$heights[$t][$s]*$lengths[$t][$s],2)*$cou[$t]; //это всё математика это мы находим м3 и умножаем их на количество товаров
    }
    $u[] = $s*$cou[$t];
}

for ($t=0; $t <= count($gab);$t++){
    $gas = $gas+$gab[$t];
    $g = $g+$u[$t];//количество мест
    for ($s=0; $s <= count($weights[$t]); $s++){
        $wei = $wei+$weights[$t][$s];// Вес посылки
    }
}


if ($sposob == 'До терминала'){
    $sp = $sposob;
    $sposob = 'ТТ';
    $terminal = $select['otpravka'];
    $sel = mysql_query("SELECT * FROM terminal WHERE adress='$terminal'"); // запрос в БД для поиска по улице терминала. Получаем код нашего терминала куда мы отправляем
    $sele = mysql_fetch_assoc($sel);
    $terminal = $sele['terminalcode'];
}
else {
    $sposob = 'ТД';
}

//$price = из бд значения вытаскиваем declaredValue;
$findcity = findCity($city); функция поиска ID города


$client = new SoapClient ("http://wstest.dpd.ru/services/order2?wsdl"); //покдлючение к тестовому серверу создания заказа для тестирования функции
$arData = array();
		$arData['auth'] = array(
            'clientNumber' => $uchet,
            'clientKey' => $keys); // данные авторизации

		$arData['header'] = array( //отправтель
            'datePickup' => $date, //дата того когда вашу посылку заберут
            'senderAddress' => array(
                'name' => 'название организации',
                'terminalCode' => 'KLF',
                'countryName' => 'страна',
                'region' => 'област',
                'office' => 'офис',
                'city' => 'город',
                'street' => 'ул',
                'streetAbbr' => 'ул', //сокращенная абривиатура
                'house' => 'дом',
                'contactFio' => 'ФИО отправителя',
                  'contactPhone' => 'телефон'
			),
			'pickupTimePeriod' => '9-18'//время работы отправителя
			);

		$arData['order'] = array(
            'orderNumberInternal' => 68, // ваш личный код (я использую код из таблицы заказов ID)
            'serviceCode' => $tarif, // тариф
            'serviceVariant' => $sposob, // вариант доставки ДД - дверь  дверь
            'cargoNumPack' => $g, //количество мест
            'cargoWeight' => $wei ,// вес посылок
            'cargoVolume' => $gas, // объём посылок
            'cargoValue' => $select['OC'], // ОЦ
            'cargoCategory' => $select["tovar"], // название товара через / таваров
            'receiverAddress' => array( // информация о получателе
                'name' => $select["customer"],
                'countryName' => 'Россия',
                'city' => $city,
                'region' => $region,
                'street' => $st,
                'streetAbbr' => $ul,
                'house' => $home,
                'contactFio' => $select["customer"],
                'contactPhone' => $select["phone1"]
            ),
            'cargoRegistered' => false
        );
		if(isset($kv)){
            $arData['order']['receiverAddress']['flat'] = $kv;	//если задана квартира записываем её
        }
		if ($sposob == 'ТТ'){
            $arData['order']['receiverAddress']['terminalCode'] = $terminal; //если указан способ ТТ то указываем наш терминал который мы искали
        }
		$arData['order']['extraService'][0] = array('esCode' => 'EML', 'param' => array('name' => 'email', 'value' => $select["email"]) );
		$arData['order']['extraService'][1] = array('esCode' => 'НПП', 'param' => array('name' => 'sum_npp', 'value' => $select["cena"]) );
		$arData['order']['extraService'][2] = array('esCode' => 'ОЖД', 'param' => array('name' => 'reason_delay', 'value' => 'СООТ') ); // пример нескольких опций

		$arRequest['orders'] = $arData; // помещаем запрос в orders
		$ret = $client->createOrder($arRequest); //делаем запрос в DPD

$echo = stdToArray($ret); //функция из объекта в массив

if ($echo['return']['errorMessage'][0] == ''){
    print_r ($echo['return']['orderNum'][0]); //выводим номер заказа (созданного)
}
else {
    print_r ($echo['return']['errorMessage'][0]); //выводим ошибки
}
