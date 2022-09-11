<?php
/**
 * Эндпоинт и контроллер скрипта интеграции UseDesk - DPD
 */

use App\Handler\UseDeskHandler;
use App\Service\DpdCityList;

require_once "../vendor/autoload.php";
require_once "../config/global.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Get-запросы
    if (!empty($_GET[CITY_SEARCH_KEY_NAME])) {
        DpdCityList::searchCitiesJson(); // Get-запрос: из формы при вводе в поле город
    } elseif (!empty($_GET[DELETE_TICKET_ID_KEY_NAME])) {
        UseDeskHandler::deleteFromDb(); // Get-запрос: Переход на форму из HTML-блока (в Get: delete_ticket_id)
    } elseif (!empty($_GET[TICKET_ID_KEY_NAME])) {
        UseDeskHandler::generateFormForOrder(); // Get-запрос: Переход на форму из HTML-блока (в Get: ticket_id)
    } else {
        UseDeskHandler::generateNothing(); // Get-запрос: непредвиденный запрос
    }
} elseif (!empty($_POST[INTERNAL_KEY_NAME])) {
    UseDeskHandler::addCreatedReturnOrder(); // Post-запрос: форма из HTML-блока Usedesk на добавление созданного ТТН в БД
} elseif (!empty($_POST)) { // Post-запрос с содержанием формы
    UseDeskHandler::createDpdOrder();
} else { // Post-запрос (content-type: json) c ticketId для HTML-блока в ЮзДеске
    UseDeskHandler::generateUsedeskBlockHtml();
}
exit();