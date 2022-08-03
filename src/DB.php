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

            $dataArrays = json_decode(DATA_JSON);

            if (is_null($dataArrays)) {
                Log::critical($logCategory, "Не получилось открыть БД");
            }

            if (!empty($dataArrays[$ticketId])) {
                Log::info($logCategory, "Удаляем прошлый тикет $ticketId из БД с содержимым: " .
                    json_encode($dataArrays[$ticketId]));
                unset($dataArrays[$ticketId]);
            }

        }

        // Добавляем вносимое значение
        $dataArrays[$ticketId] = [TTN_JSON_KEY => $ttn, STATE_JSON_KEY => $statusDPD];

        // Перезаписываем нашу БД
        if (!file_put_contents(DATA_JSON, json_encode($dataArrays))) {
            Log::critical($logCategory, "Не получилось обновить БД");
        } else {
            Log::info($logCategory, "Добавили в БД запись (ID Тикета - $ticketId): " .
                json_encode($dataArrays[$ticketId]));
        }

    }
}