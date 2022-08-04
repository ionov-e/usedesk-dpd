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
define('URL_SCRIPT_DOMAIN', $_ENV['URL_SCRIPT_DOMAIN']);
define('URL_DPD_DOMAIN', $_ENV['URL_DPD_DOMAIN']);
define('FTP_SERVER', $_ENV['FTP_SERVER']);
define('FTP_USER', $_ENV['FTP_USER']);
define('FTP_PASSWORD', $_ENV['FTP_PASSWORD']);
define('DPD_CITY_LIST_SAFE_MODE', $_ENV['DPD_CITY_LIST_SAFE_MODE']);

define('PROJECT_DIR', dirname(__DIR__, 1));              // Корневая папка

const URL_SCRIPT_PHP = URL_SCRIPT_DOMAIN . '/usedesk-dpd.php';      // URL на который должны приходить запросы от UseDesk
const LOG_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'log';  // Путь к папке для логов
const DATA_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'data';// Путь к папке для хранения данных
const DATA_JSON = DATA_FOLDER_ROOT. DIRECTORY_SEPARATOR . 'bd.json';// Здесь наша главное БД (с ID тикетов, № ТТН, Статусом ТТН)
const TTN_JSON_KEY = 'ttn';                                         // Под таким ключом хранится в JSON созданное ТТН для Тикета
const STATE_JSON_KEY = 'state';                                     // Под таким ключом хранится в JSON статус созданного ТТН для Тикета
const TICKET_ID_KEY_NAME = 'ticket_id';                             // Название параметра в Post-запросе от UseDesk
const CITY_SEARCH_KEY_NAME = "city_search";                         // Название параметра в Get-запросе из формы при вводе в поле город


// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');