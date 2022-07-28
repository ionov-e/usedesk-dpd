<?php

include_once "../common.php";

main();

// ---------------------------------------------- Функции
/**
 * Главная функция
 *
 * @return void
 */
function main()
{
    header("Content-Type: application/json");
    // Отмечаем в логе начало работы
    logMsg(str_repeat("-", 20) . ' Ответ UseDesk блоку ' . str_repeat("-", 20));
    try {
        $postTicketId = getTicketId();
        $htmlString = getHtmlString($postTicketId);
    }  catch (Exception $e) {
        logMsg("!!! Error !!! : " . $e->getMessage());
        $htmlString = 'Произошла ошибка';
    }

    echo json_encode(array('html' => $htmlString)); // Вывод web-блока UseDesk
    exit();
}

/**
 * Возвращает ID Тикета, если находит внутри Post-запроса
 *
 * @return int
 *
 * @throws Exception
 */
function getTicketId(): int
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

    if (empty($postJson)) {
        logMsg("Ничего не было прислано");
    } else {
        logMsg("Были присланы данные:" . PHP_EOL . $postJson);
    }

    throw new Exception($errorMsg);
}

/**
 * Возвращает HTML содержимое для блока UseDesk
 *
 * @param string $postTicketId
 *
 * @return string
 */
function getHtmlString(string $postTicketId): string  #TODO в отдельный файл
{
    $domain = URL_SCRIPT_DOMAIN;
    $parameterName = TICKET_ID_KEY_NAME;
    return "<form><button class='btn btn-green' formaction='$domain/usedesk_create_order.php?$parameterName=$postTicketId'>Оформить ТТН</button></form>"; #TODO <a>

}
