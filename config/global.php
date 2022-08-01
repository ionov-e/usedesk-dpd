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

define('PROJECT_DIR', dirname(__DIR__, 1));


const URL_SCRIPT_PHP = URL_SCRIPT_DOMAIN . '/usedesk-dpd.php';  // URL на который должны приходить запросы от UseDesk
const LOG_FOLDER_ROOT = 'log';                                  // Название папки для логов
const TICKET_ID_KEY_NAME = 'ticket_id';                         // Такое используется в Post-запросе от UseDesk
const CITY_SEARCH_KEY_NAME = "city_search";                     // Используется в Get-запросе из формы при вводе в поле город

// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');