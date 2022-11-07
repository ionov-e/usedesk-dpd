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
define('CITY_LIST_SEARCH_MODE', (int)$_ENV['CITY_LIST_SEARCH_MODE']);
define('RETURN_ORDER_MODE', (int)$_ENV['RETURN_ORDER_MODE']);
define('LOG_MIN_LEVEL', (int)$_ENV['LOG_MIN_LEVEL']);

define('PROJECT_DIR', dirname(__DIR__, 1));              // Корневая папка

const INDEX_FILE_USEDESK = 'usedesk-dpd.php';                       // Только название файла (без папки) куда будут приходить запросы от UseDesk
const URL_SCRIPT_PHP = URL_SCRIPT_ROOT . DIRECTORY_SEPARATOR . INDEX_FILE_USEDESK; // URL путь к файлы выше
const LOG_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'log';  // Путь к папке для логов
const DATA_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'data';// Путь к папке для хранения данных
const DATA_JSON = DATA_FOLDER_ROOT . DIRECTORY_SEPARATOR . 'bd.json';// Здесь наша главная БД (с ID тикетов, № ТТН, Статусом ТТН и датой обновления/создания)

const TTN_KEY_NAME = 'ttn';                                         // Под таким ключом хранится в JSON созданное ТТН для Тикета
const STATE_KEY_NAME = 'state';                                     // Под таким ключом хранится в JSON статус создания ТТН для Тикета (может и не присутствовать)
const LAST_KEY_NAME = 'last';                                       // Под таким ключом хранится в JSON статус выполнения ТТН для Тикета (может и не присутствовать)
const INTERNAL_KEY_NAME = 'int';                                    // Под таким ключом хранится в JSON внутренний № заказа. И используется в GET запросе
const DATE_KEY_NAME = 'date';                                       // Под таким ключом хранится в JSON дата создания записи в БД
const TICKET_ID_KEY_NAME = 'ticket_id';                             // Название параметра в Post-запросе от UseDesk
const CITY_SEARCH_KEY_NAME = "city_search";                         // Название параметра в Get-запросе из формы при вводе в поле город
const DELETE_TICKET_ID_KEY_NAME = 'delete_ticket_id';               // Название параметра в Post-запросе для удаления из БД тикета с ТТН
const STATE_READABLE_KEY_NAME = 'state_readable';                   // Под таким ключом передаю во вьюшку читаемый статус

// Статусы создания заказа регламентированные в документации DPD
const ORDER_OK = 'OK';                              // Статус заказа (от DPD): OK
const ORDER_PENDING = 'OrderPending';               // Статус заказа (от DPD): принят, но на ручной доработке DPD (проблемный)
const ORDER_DUPLICATE = 'OrderDuplicate';           // Статус заказа (от DPD): дубликат - не принят
const ORDER_ERROR = 'OrderError';                   // Статус заказа (от DPD): ошибка. Точно будет поле errorMessage
const ORDER_CANCELED = 'OrderCancelled';            // Статус заказа (от DPD): отменен
// Статусы создания заказа нерегламентированные в документации DPD
const ORDER_UNCHECKED = 'unchecked';  // Изначально присваемый статус внутреннему № заказу полученного от пользователя
const ORDER_WRONG = 'wrong';          // Если последний присланный внутренний № заказ неверен (храним, чтобы пользователю показать, что проверили)
const ORDER_DELETED = 'deleted';      // Если пользователь решил удалить ТТН (тоже, чтобы показывать пользователю последний удаленный ТТН)
const ORDER_NOT_FOUND = 'no-data-found'; // Возвращает DPD при статус-чеке, если внутренний № заказа не найден у них

// Статусы выполнения заказа регламентированные в документации DPD
const LAST_NEW_ORDER_BY_CLIENT      = 'NewOrderByClient';       // оформлен новый заказ по инициативе клиента
const LAST_NOT_DONE                 = 'NotDone';                // заказ отменен
const LAST_ON_TERMINAL_PICKUP       = 'OnTerminalPickup';       // посылка находится на терминале приема отправления
const LAST_ON_ROAD                  = 'OnRoad';                 // посылка находится в пути (внутренняя перевозка DPD)
const LAST_ON_TERMINAL              = 'OnTerminal';             // посылка находится на транзитном терминале
const LAST_ON_TERMINAL_DELIVERY     = 'OnTerminalDelivery';     // посылка находится на терминале доставки
const LAST_DELIVERING               = 'Delivering';             // посылка выведена на доставку
const LAST_DELIVERED                = 'Delivered';              // посылка доставлена получателю
const LAST_LOST                     = 'Lost';                   // посылка утеряна
const LAST_PROBLEM                  = 'Problem';                // с посылкой возникла проблемная ситуация
const LAST_RETURNED_FROM_DELIVERY   = 'ReturnedFromDelivery';   // посылка возвращена с доставки
const LAST_NEW_ORDER_BY_DPD         = 'NewOrderByDPD';          // оформлен новый заказ по инициативе DPD


// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');

// Чтобы не было Notice-ов. Иначе на рабочем сервере не возвращался нормально JSON с Html содержимым для динамического блока Usedesk
ini_set('error_reporting', 'E_ALL & ~E_NOTICE;');