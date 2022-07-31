<?php

namespace App\Helper;

use App\Log;

/**
 * Обрабатывает полученные данные от пользователя
 */
class InputHelper
{

    /**
     * Возвращает ID Тикета, если находит внутри Post-запроса
     *
     * @return int
     *
     * @throws \Exception
     */
    public static function getTicketIdFromPostJson(): int
    {
        Log::debug(LOG::UD_BLOCK, "Пробуем получить  ID Тикета из Post-запроса");

        $errorMsg = 'ID Тикета не найден'; // Переменная будет использоваться, только если не найден ID

        try {
            $postJson = file_get_contents('php://input');
            $data = json_decode($postJson);
            $ticketId = intval($data->{TICKET_ID_KEY_NAME});
            if (!empty($ticketId)) { // Здесь может быть и "0" - нас это тоже не устраивает
                Log::info(LOG::UD_BLOCK, "ID Тикета:" . $ticketId);
                return $ticketId;
            }
        } catch (\Exception $e) {
            $errorMsg .= $e->getMessage();
        }

        if (empty($postJson)) {
            Log::warning(LOG::UD_BLOCK, "Ничего не было прислано");
        } else {
            Log::warning(LOG::UD_BLOCK, "Вместо ID тикета Было прислано:" . PHP_EOL . $postJson);
        }

        throw new \Exception($errorMsg);
    }

    /**
     * Возвращает массив с данными из формы  #TODO Del
     *
     * @return array
     */
    public static function getFormData(): array
    {
        Log::info(Log::DPD_ORDER, "Получили из формы: " . json_encode($_POST));

        $form = $_POST;


        #TODO удалить из $_POST пустые значения
        #TODO убрать тестовые строки ниже. Получать значения из формы
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
    public static function getDataToSendToCreateOrder(array $form): array
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

//$arData['order']['extraService'][0] = array('esCode' => 'EML', 'param' => array('name' => 'email', 'value' => $select["email"]));
//$arData['order']['extraService'][1] = array('esCode' => 'НПП', 'param' => array('name' => 'sum_npp', 'value' => $select["cena"]));
//$arData['order']['extraService'][2] = array('esCode' => 'ОЖД', 'param' => array('name' => 'reason_delay', 'value' => 'СООТ')); // пример нескольких опций

        $arRequest['orders'] = $arData; // помещаем запрос в orders

        Log::debug(Log::DPD_ORDER, "Для создания заказа сформировали массив: " . json_encode($arRequest, JSON_UNESCAPED_UNICODE));
        return $arRequest;
    }

    /**
     * @throws \Exception
     */
    static function csvToJson($csvPath) {
        if (!($contents = fopen($csvPath, 'r'))) {
            throw new \Exception("Не вышло открыть файл");
        }

        $key = fgetcsv($contents,"256",",");

        // parse csv rows into array
        $json = array();
        while ($row = fgetcsv($contents,"256",",")) {
            $json[] = array_combine($key, $row);
        }

        // release file handle
        fclose($contents);

        // encode array to json
        return json_encode($json);
    }
}