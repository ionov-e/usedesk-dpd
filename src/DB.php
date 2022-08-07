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
     * Записи хранятся в JSON в таком виде: {$ticketId => { int => $int, state => $statusDPD, ttn => $ttn}, $ticketId2 => ... }
     * $ticketId - ID Тикета из UseDesk, $int - внутренний №заказа, $statusDPD - полученный статус ТТН от DPD, $ttn - номер ТТН от DPD (если получили)
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
    public static function saveToBD(string $ticketId, string $internal, string $statusDPD, string $ttn = null, string $logCategory = Log::DPD_ORDER): array
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
            $dataArrays = self::removeTicket($ticketId, $logCategory);
        }

        // Добавляем вносимое значение
        $newArray = [];
        $newArray[INTERNAL_JSON_KEY] = $internal;
        $newArray[STATE_JSON_KEY] = $statusDPD;
        if (!is_null($ttn)) {   // Например, если Pending в статусе - не пришлют
            $newArray[TTN_JSON_KEY] = $ttn;
        }

        $dataArrays[$ticketId] = $newArray;

        // Перезаписываем нашу БД
        if (!file_put_contents(DATA_JSON, json_encode($dataArrays, JSON_UNESCAPED_UNICODE))) {
            Log::critical($logCategory, "Не получилось обновить БД");
        } else {
            Log::info($logCategory, "Добавили в БД запись (ID Тикета - $ticketId): " .
                json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
        }

        return $newArray;
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
     * Удаление тикета из БД. Возвращает БД в виде массивов
     *
     * @param string $ticketId
     * @param string $logCategory
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function removeTicket(string $ticketId, string $logCategory = Log::DPD_ORDER): array
    {
        $dataArrays = json_decode(file_get_contents(DATA_JSON), true);

        if (is_null($dataArrays)) {
            Log::critical($logCategory, "Не получилось декодировать БД. Ошибка: " . json_last_error());
            throw new \Exception("Возникла ошибка");
        }

        if (!empty($dataArrays[$ticketId])) {
            Log::info($logCategory, "Удаляем прошлый тикет $ticketId из БД с содержимым: " .
                json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
            unset($dataArrays[$ticketId]);
        }

        return $dataArrays;
    }
}