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


const URL_SCRIPT_PHP = URL_SCRIPT_DOMAIN . '/usedesk-dpd.php';  // URL на который должны приходить запросы от UseDesk
const LOG_FOLDER_ROOT = 'log';                                  // Название папки для логов
const TICKET_ID_KEY_NAME = 'ticket_id';                         // Такое используется в Post-запросе от UseDesk

// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');