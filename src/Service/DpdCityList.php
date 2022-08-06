<?php

/**
 *  Класс для обновления списка городов с возможностью доставки DPD
 */

namespace App\Service;

use App\Log;

class DpdCityList
{


    const MAX_CITY_COUNT_TO_RETURN = 15;  // Максимальное количество подходящих городов для возврата в форму создания ТТН
    //Для Dadata можно установить максимальное количество от 5 до 20

    const LIST_FOLDER_NEW = DATA_FOLDER_ROOT . '/dpd-cities/';
    // Пути к JSON-файлам со списком городов
    const CITY_LIST_IDS_PATH_NEW = self::LIST_FOLDER_NEW . 'city-list-ids.json';       // Ключами выступают - ID нас. пункта
    const CITY_LIST_CITIES_PATH_NEW = self::LIST_FOLDER_NEW . 'city-list-cities.json'; // Ключами выступают - Название нас. пункта

    const LIST_FOLDER_SAFE = DATA_FOLDER_ROOT . '/dpd-cities-ready/';
    // Пути к JSON-файлам со списком городов
    const CITY_LIST_IDS_PATH_SAFE = self::LIST_FOLDER_SAFE . 'city-list-ids.json';       // Ключами выступают - ID нас. пункта
    const CITY_LIST_CITIES_PATH_SAFE = self::LIST_FOLDER_SAFE . 'city-list-cities.json'; // Ключами выступают - Название нас. пункта

    // ФТП-соединение с DPD (файл с городами)
    const FTP_FILENAME_PART = 'GeographyDPD';

    const CITY_LIST_ORIGINAL_PATH = self::LIST_FOLDER_NEW . self::FTP_FILENAME_PART . '.csv';


    /**
     * Возвращает Json с городами, удовлетворяющими поисковому запросу
     *
     * @return void
     */
    public static function searchCitiesJson(): void
    {
        Log::info(Log::DPD_CITY_FIND, "Старт");
        $query = $_GET[CITY_SEARCH_KEY_NAME];
        Log::debug(Log::DPD_CITY_FIND, "От пользователя: $query");

//        try {
//            $cityIds = self::searchCitiesIds($query);
//
//            $returnArray = self::searchCitiesArray($cityIds);
//            echo json_encode($returnArray, JSON_UNESCAPED_UNICODE);
//        } catch (\Exception $e) {
//            Log::error(Log::DPD_CITY_UPD, "Exception: " . $e->getMessage());
//        }
        $dadataResponse = self::searchInDadata($query);
        echo json_encode($dadataResponse, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Обновляет список со всеми городами доступными для доставки курьером
     *
     * @return void
     */
    public static function update(): void
    {

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
        if (!is_dir(self::LIST_FOLDER_NEW)) {
            if (!mkdir(self::LIST_FOLDER_NEW, 0770, true)) {
                Log::critical(Log::DPD_CITY_UPD, "Не получилось создать папку: " . self::LIST_FOLDER_NEW);
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

        // Пример строки из файла: 4553454126;RU91000008000;г;Ялта;Респ Крым;Россия
        // Из такого массива:
        // - Пропускаем строки не "Россия"
        // - Раскидываем в 2 разных массива:
        //      1) Используем элементы: id - 0 (4553454126), abbreviation - 2 (г), city - 3 (Ялта), region - 4 (Респ Крым)
        //          Записываем: 0 => [2, 3, 4]   // 0 (id) - уникален
        //      2) Используем элементы: id - 0 (4553454126), city - 3 (Ялта)
        //          Записываем: 3 => [0, 0, 0]   // 3 (city) - неуникален. А в значении - массив с уникальными ID


        ini_set('memory_limit', -1); // Иначе при выполнении скрипта на текущем сервере выкидывает 500-ую. 134 МБ использовалось

        $array1 = array();
        $array2 = array();

        $cityCount = 0; // Используется лишь для лога
        $maxIdsForOneCityName = 0; // Используем для лога - узнать максимальное количество одинаково названных населенных пунктов

        $string = iconv('WINDOWS-1251', 'UTF-8', file_get_contents($csvPath)); // Получаем строку, конвертируем

        $data = str_getcsv($string, ";"); // Получаем сплошным, неподеленным на строки массивом

        $rows = array_chunk($data, 5); // Несмотря на то, что 6 элементов в элементе - не получается увидеть разделение строк
        // Т.е. Последний элемент и предыдущей строки у нас объединен с первым из следующим. Выходит: Россия4553454126.
        // Нам, к счастью, оба элемента не нужны. Точно так же определить страну можно по второму элементу

        foreach ($rows as $row) {

            if (!str_starts_with($row[1], 'RU')) { // Пропускаем все другие страны кроме РФ
                continue;
            }

            if (str_starts_with($row[2], 'авто')) { // Пропускаем все автодороги (возможно придется вернуть) (114 всего лишь на сейчас)
                continue;
            }

            if (str_starts_with($row[2], 'ж')) { // Пропускаем все ж/д станции (возможно придется вернуть) (783 на сейчас)
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

        return [json_encode($array1, JSON_UNESCAPED_UNICODE), json_encode($array2, JSON_UNESCAPED_UNICODE)];
    }

    /**
     * Сохраняет полученные массивы с городами
     *
     * @param array $jsons
     *
     * @return void
     */
    private static function saveJsons(array $jsons): void
    {
        // Проверяет создана ли соответствующая папка. Создает, если не существует
        if (!is_dir(self::LIST_FOLDER_NEW)) {
            if (mkdir(self::LIST_FOLDER_NEW, 0770, true)) {
                Log::warning(Log::DPD_CITY_UPD, "Впервые создали папку для обновленного списка городов");
            } else {
                Log::critical(Log::DPD_CITY_UPD, "Не получилось создать несуществующую еще папку для обновленного списка городов: "
                    . self::LIST_FOLDER_NEW);
            }
        }

        if (!file_put_contents(self::CITY_LIST_IDS_PATH_NEW, $jsons[0])) {
            Log::error(Log::DPD_CITY_UPD, "Не получилось сохранить файл: " . self::CITY_LIST_IDS_PATH_NEW);
            return;
        }

        if (!file_put_contents(self::CITY_LIST_CITIES_PATH_NEW, $jsons[1])) {
            Log::error(Log::DPD_CITY_UPD, "Не получилось сохранить файл: " . self::CITY_LIST_CITIES_PATH_NEW);
            return;
        }

        Log::info(Log::DPD_CITY_UPD, "Успешно сохранился обновленный список городов");
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
    private static function searchCitiesIds(string $query): array
    {

        if (DPD_CITY_LIST_SAFE_MODE) { // В безопасном режиме используем только проверенные данные
            $file = self::CITY_LIST_CITIES_PATH_SAFE;
        } else {
            $file = self::CITY_LIST_CITIES_PATH_NEW;
        }

        $input = file_get_contents($file);

        mb_convert_encoding($input, "UTF-8", "auto");

        $cityList = json_decode($input);

        if (empty($cityList)) {
            Log::critical(Log::DPD_CITY_FIND, "Файл с городами пуст: $file");
        }

        $returnArray = [];

        foreach ($cityList as $cityName => $cityIdsArray) {

            // Перед сравнением - переводим в нижний регистр
            $cityName = mb_strtolower($cityName);
            $query = mb_strtolower($query);

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
    private static function searchCitiesArray(array $cityIds): array
    {
        if (DPD_CITY_LIST_SAFE_MODE) {  // В безопасном режиме используем только проверенные данные
            $input = file_get_contents(self::CITY_LIST_IDS_PATH_SAFE);
        } else {
            $input = file_get_contents(self::CITY_LIST_IDS_PATH_NEW);
        }

        mb_convert_encoding($input, "UTF-8", "auto");

        $cityList = json_decode($input); // Возвращает StdClass

        $returnArray = [];

        foreach ($cityIds as $id) {
            $returnArray[] = $cityList->$id;
        }

        // Сортировка по алфавиту по типу населенного пункта
        $cityType = array_column($returnArray, 0);
        array_multisort($returnArray, SORT_ASC, $cityType);

        Log::debug(Log::DPD_CITY_FIND, "Вернули массив с " . count($returnArray));
        return $returnArray;
    }

    private static function searchInDadata(string $query): array
    {
        Log::debug(Log::DPD_CITY_FIND, "Поиск в Dadata: $query");

        $dadata = new \Dadata\DadataClient(DADATA_API_KEY, null);

        $fields = array(
            "locations" => [["country" => "Россия"]],
            "from_bound" => ["value" => "city"],
            "to_bound" => ["value" => "settlement"],
            "restrict_value" => true
        );

        $result = $dadata->suggest("address", $query, 5, $fields); #TODO self::MAX_CITY_COUNT_TO_RETURN

        Log::debug(Log::DPD_CITY_FIND, "Вернули массив с " . json_encode($result, JSON_UNESCAPED_UNICODE));


//        $result = json_decode($result);
//        Log::debug(Log::DPD_CITY_FIND, "После конвертации: " . json_encode($result, JSON_UNESCAPED_UNICODE));
        Log::debug(Log::DPD_CITY_FIND, gettype($result));
        Log::debug(Log::DPD_CITY_FIND, count($result));


        $returnArray = []; // Итоговый массив

        foreach ($result as $city) { // Собираем массив из городов, каждый элемент которого массив в виде: ["г", "Ялта", "Респ Крым"]
            Log::debug(Log::DPD_CITY_FIND, "Смотрим массив с городом: " . json_encode($city, JSON_UNESCAPED_UNICODE));
            Log::debug(Log::DPD_CITY_FIND, "Смотрим массив data: " . json_encode($city['data'], JSON_UNESCAPED_UNICODE));

            $abbreviation = $city['data']['city_type'] ?? $city['data']['settlement_type'];
            if (is_null($abbreviation)) {
                Log::critical(Log::DPD_CITY_FIND, "В ответе от Dadata при поиске города не обнаружили тип нас. пункта");
            }
            Log::debug(Log::DPD_CITY_FIND, "abbreviation: $abbreviation");
            $city = $city['data']['city'] ?? $city['data']['settlement'];
            Log::debug(Log::DPD_CITY_FIND, "city: $city");
            $region = $city['data']['region_with_type'];
            Log::debug(Log::DPD_CITY_FIND, "region: $region");

            $newArray = [$abbreviation, $city, $region];

            Log::debug(Log::DPD_CITY_FIND, "Array города: " . json_encode($newArray, JSON_UNESCAPED_UNICODE));

            $returnArray[] = $newArray;
        }

        Log::debug(Log::DPD_CITY_FIND, "Вернули массив: " . json_encode($returnArray, JSON_UNESCAPED_UNICODE));

        Log::info(Log::DPD_CITY_FIND, "Вернули массив с кол-во городов: " . count($returnArray));
        return $returnArray;
    }
}