<?php
/**
 * Эндпоинт и контроллер скрипта интеграции UseDesk - DPD
 * #TODO rename to index.php
 */

use App\Handler\UseDeskHandler;

require_once "../vendor/autoload.php";
require_once "../config/global.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Get-запрос: Переход на форму из HTML-блока (в Get: ticketID)
    UseDeskHandler::generateFormForOrder();
} elseif (!empty($_POST)) { // Post-запрос с содержанием формы
    UseDeskHandler::createDpdOrder();
} else { // Post-запрос (content-type: json) c ticketId для HTML-блока в ЮзДеске
    UseDeskHandler::generateUsedeskBlockHtml();
}
exit();