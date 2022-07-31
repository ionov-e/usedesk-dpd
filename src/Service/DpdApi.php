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
     *
     *
     * @param string $ticketId
     * @param array $form
     *
     * @return string
     */
    static function createOrder(string $ticketId, array $arRequest): string
    {

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
}