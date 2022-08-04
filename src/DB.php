<?php
/**
 * Класс для работы с БД
 */

namespace App;

class DB
{

    /**
     * Вносит запись в БД
     *
     * Записи хранятся в JSON в таком виде: {$ticketId => { ttn => $ttn, state => $statusDPD }, $ticketId2 => ... }
     * $ticketId - ID Тикета из UseDesk, $ttn - номер ТТН от DPD, $statusDPD - полученный статус ТТН от DPD
     *
     * @param string $ticketId
     * @param string $ttn
     * @param string $statusDPD
     * @param string $logCategory
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function saveToBD(string $ticketId, string $ttn, string $statusDPD, string $logCategory = Log::DPD_ORDER): void
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

        } else { // Если БД существует

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

        }

        // Добавляем вносимое значение
        $dataArrays[$ticketId] = [TTN_JSON_KEY => $ttn, STATE_JSON_KEY => $statusDPD];

        // Перезаписываем нашу БД
        if (!file_put_contents(DATA_JSON, json_encode($dataArrays, JSON_UNESCAPED_UNICODE))) {
            Log::critical($logCategory, "Не получилось обновить БД");
        } else {
            Log::info($logCategory, "Добавили в БД запись (ID Тикета - $ticketId): " .
                json_encode($dataArrays[$ticketId], JSON_UNESCAPED_UNICODE));
        }

    }

    /**
     * Возвращает из БД для тикета UseDesk: массив c номером ТТН и статусом. Или пустой массив, если не было такого тикета
     *
     * Массив в виде: [ ttn => $ttn, state => $statusDPD ] , где $ttn - номер ТТН от DPD, $statusDPD - полученный статус ТТН от DPD
     *
     * @param $ticketId
     * @param $logCategory
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function getTtn($ticketId, $logCategory = Log::UD_BLOCK): array
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
            Log::info($logCategory, "Вернули из БД тикет $ticketId с содержимым: " . json_encode($dataArrays[$ticketId]));
            return $dataArrays[$ticketId];
        }

        // Если не нашли в БД Тикет
        Log::info($logCategory, "В БД не было тикета: $ticketId");

        return [];

    }
}