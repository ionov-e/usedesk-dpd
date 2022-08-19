<?php

use Dotenv\Dotenv;

// Подключение констант из .env
try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__, 1));
    $dotenv->load();
} catch (\Exception $e) {
    printf("Ошибка при подключении окружения: %s в файле %s(%d)", $e->getMessage(), $e->getFile(), $e->getLine());
    exit(1);
}
define('CLIENT_NUMBER', $_ENV['CLIENT_NUMBER']);
define('CLIENT_KEY', $_ENV['CLIENT_KEY']);
define('URL_SCRIPT_ROOT', $_ENV['URL_SCRIPT_ROOT']);
define('DADATA_API_KEY', $_ENV['DADATA_API_KEY']);
define('URL_DPD_DOMAIN', $_ENV['URL_DPD_DOMAIN']);
define('FTP_SERVER', $_ENV['FTP_SERVER']);
define('FTP_USER', $_ENV['FTP_USER']);
define('FTP_PASSWORD', $_ENV['FTP_PASSWORD']);
define('CITY_LIST_SEARCH_MODE', $_ENV['CITY_LIST_SEARCH_MODE']);
define('LOG_MIN_LEVEL', $_ENV['LOG_MIN_LEVEL']);
define('DPD_ORDER_WEIGHT', $_ENV['DPD_ORDER_WEIGHT']);
define('DPD_ORDER_VOLUME', $_ENV['DPD_ORDER_VOLUME']);
define('DPD_ORDER_RECEIVER_CONTACT_NAME', $_ENV['DPD_ORDER_RECEIVER_CONTACT_NAME']);
define('DPD_ORDER_RECEIVER_COMPANY', $_ENV['DPD_ORDER_RECEIVER_COMPANY']);
define('DPD_ORDER_RECEIVER_PHONE', $_ENV['DPD_ORDER_RECEIVER_PHONE']);
define('DPD_ORDER_RECEIVER_EMAIL', $_ENV['DPD_ORDER_RECEIVER_EMAIL']);
define('DPD_ORDER_RECEIVER_REGION', $_ENV['DPD_ORDER_RECEIVER_REGION']);
define('DPD_ORDER_RECEIVER_CITY', $_ENV['DPD_ORDER_RECEIVER_CITY']);
define('DPD_ORDER_RECEIVER_STREET', $_ENV['DPD_ORDER_RECEIVER_STREET']);
define('DPD_ORDER_RECEIVER_STREET_ABBR', $_ENV['DPD_ORDER_RECEIVER_STREET_ABBR']);
define('DPD_ORDER_RECEIVER_HOUSE', $_ENV['DPD_ORDER_RECEIVER_HOUSE']);
define('DPD_ORDER_RECEIVER_HOUSE_KORPUS', $_ENV['DPD_ORDER_RECEIVER_HOUSE_KORPUS']);
define('DPD_ORDER_RECEIVER_HOUSE_STROENIE', $_ENV['DPD_ORDER_RECEIVER_HOUSE_STROENIE']);
define('DPD_ORDER_RECEIVER_OFFICE', $_ENV['DPD_ORDER_RECEIVER_OFFICE']);
define('DPD_ORDER_RECEIVER_KVARTIRA', $_ENV['DPD_ORDER_RECEIVER_KVARTIRA']);

define('PROJECT_DIR', dirname(__DIR__, 1));              // Корневая папка

const INDEX_FILE_USEDESK = 'usedesk-dpd.php';                       // Только название файла (без папки) куда будут приходить запросы от UseDesk
const URL_SCRIPT_PHP = URL_SCRIPT_ROOT . DIRECTORY_SEPARATOR . INDEX_FILE_USEDESK; // URL путь к файлы выше
const LOG_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'log';  // Путь к папке для логов
const DATA_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'data';// Путь к папке для хранения данных
const DATA_JSON = DATA_FOLDER_ROOT . DIRECTORY_SEPARATOR . 'bd.json';// Здесь наша главное БД (с ID тикетов, № ТТН, Статусом ТТН)
const TTN_JSON_KEY = 'ttn';                                         // Под таким ключом хранится в JSON созданное ТТН для Тикета
const STATE_JSON_KEY = 'state';                                     // Под таким ключом хранится в JSON статус созданного ТТН для Тикета
const INTERNAL_JSON_KEY = 'int';                                    // Под таким ключом хранится в JSON внутренний № заказа
const DATE_JSON_KEY = 'date';                                       // Под таким ключом хранится в JSON дата обновления/создания в БД. Обновление происходит только в случае смены статуса
const TICKET_ID_KEY_NAME = 'ticket_id';                             // Название параметра в Post-запросе от UseDesk
const CITY_SEARCH_KEY_NAME = "city_search";                         // Название параметра в Get-запросе из формы при вводе в поле город
const DELETE_TICKET_ID_KEY_NAME = 'delete_ticket_id';               // Название параметра в Get-запросе для удаления из БД тикета с ТТН


// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');

// Чтобы не было Notice-ов. Иначе на рабочем сервере не возвращался нормально JSON с Html содержимым для динамического блока Usedesk
ini_set('error_reporting', 'E_ALL & ~E_NOTICE;');