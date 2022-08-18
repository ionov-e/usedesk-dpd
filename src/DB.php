<?php
/**
 * Класс для работы с БД
 */

namespace App;

class DB
{

    /**
     * Вносит запись в БД. Возвращает внесенные данные (без ticket ID)
     *
     * Записи хранятся в JSON в таком виде:
     * {$ticketId => { int => $int, state => $statusDPD, ttn => $ttn, date => 2022-08-15}, $ticketId2 => ... }
     *
     * $ticketId - ID Тикета из UseDesk,
     * $int - внутренний №заказа,
     * $statusDPD - полученный статус ТТН от DPD
     * $ttn - номер ТТН от DPD (если получили)
     * В 'date' записана дата записи (может быть повторная при перезаписи - случай смены статуса заказа)
     *
     * @param string $ticketId
     * @param string $internal
     * @param string $statusDPD
     * @param string|null $ttn
     * @param string $logCategory
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function saveTicketToDb(string $ticketId, string $internal, string $statusDPD, string $ttn = null, string $logCategory = Log::DPD_ORDER): array
    {

        if (!file_exists(DATA_JSON)) { // Если БД еще не существует

            Log::warning($logCategory, "Первая запись в БД");

            // Проверяет создана ли соответствующая папка. Создает, если не существует
            if (!is_dir(DATA_FOLDER_ROOT)) {
                if (!mkdir(DATA_FOLDER_ROOT, 0770, true)) {
                    Log::critical($logCategory, "Не получилось создать папку для БД");
                    throw new \Exception("Возникла ошибка");
                }
            }

            $dataArrays = [];

        } else { // Если БД существует - попытаемся удалить тикет из нее, если существует
            $dataArrays = self::getDbAsArray($logCategory);
            self::removeTicketFromArray($dataArrays, $ticketId, $logCategory);
        }

        // Добавляем вносимое значение
        $newArray = [];
        $newArray[INTERNAL_JSON_KEY] = $internal;
        $newArray[STATE_JSON_KEY] = $statusDPD;
        $newArray[DATE_JSON_KEY] = date("Y-m-d");
        if (!is_null($ttn)) {   // Например, если Pending в статусе - не пришлют
            $newArray[TTN_JSON_KEY] = $ttn;
        }

        $dataArrays[$ticketId] = $newArray;

        if (self::overwriteDb($dataArrays, $logCategory)) { // Перезаписываем нашу БД
            Log::info($logCategory, "Добавили в БД запись (ID Тикета - $ticketId): " .
                json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
        }

        return $newArray;
    }

    /**
     * Перезаписываем БД содержимым массива из параметра
     *
     * @param array $dataArrays
     * @param string $logCategory
     *
     * @return bool
     */
    public static function overwriteDb(array $dataArrays, string $logCategory = Log::DPD_ORDER): bool
    {
        if (!file_put_contents(DATA_JSON, json_encode($dataArrays, JSON_UNESCAPED_UNICODE))) {
            Log::critical($logCategory, "Не получилось обновить БД");
            return false;
        }
        return true;
    }

    /**
     * Возвращает из БД для тикета UseDesk: массив c номером ТТН и статусом. Или пустой массив, если не было такого тикета
     *
     * Массив в виде: [ ttn => $ttn, state => $statusDPD ] , где $ttn - номер ТТН от DPD, $statusDPD - полученный статус ТТН от DPD
     *
     * @param int $ticketId
     * @param string $logCategory
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getTtn(int $ticketId, string $logCategory = Log::UD_BLOCK): array
    {
        if (!file_exists(DATA_JSON)) { // Если БД еще не существует
            Log::warning($logCategory, "БД еще не существует");
            return [];
        }

        // Если БД существует

        $dataArrays = json_decode(file_get_contents(DATA_JSON), true);

        if (is_null($dataArrays)) {
            Log::critical($logCategory, "Не получилось декодировать БД. Ошибка: " . json_last_error());
            throw new \Exception("Возникла ошибка");
        }

        // Если нашли в БД Тикет
        if (!empty($dataArrays[$ticketId])) {
            Log::info($logCategory, "Вернули из БД тикет $ticketId с содержимым: " . json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
            return $dataArrays[$ticketId];
        }

        // Если не нашли в БД Тикет
        Log::info($logCategory, "В БД не было тикета: $ticketId");

        return [];

    }

    /**
     * Возвращает содержимое БД в виде массива
     *
     * @param string $logCategory
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getDbAsArray(string $logCategory = Log::DPD_ORDER): array
    {
        $dataArrays = json_decode(file_get_contents(DATA_JSON), true);

        if (is_null($dataArrays)) {
            Log::critical($logCategory, "Не получилось декодировать БД. Ошибка: " . json_last_error());
            throw new \Exception("Возникла ошибка");
        }

        return $dataArrays;
    }

    /**
     * Удаляет из массива с "БД" запись с тикетом и возвращает true, если тикет был найден внутри
     *
     * @param array $dataArrays
     * @param string $ticketId
     * @param string $logCategory
     *
     * @return bool
     */
    public static function removeTicketFromArray(array &$dataArrays, string $ticketId, string $logCategory = Log::DPD_ORDER): bool
    {
        if (!empty($dataArrays[$ticketId])) {
            Log::info($logCategory, "Удаляем прошлый тикет $ticketId из БД с содержимым: " .
                json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
            unset($dataArrays[$ticketId]);
            return true;
        }
        return false;
    }
}