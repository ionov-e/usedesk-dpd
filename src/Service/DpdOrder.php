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
    const URL_TRACING = URL_DPD_DOMAIN . "services/tracing1-1?wsdl";

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
            $lastProcessState = self::getLastProcessState($return->orderNum);
            DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $return->orderNum, $lastProcessState);
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

        Log::debug(Log::UD_BLOCK, "Запрос на чек создания статуса посылки: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));

        // IDE не подсказывает, но Soap может кидать SoapFault исключения
        $client = new \SoapClient (self::URL_ORDER);
        $responseStd = $client->getOrderStatus($arRequest); //делаем запрос в DPD

        Log::debug(Log::UD_BLOCK, "Ответ на чек создания статуса посылки: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));

        if (!property_exists($responseStd, "return")) { // Если статусы не возвращаются
            Log::critical(Log::UD_BLOCK, "В ответе не было свойства 'return'");
            return [];
        }

        // StdClass. Все входить внутри одного свойства 'return'
        //Обязательные ключи: orderNumberInternal, status.
        //Необязательные: orderNum, errorMessage, pickupDate, dateFlag (последние 2 - ни разу не наблюдал)

        $return = $responseStd->return;

        // Если статус изменился - записываем изменения в "БД" (исключение: статус "не найден". Возможно перезапишем)
        $logMessage = "Тикет $ticketId имел в БД статус создания: " . $ttnArray[STATE_KEY_NAME] . ". В DPD: " . $return->status . PHP_EOL . json_encode($return, JSON_UNESCAPED_UNICODE);

        // Перепроверим последний статус выполнения заказа.
        if (ORDER_OK == $return->status && LAST_DELIVERED != $ttnArray[LAST_KEY_NAME]) { // Иначе нет смысла проверять
            $lastProcessState = self::getLastProcessState($return->orderNum);
            // Если получили непустое значение и отличается от прошлого - перезаписываем БД
            if (!empty($lastProcessState) && $lastProcessState != $ttnArray[LAST_KEY_NAME]) {
                Log::info(Log::UD_BLOCK, "Вносим в БД статус выполнения заказа: '$lastProcessState' вместо '{$ttnArray[LAST_KEY_NAME]}'");
                $ttnArray = DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $return->orderNum, $lastProcessState, Log::UD_BLOCK);
            }
        }

        switch ($return->status) {
            case ORDER_NOT_FOUND:
                Log::info(Log::UD_BLOCK, $logMessage);
                if ((new \DateTime($ttnArray[DATE_KEY_NAME]))->modify("+1 day")->getTimestamp() < time()) {
                    return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, ORDER_WRONG, null, null, Log::UD_BLOCK);
                }
                return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, null, null, Log::UD_BLOCK);
            case $ttnArray[STATE_KEY_NAME]: // Если статус не изменился и статус был найден - возвращаем значения из "БД" без перезаписи БД
                Log::info(Log::UD_BLOCK, "Проверили тикет: $ticketId - статус создания не изменился: {$ttnArray[STATE_KEY_NAME]}");
                return $ttnArray;
            case ORDER_OK:
                Log::info(Log::UD_BLOCK, $logMessage);
                return $ttnArray;
            case ORDER_PENDING:
            case ORDER_DUPLICATE:
                Log::warning(Log::UD_BLOCK, $logMessage);
                return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, null, null, Log::UD_BLOCK);
            case ORDER_ERROR:
                if (ORDER_UNCHECKED == $ttnArray[STATE_KEY_NAME]) {
                    Log::warning(Log::UD_BLOCK, "Ставим ORDER_WRONG: $logMessage");
                    return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, ORDER_WRONG, null, null, Log::UD_BLOCK);
                } else {
                    Log::error(Log::UD_BLOCK, "Неожиданно получили {$return->status} при статус-чеке: $logMessage");
                    return [];
                }
            case ORDER_CANCELED:
                if (ORDER_UNCHECKED == $ttnArray[STATE_KEY_NAME]) {
                    Log::warning(Log::UD_BLOCK, "Ставим ORDER_WRONG: $logMessage");
                    return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, ORDER_WRONG, null, null, Log::UD_BLOCK);
                } else {
                    Log::error(Log::UD_BLOCK, "Получили {$return->status} при статус-чеке: $logMessage");
                    // Пытаемся найти №ТТН вначале в ответе на запрос, потом в прошлой записи БД. Иначе ничего
                    $ttn = null;
                    if (!empty($return->orderNum)) {
                        $ttn = $return->orderNum;
                    } elseif (!empty($ttnArray[TTN_KEY_NAME])) {
                        $ttn = $ttnArray[TTN_KEY_NAME];
                    }
                    return DB::saveTicketToDb($ticketId, $return->orderNumberInternal, $return->status, $ttn, null, Log::UD_BLOCK);
                }
            default: // По идее сюда мы не должны дойти. Все случаи в "если" предусмотрены
                Log::critical(Log::UD_BLOCK, "Непредвиденный случай. $logMessage");
                return [];
        }
    }

    /**
     * Обновляет статусы выполнения заказов DPD
     *
     * @return void
     */
    public static function updateProcessStates(): void
    {
        Log::info(Log::CRON_LAST_UPDATE, "Старт");
        try {

            $dataArrays = DB::getDbAsArray(Log::CRON_LAST_UPDATE);

            // Переменные для лога
            $countTotal = count($dataArrays);
            $countNotCreated = 0;
            $countUpdated = 0;
            $countDoneBefore = 0;
            $countTooOld = 0;
            $countSame = 0;

            foreach ($dataArrays as $ticketId => $ttnArray) {

                // Не будем проверять новый статус выполнения, если статус создания не ОК
                if (ORDER_OK != $ttnArray[STATE_KEY_NAME]) {
                    $countNotCreated++;
                    continue;
                }

                // Не будем проверять новый статус выполнения, если уже получен "окончательный"
                if (!empty($ttnArray[LAST_KEY_NAME]) || in_array($ttnArray[LAST_KEY_NAME], [LAST_DELIVERED, LAST_NOT_DONE])) {
                    $countDoneBefore++;
                    continue;
                }

                // Не будем проверять новый статус выполнения, если с последней записи в БД > 90 дней
                if ((new \DateTime($ttnArray[DATE_KEY_NAME]))->modify("+90 day")->getTimestamp() < time()) {
                    $countTooOld++;
                    continue;
                }

                $lastProcessState = self::getLastProcessState($ttnArray[TTN_KEY_NAME], Log::CRON_LAST_UPDATE);
                // Если получили пустое значение или такое же, как в БД - не перезаписываем БД
                if (empty($lastProcessState) || $lastProcessState == $ttnArray[LAST_KEY_NAME]) {
                    $countSame++;
                    continue;
                }

                DB::saveTicketToDb($ticketId, $ttnArray[INTERNAL_KEY_NAME], $ttnArray[STATE_KEY_NAME], $ttnArray[TTN_KEY_NAME], $lastProcessState, Log::CRON_LAST_UPDATE);
                $countUpdated++;
            }

            Log::info(Log::CRON_LAST_UPDATE, "Обновили тикетов: $countUpdated/$countTotal. Пропущено: 'несозданных' $countNotCreated, 'готовых' $countDoneBefore, 'старых' $countTooOld, 'таких же' $countSame");

        } catch (\Exception $e) {
            Log::error(Log::CRON_LAST_UPDATE, "Exception: " . $e->getMessage());
        }
    }


    /**
     * Возвращает последний статус выполнения посылки. Если не получили - возвращает пустую строку
     *
     * @param string $ttnNumber
     * @param string $logCategory
     *
     * @return string
     */
    public static function getLastProcessState(string $ttnNumber, string $logCategory = Log::UD_BLOCK): string
    {
        try {

            $arData = array();
            $arData['auth'] = self::getAuthArray();
            $arData['clientParcelNr'] = $ttnNumber;
            $arRequest['request'] = $arData; // помещаем запрос

            Log::debug($logCategory, "Запрос на чек статуса выполнения посылки: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));

            // IDE не подсказывает, но Soap может кидать SoapFault исключения
            $client = new \SoapClient (self::URL_TRACING);
            $responseStd = $client->getStatesByClientParcel($arRequest); //делаем запрос в DPD
            Log::debug($logCategory, "Ответ на чек статуса выполнения посылки:" . json_encode($responseStd, JSON_UNESCAPED_UNICODE));

            // StdClass. Все входить внутри одного свойства 'return'
            if (!property_exists($responseStd, "return")) { // Если статусы не возвращаются
                Log::critical($logCategory, "В ответе не было свойства 'return'");
                return "";
            }

            // Может содержать 'states' (массив), а может нет (случай, если еще нет получен на терминал, или уже прошло 90 дней хранения последнего статуса выполнения посылки)
            if (!property_exists($responseStd->return, "states")) { // Если статусы не возвращаются
                return "";
            }

            $states = $responseStd->return->states;
            if (!is_array($states)) { // Если статус возвращается 1 - не будет массива
                return $states->newState;
            }

            //Поиск последнего статуса в массиве
            $maxTransitionTime = ''; //transitionTime
            $lastNewState = ''; //newState
            foreach ($states as $state) {
                if ($state->transitionTime > $maxTransitionTime) { // Если новее
                    $maxTransitionTime = $state->transitionTime;
                    $lastNewState = $state->newState;
                }
            }

            if (empty($lastNewState)) {
                Log::critical($logCategory, "Ошибка в логике поиска последнего статуса выполнения посылки");
                return "";
            }

            Log::debug($logCategory, "Получили статус выполнения: $lastNewState");

            return $lastNewState;

        } catch (\SoapFault $e) {
            if (!$e->getMessage() == "Данные не найдены") {
                Log::error($logCategory, "SoapFault:" . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
            }
        } catch (\Exception $e) {
            Log::error($logCategory, "Exception:" . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE));
        }
        return '';
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