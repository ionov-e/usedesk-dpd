<?php
/**
 * Эндпоинт и контроллер скрипта интеграции UseDesk - DPD
 */

use App\Handler\UseDeskHandler;

require_once "../vendor/autoload.php";
require_once "../config/global.php";


if ($_SERVER['REQUEST_METHOD'] === 'GET') { // Get-запрос: Переход на форму из HTML-блока (в Get: ticketID)
    UseDeskHandler::generateForm();
} if (!empty($_POST)) { // Post-запрос с содержанием формы
    UseDeskHandler::createOrder();
} else { // Post-запрос c ticketId для HTML-блока в ЮзДеске
    UseDeskHandler::responseToBlock();
}