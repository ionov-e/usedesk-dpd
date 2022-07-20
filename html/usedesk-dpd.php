<?php

const LOG_FOLDER_ROOT = 'log';                      // Произвольное имя папки для хранения логов
const TICKET_ID_KEY_NAME = 'ticket_id';             // Поле с ID тикета в теле Post от Юздеска


preConfig();
logOnScriptStart();
main();
exit();


// ---------------------------------------------- Функции
/**
 * Выполнение преднастроек скрипта
 *
 * @return void
 *
 * @throws ErrorException
 */
function preConfig(): void
{

// Установка часового пояса как в примере (где бы не выполнялся скрипт - одинаковое время)
    date_default_timezone_set('Europe/Moscow');

    header("Content-Type: application/json");
}

/**
 * Главная функция
 *
 * @return void
 */
function main() {
    try {
        $ticketId = findTicketId();
        $htmlString = "<form><button class='btn btn-green' formaction='http://mylink.com'>ID: $ticketId </button></form>";
    }  catch (Exception $e) {
        logMsg("!!! Error !!! : " . $e->getMessage());
        $htmlString = 'Произошла ошибка';
    }

    echo json_encode(array('html' => $htmlString)); // Вывод html-блока

}

/**
 * Возвращает ID Тикета, если находит внутри Post-запроса
 *
 * @return int
 *
 * @throws Exception
 */
function findTicketId(): int
{
    $errorMsg = 'ID Тикета не найден';

    try {
        $postJson = file_get_contents('php://input');
        $data = json_decode($postJson);
        $ticketId = intval($data->{TICKET_ID_KEY_NAME});
        if (!empty($ticketId)) { // Здесь может быть и "0" - нас это тоже не устраивает
            logMsg("ID Тикета:" . $ticketId);
            return $ticketId;
        }
    }  catch (Exception $e) {
        $errorMsg .= ". Exception: " . $e->getMessage();
    }
    throw new Exception($errorMsg);
}


/**
 * Ряд логирований при старте работы скрипта
 *
 * @return void
 */
function logOnScriptStart(): void
{

    $firstLine = str_repeat("-", 20) . ' Начало работы ' . str_repeat("-", 20);
    logMsg($firstLine);

    $postJson = file_get_contents('php://input');

    if (empty($postJson)) {
        logMsg("Ничего не было прислано");
    } else {
        logMsg("Были присланы данные:" . PHP_EOL . $postJson);
    }
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
    $logFolder = LOG_FOLDER_ROOT . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m');

    if (!is_dir($logFolder)) { // Проверяет создана ли соответствующая папка. Создает, если не существует
        mkdir($logFolder, 0770, true);
    }

    $logFileAddress = $logFolder . DIRECTORY_SEPARATOR . date('d') . '.log';

    $logString = date('H:i:s') . " > " . $logString . PHP_EOL;
    file_put_contents($logFileAddress, $logString, FILE_APPEND);
}