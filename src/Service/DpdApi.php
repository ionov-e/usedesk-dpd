<?php

namespace App\Service;

use SoapClient;
use App\Log;

class DpdApi
{

    const URL_GEOGRAPHY = URL_DPD_DOMAIN . "services/geography2?wsdl";
    const URL_ORDER = URL_DPD_DOMAIN . "services/order2?wsdl";

#TODO Переделать для сохранения всех городов в один файл.
#TODO log
#TODO Повесить CRON-задачу

    /**
     * @return string
     */
    static function createOrder(): string
    {
        $form = self::getFormData();

        $ticketId = $form['$ticketId']; // Для лога

        $arRequest = self::getDataToSendToCreateOrder($form);

        try { // IDE не подсказывает, но Soap может кидать SoapFault исключения
            $client = new \SoapClient (self::URL_ORDER);
            $responseStd = $client->createOrder($arRequest); //делаем запрос в DPD
        } catch (\SoapFault $e) {
            Log::error(Log::SOAP, "Exception: " . $e->getMessage());
            return 'Произошла ошибка';
        }

// StdClass. Обязательные ключи: orderNumberInternal, status. Необязательные: orderNum, pickupDate, dateFlag, errorMessage

        $return = $responseStd->return;

// Статусы могут быть:
// OK – заказ на доставку успешно создан с номером, указанным в поле orderNum.
// OrderPending – заказ на доставку принят, но нуждается в ручной доработке сотрудником DPD, (например, по причине того, что адрес доставки не распознан автоматически). Номер заказа будет присвоен ему, когда это доработка будет произведена.
// OrderDuplicate – заказ на доставку не может быть принять по причине, указанной в поле errorMessage.
// OrderError – заказ на доставку не может быть создан по причине, указанной в поле errorMessage.
// OrderCancelled – заказ отменен

        if ($return->status == 'OK') {
            Log::info(Log::SOAP,  "Тикет $ticketId: Успешно создан заказ в DPD. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            return "Успешно создано! Ваш ТТН: " . $return->orderNum;
            #TODO внести в JSON файл
        } elseif ($return->status == 'OrderPending') {
            Log::warning(Log::SOAP, "Тикет $ticketId: Получил статус 'OrderPending'. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            return "заказ на доставку принят, но нуждается в ручной доработке сотрудником DPD, (например, по причине " .
                "того, что адрес доставки не распознан автоматически). Номер заказа будет присвоен ему, когда это доработка будет произведена";
            #TODO внести в JSON файл (иначе). На каждый Пост-запрос с этим тикетом отправлять статус-чек
        } else {
            Log::error(Log::SOAP, "Тикет $ticketId: ОШИБКА. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            return $return->errorMessage; //выводим ошибки
        }
    }


//responseArray findCity(196050161);
//responseArray findCity(1960501610420942390); // Неверный айди города

//Пример запроса
//$city = ‘Калуга’;
//$findcity = findCity($city); //так мы запишем номер города из DPD в нашу переменную.

    static function findCity($cityId)
    {
        $client = new SoapClient (self::URL_GEOGRAPHY);

        $arData['auth'] = array(
            'clientNumber' => CLIENT_NUMBER,
            'clientKey' => CLIENT_KEY);
        $arRequest['request'] = $arData; //помещаем наш массив авторизации в массив запроса request.
        $ret = $client->getCitiesCashPay($arRequest); //обращаемся к функции getCitiesCashPay и получаем список городов.

//        $mass = stdToArray($ret); //вызываем эту самую функцию для того чтобы можно было перебрать массив #TODO Не нужна будет
//
//        foreach ($mass as $key => $key1) {
//            foreach ($key1 as $cityid => $city) {
//                if (in_array($cityId, $city)) {
//                    return $city['cityId']; // если мы находим этот город в массиве (который мы искали) - возвращаем
//                }
//            }
//        }

        return 0; // Значит не найден такой город
    }

    private static function getFormData(): array
    {
        #TODO убрать тестовые строки ниже. Получать значения из формы

        $form = [];

        $form['$ticketId'] = '123123121';

        $form['orderNumberInternal'] = '220620-sdfs';
        $form['serviceCode'] = 'PCL';
        $form['cargoNumPack'] = '1';
        $form['cargoWeight'] = '60';
        $form['cargoVolume'] = '5';
        $form['cargoValue'] = '60000';
        $form['cargoCategory'] = 'Товары';

        $form['senderAddress']['name'] = 'Илья Отправитель';
        $form['senderAddress']['datePickup'] = '2022-08-02'; // 2016-07-26
        $form['senderAddress']['pickupTimePeriod'] = '9-18';
        $form['senderAddress']['city'] = 'Люберцы'; // Люберцы // 196050161  ???
        $form['senderAddress']['region'] = 'Московская обл.';
        $form['senderAddress']['street'] = 'Авиаторов';
        $form['senderAddress']['streetAbbr'] = 'ул';
        $form['senderAddress']['house'] = '1';
        $form['senderAddress']['houseKorpus'] = ''; // Корпус, например: А
        $form['senderAddress']['str'] = ''; // Строение, например: 1
        $form['senderAddress']['office'] = ''; // Офис, например: 12Б
        $form['senderAddress']['flat'] = ''; // Номер квартиры, например: 144А
        $form['senderAddress']['contactFio'] = 'Смирнов Игорь Николаевич';
        $form['senderAddress']['contactPhone'] = '89165555555';

        $form['receiverAddress']['name'] = 'ООО "ФИРМЕННЫЕ РЕШЕНИЯ"';
        $form['receiverAddress']['city'] = 'Петро-Славянка';
        $form['receiverAddress']['region'] = 'Санкт-Петербург';
        $form['receiverAddress']['street'] = 'Софийская';
        $form['receiverAddress']['streetAbbr'] = 'ул';
        $form['receiverAddress']['house'] = '118';
        $form['receiverAddress']['contactFio'] = 'Сотрудник склада';
        $form['receiverAddress']['contactPhone'] = '244 68 04';
        $form['receiverAddress']['houseKorpus'] = '5';
        $form['receiverAddress']['str'] = '';
        $form['receiverAddress']['office'] = '';
        $form['receiverAddress']['flat'] = '';

        return $form;
    }

    private static function getDataToSendToCreateOrder(array $form): array
    {
        $arData = array();

        $arData['auth'] = array(
            'clientNumber' => CLIENT_NUMBER,
            'clientKey' => CLIENT_KEY
        ); // данные авторизации

        $arData['header'] = array( //отправитель
            'datePickup' => $form['senderAddress']['datePickup'], //дата того когда вашу посылку заберут
            'pickupTimePeriod' => $form['senderAddress']['pickupTimePeriod'], //время для курьера: 9-18, 9-13, 13-18
            'senderAddress' => array(
                'name' => $form['senderAddress']['name'],
                'countryName' => 'Россия',
                'city' => $form['senderAddress']['city'],
                'region' => $form['senderAddress']['region'],
                'street' => $form['senderAddress']['street'],
                'house' => $form['senderAddress']['house'],
                'contactFio' => $form['senderAddress']['contactFio'],
                'contactPhone' => $form['senderAddress']['contactPhone']
            )
        );

        if (!empty($form['senderAddress']['streetAbbr'])) {
            $arData['header']['senderAddress']['streetAbbr'] = $form['senderAddress']['streetAbbr'];  // Например: ул
        }

        if (!empty($form['senderAddress']['houseKorpus'])) {
            $arData['header']['senderAddress']['houseKorpus'] = $form['senderAddress']['houseKorpus'];  // Корпус, например: А
        }

        if (!empty($form['senderAddress']['str'])) {
            $arData['header']['senderAddress']['str'] = $form['senderAddress']['str'];// Строение, например: 1
        }

        if (!empty($form['senderAddress']['office'])) {
            $arData['header']['senderAddress']['office'] = $form['senderAddress']['office'];// Офис, например: 12Б
        }

        if (!empty($form['senderAddress']['flat'])) {
            $arData['header']['senderAddress']['flat'] = $form['senderAddress']['flat'];// Номер квартиры, например: 144А
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
                'name' => $form['receiverAddress']['name'],
                'countryName' => 'Россия',
                'city' => $form['receiverAddress']['city'],
                'region' => $form['receiverAddress']['region'],
                'street' => $form['receiverAddress']['street'],
                'house' => $form['receiverAddress']['house'],
                'contactFio' => $form['receiverAddress']['contactFio'],
                'contactPhone' => $form['receiverAddress']['contactPhone']
                #TODO спросить про необходимость почты?
            ),
            'cargoRegistered' => false
        );

        if (!empty($form['receiverAddress']['streetAbbr'])) {
            $arData['order']['receiverAddress']['streetAbbr'] = $form['receiverAddress']['streetAbbr']; // Например: ул
        }

        if (!empty($form['receiverAddress']['houseKorpus'])) {
            $arData['order']['receiverAddress']['houseKorpus'] = $form['receiverAddress']['houseKorpus'];  // Корпус, например: А
        }

        if (!empty($form['receiverAddress']['str'])) {
            $arData['order']['receiverAddress']['str'] = $form['receiverAddress']['str'];// Строение, например: 1
        }

        if (!empty($form['receiverAddress']['office'])) {
            $arData['order']['receiverAddress']['office'] = $form['receiverAddress']['office'];// Офис, например: 12Б
        }

        if (!empty($form['receiverAddress']['flat'])) {
            $arData['order']['receiverAddress']['flat'] = $form['receiverAddress']['flat'];// Номер квартиры, например: 144А
        }

//$arData['order']['extraService'][0] = array('esCode' => 'EML', 'param' => array('name' => 'email', 'value' => $select["email"]));
//$arData['order']['extraService'][1] = array('esCode' => 'НПП', 'param' => array('name' => 'sum_npp', 'value' => $select["cena"]));
//$arData['order']['extraService'][2] = array('esCode' => 'ОЖД', 'param' => array('name' => 'reason_delay', 'value' => 'СООТ')); // пример нескольких опций

        $arRequest['orders'] = $arData; // помещаем запрос в orders
        return $arRequest;
    }
}