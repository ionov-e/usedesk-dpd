<?php
/** Сюда прилетает крон-задача и выполняется апдейт статусов выполнения заказов.
 * Служит, чтобы предотвратить возможную ситуацию хранения более 90 дней не последнего статуса выполнения заказа
 * без проверки с сервером DPD.
 * Объяснение: Ведь без этого исполняемого скрипта получение статуса выполнения заказа от DPD происходит только при
 * каждом переходе на страницу тикета\заявки в UseDesk. А срок хранения статуса на сервере DPD 90 дней. Т.е. если
 * пользователь задет на страницу тикета в UseDesk и получит не последний статус выполнения заказа. А в следующий раз
 * зайдет на страницу тикета, к примеру, через 120 дней - он увидит последний полученный статус из БД. Ведь от сервера
 * уже статус никакой не получит.
 */

use App\Service\DpdOrder;

$pathToRootFolder = realpath(dirname(__FILE__, 2));

require_once $pathToRootFolder . "/vendor/autoload.php";
require_once $pathToRootFolder . "/config/global.php";

DpdOrder::updateProcessStates();

exit();