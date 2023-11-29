<?php

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__, 1));
    $dotenv->load();
} catch (Exception $e) {
    printf("Ошибка при подключении окружения: %s в файле %s(%d)", $e->getMessage(), $e->getFile(), $e->getLine());
    exit(1);
}

define('CLIENT_NUMBER', $_ENV['CLIENT_NUMBER']);
define('CLIENT_KEY', $_ENV['CLIENT_KEY']);
define('URL_SCRIPT_ROOT', $_ENV['URL_SCRIPT_ROOT']);
define('DADATA_API_KEY', $_ENV['DADATA_API_KEY']);
define('USEDESK_API_KEY', $_ENV['USEDESK_API_KEY']);
define('URL_DPD_DOMAIN', $_ENV['URL_DPD_DOMAIN']);
define('FTP_SERVER', $_ENV['FTP_SERVER']);
define('FTP_USER', $_ENV['FTP_USER']);
define('FTP_PASSWORD', $_ENV['FTP_PASSWORD']);
define('CITY_LIST_SEARCH_MODE', (int)$_ENV['CITY_LIST_SEARCH_MODE']);
define('RETURN_ORDER_MODE', (int)$_ENV['RETURN_ORDER_MODE']);
define('LOG_MIN_LEVEL', (int)$_ENV['LOG_MIN_LEVEL']);
define('PROJECT_DIR', dirname(__DIR__, 1));
/** Только название файла (без папки) куда будут приходить запросы от UseDesk */
const FILENAME_OF_INDEX_USEDESK = 'usedesk-dpd.php';
/** URL путь к исполняемому файлу юздеск */
const URL_TO_INDEX_USEDESK = URL_SCRIPT_ROOT . DIRECTORY_SEPARATOR . FILENAME_OF_INDEX_USEDESK;
const PATH_LOG_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'log';
/** Путь к папке для хранения данных */
const PATH_DATA_FOLDER_ROOT = PROJECT_DIR . DIRECTORY_SEPARATOR . 'data';
/** Здесь наша главная БД (с ID тикетов, № ТТН, Статусом ТТН и датой обновления/создания) */
const PATH_DATA_JSON = PATH_DATA_FOLDER_ROOT . DIRECTORY_SEPARATOR . 'bd.json';
/** Под таким ключом хранится в JSON созданное ТТН для Тикета */
const TTN_KEY_NAME = 'ttn';
/** Под таким ключом хранится в JSON статус создания ТТН для Тикета (может и не присутствовать) */
const STATE_KEY_NAME = 'state';
/** Под таким ключом хранится в JSON статус выполнения ТТН для Тикета (может и не присутствовать) */
const LAST_KEY_NAME = 'last';
/** Под таким ключом хранится в JSON внутренний № заказа. И используется в GET запросе */
const INTERNAL_KEY_NAME = 'int';
/** Под таким ключом хранится в JSON дата создания записи в БД */
const DATE_KEY_NAME = 'date';
/** Название параметра в Post-запросе от UseDesk */
const TICKET_ID_KEY_NAME = 'ticket_id';
/** Название параметра в Get-запросе из формы при вводе в поле город */
const CITY_SEARCH_KEY_NAME = "city_search";
/** Название параметра в Post-запросе для удаления из БД тикета с ТТН */
const DELETE_TICKET_ID_KEY_NAME = 'delete_ticket_id';
/** Под таким ключом передаю во вьюшку читаемый статус */
const STATE_READABLE_KEY_NAME = 'state_readable';

//-------------- Статусы создания заказа регламентированные в документации DPD

/** Статус заказа (от DPD): OK */
const ORDER_OK = 'OK';
/** Статус заказа (от DPD): принят, но на ручной доработке DPD (проблемный) */
const ORDER_PENDING = 'OrderPending';
/** Статус заказа (от DPD): дубликат - не принят */
const ORDER_DUPLICATE = 'OrderDuplicate';
/** Статус заказа (от DPD): ошибка. Точно будет поле errorMessage */
const ORDER_ERROR = 'OrderError';
/** Статус заказа (от DPD): отменен */
const ORDER_CANCELED = 'OrderCancelled';

//------------ Статусы создания заказа нерегламентированные в документации DPD

/** Изначально присваемый статус внутреннему № заказу полученного от пользователя */
const ORDER_UNCHECKED = 'unchecked';
/** Если последний присланный внутренний № заказ неверен (храним, чтобы пользователю показать, что проверили) */
const ORDER_WRONG = 'wrong';
/** Если пользователь решил удалить ТТН (тоже, чтобы показывать пользователю последний удаленный ТТН) */
const ORDER_DELETED = 'deleted';
/** Возвращает DPD при статус-чеке, если внутренний № заказа не найден у них */
const ORDER_NOT_FOUND = 'no-data-found';

//--------- Статусы выполнения заказа регламентированные в документации DPD

/** Оформлен новый заказ по инициативе клиента */
const LAST_NEW_ORDER_BY_CLIENT = 'NewOrderByClient';
/** Заказ отменен */
const LAST_NOT_DONE = 'NotDone';
/** Посылка находится на терминале приема отправления */
const LAST_ON_TERMINAL_PICKUP = 'OnTerminalPickup';
/** Посылка находится в пути (внутренняя перевозка DPD) */
const LAST_ON_ROAD = 'OnRoad';
/** Посылка находится на транзитном терминале */
const LAST_ON_TERMINAL = 'OnTerminal';
/** Посылка находится на терминале доставки */
const LAST_ON_TERMINAL_DELIVERY = 'OnTerminalDelivery';
/** Посылка выведена на доставку */
const LAST_DELIVERING = 'Delivering';
/** Посылка доставлена получателю */
const LAST_DELIVERED = 'Delivered';
/** Посылка утеряна */
const LAST_LOST = 'Lost';
/** С посылкой возникла проблемная ситуация */
const LAST_PROBLEM = 'Problem';
/** Посылка возвращена с доставки */
const LAST_RETURNED_FROM_DELIVERY = 'ReturnedFromDelivery';
/** Оформлен новый заказ по инициативе DPD */
const LAST_NEW_ORDER_BY_DPD = 'NewOrderByDPD';


// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
date_default_timezone_set('Europe/Moscow');

// Чтобы не было Notice-ов. Иначе на рабочем сервере не возвращался нормально JSON с Html содержимым для динамического блока Usedesk
ini_set('error_reporting', 'E_ALL & ~E_NOTICE;');