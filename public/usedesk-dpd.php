<?php
/**
 * Эндпоинт и контроллер скрипта интеграции UseDesk - DPD
 */

use App\Handler\UseDeskHandler;
use App\Log;
use App\Service\DpdCityList;

require_once "../vendor/autoload.php";
require_once "../config/global.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {     // Get-запросы
    if (!empty($_GET[CITY_SEARCH_KEY_NAME])) {
        DpdCityList::searchCitiesJson();        // Get-запрос: из "нашей" формы при вводе в поле Город. Возвращаем Json
    } elseif (!empty($_GET[DELETE_TICKET_ID_KEY_NAME])) {
        UseDeskHandler::deleteFromDb();         // Get-запрос: форма из HTML-блока Usedesk - на отвязывание "ТТН" от тикета
    } elseif (!empty($_GET[INTERNAL_KEY_NAME])) {
        UseDeskHandler::addCreatedReturnOrder(); // Get-запрос: форма из HTML-блока Usedesk - на добавление созданного ТТН в БД
    } elseif (!empty($_GET[TICKET_ID_KEY_NAME])) {
        UseDeskHandler::generateFormForOrder(); // Get-запрос: Переход из HTML-блока Usedesk - на страницу "нашей" формы создания обычной доставки DPD
    } else {
        UseDeskHandler::generateNothing(); // Get-запрос: непредвиденный запрос
    }
} elseif (!empty($_POST)) { // Post-запрос с содержанием "нашей" формы для оформления обычной доставки DPD
    UseDeskHandler::createDpdOrder();
} else {                    // Post-запрос (c json) при открытии станицы заявки Usedesk. Ждет ответ: HTML содержимое динамического блока (тоже json)
    UseDeskHandler::generateUsedeskBlockHtml();
}
exit();