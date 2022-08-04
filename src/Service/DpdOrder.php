<?php

/**
 *  Класс для создания ТТН в DPD
 */

namespace App\Service;

use App\DB;
use App\Log;

class DpdOrder
{

    const URL_ORDER = URL_DPD_DOMAIN . "services/order2?wsdl";

    /**
     * Создание заказа на отправку в DPD
     *
     * @return string
     * @throws \Exception
     */
    public static function createOrder(): string
    {
        $form = self::getFormData();

        $ticketId = $form[TICKET_ID_KEY_NAME];

        $arRequest = self::getDataToSendToCreateOrder($form);

        // IDE не подсказывает, но Soap может кидать SoapFault исключения
        $client = new \SoapClient (self::URL_ORDER);
        $responseStd = $client->createOrder($arRequest); //делаем запрос в DPD

// StdClass. Обязательные ключи: orderNumberInternal, status. Необязательные: orderNum, pickupDate, dateFlag, errorMessage

        $return = $responseStd->return;

// Статусы могут быть:
// OK – заказ на доставку успешно создан с номером, указанным в поле orderNum.
// OrderPending – заказ на доставку принят, но нуждается в ручной доработке сотрудником DPD, (например, по причине того, что адрес доставки не распознан автоматически). Номер заказа будет присвоен ему, когда это доработка будет произведена.
// OrderDuplicate – заказ на доставку не может быть принять по причине, указанной в поле errorMessage.
// OrderError – заказ на доставку не может быть создан по причине, указанной в поле errorMessage.
// OrderCancelled – заказ отменен

        if ($return->status == 'OK') {
            Log::info(Log::DPD_ORDER, "Тикет $ticketId: Успешно создан заказ в DPD. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            DB::saveToBD($ticketId, $return->orderNum, $return->status);
            return "Успешно создано! Ваш ТТН: " . $return->orderNum;
        } elseif ($return->status == 'OrderPending') {
            Log::warning(Log::DPD_ORDER, "Тикет $ticketId: Получил статус 'OrderPending'. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            DB::saveToBD($ticketId, $return->orderNumberInternal, $return->status); // Вписываем не orderNum, а orderNumberInternal
            return "заказ на доставку принят, но нуждается в ручной доработке сотрудником DPD, (например, по причине " .
                "того, что адрес доставки не распознан автоматически). Номер заказа будет присвоен ему, когда это доработка будет произведена";
        } else {
            Log::error(Log::DPD_ORDER, "Тикет $ticketId: ОШИБКА. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            return $return->errorMessage; //выводим ошибки
        }
    }


    /**
     * Возвращает массив с данными из формы
     *
     * @return array
     */
    private static function getFormData(): array
    {
        Log::info(Log::DPD_ORDER, "Получили из формы: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));

        $form = $_POST;


        // Начнем с необязательного поля. Если пришло пустое - может и нужно оставить пустым. Поэтому проверяем вместе с № дома
        if (empty($form['receiverAddress']['houseKorpus']) && empty($form['receiverAddress']['house'])) {
            $form['receiverAddress']['houseKorpus'] = '5';
        }

        $form['receiverAddress']['name'] = $form['receiverAddress']['name'] ?: 'ООО "ФИРМЕННЫЕ РЕШЕНИЯ"';
        $form['receiverAddress']['contactFio'] = $form['receiverAddress']['contactFio'] ?: 'Сотрудник склада';
        $form['receiverAddress']['contactPhone'] = $form['receiverAddress']['contactPhone'] ?: '244 68 04';
        $form['receiverAddress']['city'] = $form['receiverAddress']['city'] ?: 'Петро-Славянка';
        $form['receiverAddress']['region'] = $form['receiverAddress']['region'] ?: 'Санкт-Петербург';
        $form['receiverAddress']['street'] = $form['receiverAddress']['street'] ?: 'Софийская';
        $form['receiverAddress']['streetAbbr'] = $form['receiverAddress']['streetAbbr'] ?: 'ул';
        $form['receiverAddress']['house'] = $form['receiverAddress']['house'] ?: '118';

        $form['cargoNumPack'] = $form['cargoCategory'] ?: '1';
        $form['cargoCategory'] = $form['cargoCategory'] ?: 'Товары';

        // Незаполненные необязательные поля будут содержать null. Избавимся от них
        foreach ($form as $element) {
            if (empty($element)) {
                unset($element);
            }
        }

        #TODO проверки сюда нужно на данные

//        $form = [];
//
//        $form['$ticketId'] = '123123121';
//
//        $form['orderNumberInternal'] = '220620-sdfs';
//        $form['serviceCode'] = 'PCL';
//        $form['cargoNumPack'] = '1';
//        $form['cargoWeight'] = '60';
//        $form['cargoVolume'] = '5';
//        $form['cargoValue'] = '60000';
//        $form['cargoCategory'] = 'Товары';
//
//        $form['senderAddress']['name'] = 'Илья Отправитель';
//        $form['senderAddress']['datePickup'] = '2022-08-02'; // 2016-07-26
//        $form['senderAddress']['pickupTimePeriod'] = '9-18';
//        $form['senderAddress']['city'] = 'Люберцы'; // Люберцы // 196050161  ???
//        $form['senderAddress']['region'] = 'Московская обл.';
//        $form['senderAddress']['street'] = 'Авиаторов';
//        $form['senderAddress']['streetAbbr'] = 'ул';
//        $form['senderAddress']['house'] = '1';
//        $form['senderAddress']['houseKorpus'] = ''; // Корпус, например: А
//        $form['senderAddress']['str'] = ''; // Строение, например: 1
//        $form['senderAddress']['office'] = ''; // Офис, например: 12Б
//        $form['senderAddress']['flat'] = ''; // Номер квартиры, например: 144А
//        $form['senderAddress']['contactFio'] = 'Смирнов Игорь Николаевич';
//        $form['senderAddress']['contactPhone'] = '89165555555';
//
//        $form['receiverAddress']['name'] = 'ООО "ФИРМЕННЫЕ РЕШЕНИЯ"';
//        $form['receiverAddress']['city'] = 'Петро-Славянка';
//        $form['receiverAddress']['region'] = 'Санкт-Петербург';
//        $form['receiverAddress']['street'] = 'Софийская';
//        $form['receiverAddress']['streetAbbr'] = 'ул';
//        $form['receiverAddress']['house'] = '118';
//        $form['receiverAddress']['contactFio'] = 'Сотрудник склада';
//        $form['receiverAddress']['contactPhone'] = '244 68 04';
//        $form['receiverAddress']['houseKorpus'] = '5';
//        $form['receiverAddress']['str'] = '';
//        $form['receiverAddress']['office'] = '';
//        $form['receiverAddress']['flat'] = '';

        return $form;
    }

    /**
     * Возвращает массив с данными для отправки на сервер DPD для создания заказа
     *
     * @param array $form
     *
     * @return array
     */
    private static function getDataToSendToCreateOrder(array $form): array
    {
        Log::debug(Log::DPD_ORDER, "Из данных формы формируем массив для отправки на создание ТТН");

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

        if (!empty($form['senderAddress']['index'])) {
            $arData['header']['senderAddress']['index'] = $form['senderAddress']['index'];// Почтовый индекс
        }


        $arData['order'] = array(
            'orderNumberInternal' => $form['orderNumberInternal'], // ваш личный код (я использую код из таблицы заказов ID)
            'serviceCode' => 'PCL', //$form['serviceCode'], // тариф. 3-ех буквенный. PCL - то что нужно (DPD OPTIMUM)
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

        if (!empty($form['receiverAddress']['index'])) {
            $arData['order']['receiverAddress']['index'] = $form['receiverAddress']['index'];// Почтовый индекс
        }

//$arData['order']['extraService'][0] = array('esCode' => 'EML', 'param' => array('name' => 'email', 'value' => $select["email"]));
//$arData['order']['extraService'][1] = array('esCode' => 'НПП', 'param' => array('name' => 'sum_npp', 'value' => $select["cena"]));
//$arData['order']['extraService'][2] = array('esCode' => 'ОЖД', 'param' => array('name' => 'reason_delay', 'value' => 'СООТ')); // пример нескольких опций

        $arRequest['orders'] = $arData; // помещаем запрос в orders

        Log::debug(Log::DPD_ORDER, "Для создания заказа сформировали массив: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));
        return $arRequest;
    }

}