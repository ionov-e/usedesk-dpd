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
     * @return void
     *
     * @throws \Exception
     */
    public static function createOrder(): void
    {
        $form = self::getFormData();

        $ticketId = $form[TICKET_ID_KEY_NAME];

        $arRequest = self::getDataToSendToCreateOrder($form);

        // IDE не подсказывает, но Soap может кидать SoapFault исключения
        $client = new \SoapClient (self::URL_ORDER);
        $responseStd = $client->createOrder($arRequest); //делаем запрос в DPD, получаем StdClass
        $return = $responseStd->return;

        //Обязательные ключи: orderNumberInternal, status.
        //Необязательные: orderNum, errorMessage, pickupDate, dateFlag (последние 2 - ни разу не наблюдал)

        // Статусы могут быть:
        // OK – заказ на доставку успешно создан с номером, указанным в поле orderNum.
        // OrderPending – заказ на доставку принят, но нуждается в ручной доработке сотрудником DPD, (например, по причине того, что адрес доставки не распознан автоматически). Номер заказа будет присвоен ему, когда это доработка будет произведена.
        // OrderDuplicate – заказ на доставку не может быть принять по причине, указанной в поле errorMessage.
        // OrderError – заказ на доставку не может быть создан по причине, указанной в поле errorMessage.
        // OrderCancelled – заказ отменен

        if (ORDER_OK == $return->status) { // Единственный ответ с № ТТН
            Log::info(Log::DPD_ORDER, "Тикет $ticketId: Успешно создан заказ в DPD. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $return->orderNum);
            header("Location: https://secure.usedesk.ru/tickets/$ticketId"); // Возвращаем на страницу тикета
        } elseif (ORDER_ERROR == $return->status) {
            Log::error(Log::DPD_ORDER, "Тикет $ticketId: ОШИБКА. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            echo $return->errorMessage; //выводим ошибки
        } else { // Здесь остаются на вариант статусы: OrderPending, OrderDuplicate, OrderCancelled. Все записываем, но без ТТН
            Log::warning(Log::DPD_ORDER, "Тикет $ticketId: Получил статус 'OrderPending'. Ответ: " . json_encode($return, JSON_UNESCAPED_UNICODE));
            DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status);
            header("Location: https://secure.usedesk.ru/tickets/$ticketId"); // Возвращаем на страницу тикета
        }
    }

    /**
     * Возвращает массив тикета из ответа статус-чека заказа в DPD. Перезаписывает в БД при изменении статуса
     *
     * Если статус не изменился - в итоге вернет точно такой же массив как и переданный в параметре
     *
     * @param string $ticketId
     * @param array $ttnArray
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function checkOrder(string $ticketId, array $ttnArray): array
    {

        // Формирование массива для отправки на проверку статуса в DPD
        $arData = array();
        $arData['auth'] = self::getAuthArray();
        $arData['order'] = array('orderNumberInternal' => $ttnArray[INTERNAL_KEY_NAME]);
        $arRequest['orderStatus'] = $arData; // помещаем запрос в orders

        Log::debug(Log::UD_BLOCK, "Подготовлен массив для получения статуса посылки: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));

        // IDE не подсказывает, но Soap может кидать SoapFault исключения
        $client = new \SoapClient (self::URL_ORDER);
        $responseStd = $client->getOrderStatus($arRequest); //делаем запрос в DPD

        // StdClass.
        //Обязательные ключи: orderNumberInternal, status.
        //Необязательные: orderNum, errorMessage, pickupDate, dateFlag (последние 2 - ни разу не наблюдал)

        $return = $responseStd->return;

        // Если статус не изменился - возвращаем значения из "БД"
        if ($return->status == $ttnArray[STATE_KEY_NAME]) {
            Log::info(Log::UD_BLOCK, "Проверили тикет: $ticketId - статус не изменился: {$ttnArray[STATE_KEY_NAME]}");
            return $ttnArray;
        }

        // Если статус изменился - записываем изменения в "БД"

        $logMessage = "Тикет $ticketId имел в БД статус: " . $ttnArray[STATE_KEY_NAME] . ". В DPD: " . $return->status . PHP_EOL . json_encode($return, JSON_UNESCAPED_UNICODE);

        if (ORDER_OK == $return->status) {
            Log::info(Log::UD_BLOCK, $logMessage);
            return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $return->orderNum, Log::UD_BLOCK);
        }

        if (ORDER_PENDING == $return->status || ORDER_DUPLICATE == $return->status) {
            Log::warning(Log::UD_BLOCK, $logMessage);
            return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, null, Log::UD_BLOCK);
        }

        if (ORDER_ERROR == $return->status) {
            if (ORDER_UNCHECKED == $ttnArray[STATE_KEY_NAME]) {
                Log::warning(Log::UD_BLOCK, "Ставим ORDER_WRONG: $logMessage");
                return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, ORDER_WRONG, null, Log::UD_BLOCK);
            } else {
                Log::error(Log::UD_BLOCK, "Неожиданно получили {$return->status} при статус-чеке: $logMessage");
                return [];
            }
        }

        if (ORDER_CANCELED == $return->status) {
            if (ORDER_UNCHECKED == $ttnArray[STATE_KEY_NAME]) {
                Log::warning(Log::UD_BLOCK, "Ставим ORDER_WRONG: $logMessage");
                return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, ORDER_WRONG, null, Log::UD_BLOCK);
            } else {
                Log::error(Log::UD_BLOCK, "Получили {$return->status} при статус-чеке: $logMessage");
                $ttn = null;
                if (!empty($return->orderNum)) {
                    $ttn = $return->orderNum;
                } elseif (!empty($ttnArray[TTN_KEY_NAME])) {
                    $ttn = $ttnArray[TTN_KEY_NAME];
                }

                return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $ttn, Log::UD_BLOCK);
            }
        }

        // По идее сюда мы не должны дойти. Все случа в "если" предусмотрены
        Log::critical(Log::UD_BLOCK, "Непредвиденный случай. $logMessage");
        return [];
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

        if (empty($form['senderAddress']['contactFio'])) { // Если пусто значение - берем из "Названия компании"
            $form['senderAddress']['contactFio'] = $form['senderAddress']['name'];
        }

        // Незаполненные необязательные поля будут содержать null. Избавимся от них
        foreach ($form as $element) {
            if (empty($element)) {
                unset($element);
            }
        }
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

        $arData['auth'] = self::getAuthArray();

        $arData['header'] = array( //отправитель
            'datePickup' => $form['senderAddress']['datePickup'],             //дата того когда посылку заберут
            'pickupTimePeriod' => $form['senderAddress']['pickupTimePeriod'], //время для курьера: 9-18, 9-13, 13-18
            'senderAddress' => array(
                'name' => $form['senderAddress']['name'],
                'countryName' => 'Россия',
                'city' => $form['senderAddress']['city'],
                'region' => $form['senderAddress']['region'],
                'street' => $form['senderAddress']['street'],
                'house' => $form['senderAddress']['house'],
                'contactFio' => $form['senderAddress']['contactFio'],
                'contactPhone' => $form['senderAddress']['contactPhone'],
                'contactEmail' => $form['senderAddress']['contactEmail']
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
            'orderNumberInternal' => $form['orderNumberInternal'],
            'serviceCode' => 'PCL', //$form['serviceCode'], // тариф. 3-ех буквенный. PCL - то что нужно (DPD OPTIMUM)
            'serviceVariant' => 'ДД',                   // вариант доставки ДД - дверь-дверь
            'cargoNumPack' => $form['cargoNumPack'],    //количество мест
            'cargoWeight' => $form['cargoWeight'],      // вес посылок Пример: 0.05 (после точки не более 2-х знаков)
            'cargoVolume' => $form['cargoVolume'],      // объём посылок
            'cargoValue' => $form['cargoValue'],        // оценочная стоимость
            'cargoCategory' => $form['cargoCategory'],  // Пример: Одежда / Товары
            'receiverAddress' => array(
                'name' => $form['receiverAddress']['name'],
                'countryName' => 'Россия',              // Другие кажется не надо
                'city' => $form['receiverAddress']['city'],
                'region' => $form['receiverAddress']['region'],
                'street' => $form['receiverAddress']['street'],
                'house' => $form['receiverAddress']['house'],
                'contactFio' => $form['receiverAddress']['contactFio'],
                'contactPhone' => $form['receiverAddress']['contactPhone'],
                'contactEmail' => $form['receiverAddress']['contactEmail']
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

    /**
     * Возвращает массив с данными авторизации для SOAP
     *
     * @return array
     */
    private static function getAuthArray()
    {
        return array(
            'clientNumber' => CLIENT_NUMBER,
            'clientKey' => CLIENT_KEY
        );
    }

}