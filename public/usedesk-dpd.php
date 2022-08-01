<?php
/**
 * Эндпоинт и контроллер скрипта интеграции UseDesk - DPD
 * #TODO rename to index.php
 */

use App\Handler\UseDeskHandler;
use App\Service\DpdCityList;

require_once "../vendor/autoload.php";
require_once "../config/global.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Get-запросы
    if (!empty($_GET[CITY_SEARCH_KEY_NAME])) {
        DpdCityList::getCitiesJson(); // Get-запрос: из формы при вводе в поле город
    } else {
        UseDeskHandler::generateFormForOrder(); // Get-запрос: Переход на форму из HTML-блока (в Get: ticketID)
    }
} elseif (!empty($_POST)) { // Post-запрос с содержанием формы
    UseDeskHandler::createDpdOrder();
} else { // Post-запрос (content-type: json) c ticketId для HTML-блока в ЮзДеске
    UseDeskHandler::generateUsedeskBlockHtml();
}
exit();