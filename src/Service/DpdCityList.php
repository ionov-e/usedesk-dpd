<?php

/**
 *  Класс для обновления списка городов с возможностью доставки DPD
 */

namespace App\Service;

use App\Log;

class DpdCityList
{

    const LIST_FOLDER = PROJECT_DIR . '/data/dpd-cities/';
    const JSON1_PATH = self::LIST_FOLDER . 'city-list-ids.json';
    const JSON2_PATH = self::LIST_FOLDER . 'city-list-cities.json';

    /**
     * Обновляет список со всеми городами доступными для доставки курьером
     *
     * @return void
     */
    public static function update(): void
    {
        // Без следующей строки CSV-файл от DPD не будет видеть конец строки (используется разделить строк: \r
        ini_set("auto_detect_line_endings", true);

        Log::info(Log::DPD_CITIES, "Старт");
        try {
            $csvPath = self::LIST_FOLDER . "csv/GeographyDPD_20220725.csv"; #TODO сделать скачивание с ФТП, и передачу ссылки
            $jsons = self::csvToJson($csvPath);
            self::saveJsons($jsons);
        } catch (\Exception $e) {
            Log::error(Log::DPD_CITIES, "Exception" . $e->getMessage());
        }
    }

    /**
     * Возвращает 2 JSON строки с распарсенными данными из CSV
     *
     * @param string $csvPath Путь к файлу с городами
     *
     * @return array
     * @throws \Exception
     */
    private static function csvToJson(string $csvPath): array
    {
        if (!($contents = fopen($csvPath, 'r'))) {
            throw new \Exception("Не вышло открыть файл");
        }


        // Пример строки из файла: 4553454126;RU91000008000;г;Ялта;Респ Крым;Россия
        // Из такого массива:
        // - Пропускаем строки не "Россия"
        // - Раскидываем в 2 разных массива:
        //      1) Используем элементы: id - 0 (4553454126), abbreviation - 2 (г), city - 3 (Ялта), region - 4 (Респ Крым)
        //          Записываем: 0 => [2, 3, 4]   // 0 (id) - уникален
        //      2) Используем элементы: id - 0 (4553454126), city - 3 (Ялта)
        //          Записываем: 3 => [0, 0, 0]   // 3 (city) - неуникален. А в значении - массив с уникальными ID


        $array1 = array();
        $array2 = array();
        $cityCount = 0; // Используется лишь для лога


        while ($row = self::customfgetcsv($contents, "400", ";")) {
            if (!str_starts_with($row[5], 'Россия')) {
                continue;
            }

            $array1[$row[0]] = [$row[2], $row[3], $row[4]];

            $array2[$row[3]][] = $row[0];

            $cityCount++;

        }

        Log::info(Log::DPD_CITIES, "Из CSV забрали городов РФ: $cityCount");

        if (!fclose($contents)) {
            Log::warning(Log::DPD_CITIES, "функция fclose после работы с CSV вернула False");
        }

        return [json_encode($array1, JSON_UNESCAPED_UNICODE), json_encode($array2, JSON_UNESCAPED_UNICODE)];
    }


    /**
     * Заменяет работу fgetcsv, но с решением проблемы кириллицы с конкретными CSV-файлами
     *
     * Совершенно не собираюсь брать заслуги за свое спасение этой функцией. Источник: https://stackoverflow.com/a/19213270
     */
    private static function customfgetcsv(&$handle, $length, $separator = ';')
    {
        if (($buffer = fgets($handle, $length)) !== false) {
            return explode($separator, iconv("CP1251", "UTF-8", $buffer));
        }
        return false;
    }

    /**
     * Сохраняет полученные массивы
     *
     * @param array $jsons
     *
     * @return void
     */
    private static function saveJsons(array $jsons): void
    {
        $isError = false;

        // Проверяет создана ли соответствующая папка. Создает, если не существует
        // Можно было бы вынести проверку коренной папке при инициализации, а другие нет - может пройти месяц, год (в новые папке сохранять)
        if (!is_dir(self::LIST_FOLDER)) {
            mkdir(self::LIST_FOLDER, 0770, true);
        }

        if (!file_put_contents(self::JSON1_PATH, $jsons[0])) {
            Log::error(Log::DPD_CITIES, "Не получилось сохранить файл: " . self::JSON1_PATH);
            $isError = true;
        }

        if (!file_put_contents(self::JSON2_PATH, $jsons[1])) {
            Log::error(Log::DPD_CITIES, "Не получилось сохранить файл: " . self::JSON2_PATH);
            $isError = true;
        }

        if (!$isError) {
            Log::info(Log::DPD_CITIES, "Успешно сохранился обновленный список городов");
        }
    }

}