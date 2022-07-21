<?php
include "setings.php";
include "city.php";

$city = 'Калуга';
$findcity = findCity($city);
$sposob =Способ;
//Массив tovars $a[] = array(0 =>’тут id’, 1=>’тут количество этого товара’); и так можно дублировать до скольких вам нужно. Или же использовать отправление с помощью AJAX
$tovars = $_POST[tovars]; //принимаем масив товаров
$spec = $_POST[tovars];
for ($g=0; $g <= count($tovars)-1; $g++){ //перебираем масив(можно через foreach)
    $all[] = $tovars[$g][0];//id товара
    $cout[] = $tovars[$g][1];//количество товаров
}


sort($cout);// сортируем количество
$tovar = array_unique($all);//удаляем тот товар который повторяеться
$tovar = implode(",", $tovar); // записываем товары через ‘,’

$mysql_query = mysql_query("SELECT * FROM items WHERE id IN ($tovar)"); //таблица items имеет структуру id(тот который мы искали),name(название товара),mesto(количество мест),width,height,weight,length,price
$mysql_array = mysql_fetch_assoc($mysql_query);


$client = new SoapClient ("$server[0]calculator2?wsdl"); //создаем подключение soap
$arData = array(
    'delivery' => array(			// город доставки
        'cityId' => $findcity, //id города
        'cityName' => $city, //сам город
    ),
);		$arData['auth'] = array(
    'clientNumber' => $uchet,
    'clientKey' => $keys); //данные авторизации
if ($sposob == 'home'){ //если отправляем до дома то ставим значение false
    $arData['selfDelivery'] = false;// Доставка ДО дома
}
else { // если же мы хотим отправить до терминала то true
    $arData['selfDelivery'] = true;// Доставка ДО терминала
}
$arData['pickup'] = array(
    'cityId' => 195733465,
    'cityName' => 'Калуга',
); // где забирают товар

// что делать с терминалом
$arData['selfPickup'] = true;// Доставка ОТ терминала // если вы сами довозите до терминала то true если вы отдаёте от двери то false
$i=0;
do { //перебираем массив запроса в БД
    if ($mysql_array['mesto'] > 1 ){ //если мест больше чем 1
        $ves = explode(",", $mysql_array["weight"]); //в бд всё храниться в одном столбике но через ‘,’ для этого используем команду explode(где указываем что у нас стоит ‘,');
        $length = explode(",", $mysql_array["length"]);
        $width = explode(",", $mysql_array["width"]);
        $height = explode(",", $mysql_array["height"]);
    }
    else {
        $ves[] = $mysql_array["weight"];
        $length[] = $mysql_array["length"];
        $width[] = $mysql_array["width"];
        $height[] = $mysql_array["height"]; //если у нас место 1 то мы просто заносим в массив
    }
    for ($s = 0; $s <= $mysql_array['mesto']-1; $s++){ //создаем цикл помещаем в масив parcel информацию о товарах
        $arData['parcel'][] = array('weight' => $ves[$s], 'length' => $length[$s], 'width' => $width[$s], 'height' => $height[$s] , 'quantity' => $cout[$i]);
    }
    $i++;
    $cena[] = $mysql_array['price']; // указываем цену за товар из БД

}while($mysql_array = mysql_fetch_assoc($mysql_query)); //повторяем тело цикла

for ($c=0; $c <= count($cena); $c++){
    $a = $a + ($cena[$c] * $cout[$c]);
} //--- сумируем цену и умножаем на количество
		$arData['declaredValue'] = $a; //Объявленная ценность (итоговая)
		$arRequest['request'] = $arData; // помещаем в массив запроса
		$ret = $client->getServiceCostByParcels2($arRequest); //делаем сам запрос

$echo = stdToArray($ret); // функция из объекта в массив (в 1 пункте она есть).
$all = array();
for ($j = 0; $j <= count($echo['return'])-1; $j++){
    $all[] = array('serviceName' => $echo['return'][$j]['serviceName'],'cost' => $echo['return'][$j]['cost'],'tarif' => $echo['return'][$j]['serviceCode']);
} //помещаем в массив all – указывает название тарифа, код тарифа, стоимость.

echo json_encode($all); //--- выводим для JS в json формате.
