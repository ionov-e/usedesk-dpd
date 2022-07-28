<?php

include_once "vendor/autoload.php";

use Dotenv\Dotenv;

const URL_DPD_DOMAIN = 'https://wstest.dpd.ru/';
const URL_GEOGRAPHY = URL_DPD_DOMAIN . "services/geography2?wsdl";
const URL_ORDER = URL_DPD_DOMAIN . "services/order2?wsdl";

const LOG_FOLDER_ROOT = 'log';                      // Произвольное имя папки для хранения логов
const TICKET_ID_KEY_NAME = 'ticket_id';             // Поле с ID тикета в теле Post от Юздеска


// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    printf("Error: %s in %s(%d)", $e->getMessage(), $e->getFile(), $e->getLine());
    exit(1);
}

define('CLIENT_NUMBER', $_ENV['CLIENT_NUMBER']);
define('CLIENT_KEY', $_ENV['CLIENT_KEY']);
define('URL_SCRIPT_DOMAIN', $_ENV['URL_SCRIPT_DOMAIN']);

/**
 * Преобразует объект с ответом в двумерный массив
 *
 * Функция взята из примера с сайта DPD
 *
 * @param $object
 *
 * @return array
 */
function stdToArray($object): array
{
    $array = (array)$object;
    foreach ($array as $key => $item) {
        $array[$key] = (array)$item;
        foreach ($array[$key] as $key2 => $item2) {
            $array[$key][$key2] = (array)$item2;
        }
    }
    return $array;
}

/**
 * Добавление строки в лог
 *
 * @param string $logString Строка для логирования
 *
 * @return void
 */
function logMsg(string $logString): void
{
    $logFolder = ".." . DIRECTORY_SEPARATOR . LOG_FOLDER_ROOT . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

    if (!is_dir($logFolder)) { // Проверяет создана ли соответствующая папка. Создает, если не существует
        mkdir($logFolder, 0770, true);
    }

    $logFileAddress = $logFolder . DIRECTORY_SEPARATOR . date('d') . '.log';

    $logString = date('H:i:s') . " > " . $logString . PHP_EOL;
    file_put_contents($logFileAddress, $logString, FILE_APPEND);
}