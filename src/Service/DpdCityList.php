<?php

/**
 *  Класс для обновления списка городов с возможностью доставки DPD
 */

namespace App\Service;

use App\Log;

class DpdCityList
{

    const MAX_CITY_COUNT_TO_RETURN = 15;  // Максимальное количество подходящих городов для возврата в форму создания ТТН

    const LIST_FOLDER = PROJECT_DIR . '/data/dpd-cities/';
    // Пути к JSON-файлам со списком городов
    const CITY_LIST_IDS_PATH = self::LIST_FOLDER . 'city-list-ids.json';        // Ключами выступают - ID нас. пункта
    const CITY_LIST_CITIES_PATH = self::LIST_FOLDER . 'city-list-cities.json';  // Ключами выступают - Название нас. пункта

    // ФТП-соединение с DPD (файл с городами)
    const FTP_FILENAME_PART = 'GeographyDPD';

    const CITY_LIST_ORIGINAL_PATH = self::LIST_FOLDER . self::FTP_FILENAME_PART . '.csv';


    /**
     * Возвращает Json с городами, удовлетворяющими поисковому запросу
     *
     * @return void
     */
    public static function getCitiesJson(): void
    {
        Log::info(Log::DPD_CITY_FIND, "Старт");
        $query = $_GET[CITY_SEARCH_KEY_NAME];
        Log::debug(Log::DPD_CITY_FIND, "От пользователя: $query");

        $cityIds = self::getCityIds($query);

        $returnArray = self::getCityArray($cityIds);

        echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);

    }

    /**
     * Обновляет список со всеми городами доступными для доставки курьером
     *
     * @return void
     */
    public static function update(): void
    {
        // Без следующей строки CSV-файл от DPD не будет видеть конец строки (используется разделить строк: \r
        ini_set("auto_detect_line_endings", true);

        Log::info(Log::DPD_CITY_UPD, "Старт");
        try {
            self::downloadCsvFromFtp();
            $jsons = self::csvToJson(self::CITY_LIST_ORIGINAL_PATH);
            self::saveJsons($jsons);
        } catch (\Exception $e) {
            Log::error(Log::DPD_CITY_UPD, "Exception: " . $e->getMessage());
        }
    }

    /**
     * Выкачивает файл с фтп с заменой уже скачанного
     *
     * @return void
     *
     * @throws \Exception
     */
    private static function downloadCsvFromFtp(): void
    {
        $ftp = ftp_connect(FTP_SERVER); // установка соединения

        if (!$ftp) {
            throw new \Exception("FTP ошибка: Не Удалось подсоединиться к серверу");
        }

        if (!ftp_login($ftp, FTP_USER, FTP_PASSWORD)) {
            throw new \Exception("FTP ошибка: Неверный логин / пароль");
        }

        ftp_pasv($ftp, true);


        $remoteFolder = 'integration'; // В документации от DPD сказано об этой папке. Сама в руте, а в ней искомый CSV

        if (!$listOfFilesOnServer = ftp_nlist($ftp, $remoteFolder)) {
            throw new \Exception("FTP ошибка: Не получилось получить список файлов");
        }

        $remoteFilename = ""; // Сюда запишем название файла для скачивания

        foreach ($listOfFilesOnServer as $filename) { // Среди всех файлов в руте на сайте ищем необходимый
            if (str_contains($filename, self::FTP_FILENAME_PART) && str_contains($filename, ".csv")) {
                $remoteFilename = $filename;
                break;
            }
        }

        if (empty($remoteFilename)) {
            throw new \Exception("На FTP не было найдено файла с упоминанием " . self::FTP_FILENAME_PART .
                " среди: " . implode(", ", $listOfFilesOnServer));
        }

        Log::debug(Log::DPD_CITY_UPD, "Пытаемся выкачать с FTP файл: $remoteFilename");

        // Проверяет создана ли соответствующая папка. Создает, если не существует
        if (!is_dir(self::LIST_FOLDER)) {
            if (!mkdir(self::LIST_FOLDER, 0770, true)) {
                Log::critical(Log::DPD_CITY_UPD, "Не получилось создать папку: " . self::LIST_FOLDER);
            }
        }

        if (!ftp_get($ftp, self::CITY_LIST_ORIGINAL_PATH, $remoteFilename, FTP_ASCII)) {
            throw new \Exception("FTP ошибка: Не удалось скачать существующий файл: $remoteFilename");
        }

        ftp_close($ftp);

        Log::info(Log::DPD_CITY_UPD, "Успешно скачали с FTP файл: $remoteFilename");
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
        $maxIdsForOneCityName = 0; // Используем для лога - узнать максимальное количество одинаково названных населенных пунктов


        while ($row = self::customfgetcsv($contents, "400", ";")) {
            if (!str_starts_with($row[5], 'Россия')) {
                continue;
            }

            $array1[$row[0]] = [$row[2], $row[3], $row[4]];

            $array2[$row[3]][] = $row[0];


            $cityCount++; // Для лога

            if (count($array2[$row[3]]) > $maxIdsForOneCityName) {  // Для лога
                $maxIdsForOneCityName = count($array2[$row[3]]);
                if ($maxIdsForOneCityName == 300) {
                    Log::debug(Log::DPD_CITY_UPD, "Нас. пункт встретился 300 раз: $row[3]");
                }
            }
        }

        Log::info(Log::DPD_CITY_UPD, "Из CSV забрали городов РФ: $cityCount");
        Log::info(Log::DPD_CITY_UPD, "Название одного города повторялось максимально $maxIdsForOneCityName раз");

        if (!fclose($contents)) {
            Log::warning(Log::DPD_CITY_UPD, "функция fclose после работы с CSV вернула False");
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

        if (!file_put_contents(self::CITY_LIST_IDS_PATH, $jsons[0])) {
            Log::error(Log::DPD_CITY_UPD, "Не получилось сохранить файл: " . self::CITY_LIST_IDS_PATH);
            $isError = true;
        }

        if (!file_put_contents(self::CITY_LIST_CITIES_PATH, $jsons[1])) {
            Log::error(Log::DPD_CITY_UPD, "Не получилось сохранить файл: " . self::CITY_LIST_CITIES_PATH);
            $isError = true;
        }

        if (!$isError) {
            Log::info(Log::DPD_CITY_UPD, "Успешно сохранился обновленный список городов");
        }
    }

    /**
     * Возвращает массив с ID городов удовлетворяющих поисковом запросу
     *
     * Например: в параметре получили "Мос". Метод вернет ID нас.пунктов начинающихся на эти буквы "Мос"
     *
     * @param string $query
     *
     * @return array Например: [100, 101234, ...
     */
    private static function getCityIds(string $query): array
    {

        $input = file_get_contents(self::CITY_LIST_CITIES_PATH);
        mb_convert_encoding($input, "UTF-8", "auto");

        $cityList = json_decode($input);

        if (empty($cityList)) {
            Log::critical(Log::DPD_CITY_FIND, "Файл с городами пуст: " . self::CITY_LIST_CITIES_PATH);
        }

        $returnArray = [];

        foreach ($cityList as $cityName => $cityIdsArray) {
            if (str_starts_with($cityName, $query)) {
                $returnArray = array_merge($returnArray, $cityIdsArray);
            }
            if (count($returnArray) > self::MAX_CITY_COUNT_TO_RETURN) { // Если накопили больше необходимого - обрезаем
                $returnArray = array_slice($returnArray, count($returnArray) - self::MAX_CITY_COUNT_TO_RETURN);
                break;
            }
        }

        Log::info(Log::DPD_CITY_FIND, "Нашли подходящих ID городов: " . count($returnArray));
        return $returnArray;
    }

    /**
     * Возвращает массив из нашего списка городов, но лишь тех городов, чьи ID переданы в параметре
     *
     * Выйдет массив каждый элемент которого одномерный массив с 3 элементами: abbreviation - 2 (г), city - 3 (Ялта), region - 4 (Респ Крым)
     *
     * @param array $cityIds
     *
     * @return array
     */
    private static function getCityArray(array $cityIds): array
    {
        $input = file_get_contents(self::CITY_LIST_IDS_PATH);
        mb_convert_encoding($input, "UTF-8", "auto");

        $cityList = json_decode($input); // Возвращает StdClass

        $returnArray = [];

        foreach ($cityIds as $id) {
            $returnArray[] = $cityList->$id;
        }

        Log::debug(Log::DPD_CITY_FIND, "Вернули массив с " . count($returnArray));
        return $returnArray;
    }

}