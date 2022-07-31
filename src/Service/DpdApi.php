<?php

namespace App\Service;

use App\Log;

class DpdApi
{

    const URL_ORDER = URL_DPD_DOMAIN . "services/order2?wsdl";

#TODO Добавить функцию для получения / обновления списка всех городов. Хранить в JSON
#TODO Повесить CRON-задачу

    /**
     * Создание заказа на отправку в DPD
     *
     * @param string $ticketId
     * @param array $arRequest
     *
     * @return string
     */
    public static function createOrder(string $ticketId, array $arRequest): string
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
}