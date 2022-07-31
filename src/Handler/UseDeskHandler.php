<?php

namespace App\Handler;

use App\Helper\Input;
use App\Log;
use App\Service\DpdApi;

class UseDeskHandler
{

    /**
     * Ответ при переходе в UseDesk на страницу тикета (при включенном блоке)
     *
     * @return void
     */
    static function responseToBlock()
    {
        Log::info(Log::START, 'Ответ на Пост-запрос от UseDesk блока');

        header("Content-Type: application/json");
        try {
            $postTicketId = Input::getTicketId();

            $htmlString = self::getBlockHtml($postTicketId);

        } catch (\Exception $e) {
            Log::error(Log::INPUT, "Exception: " . $e->getMessage());
            $htmlString = 'Произошла ошибка'; #TODO может реальный 404?
        }

        echo json_encode(array('html' => $htmlString)); // Вывод web-блока UseDesk
        exit();
    }

    /**
     * Ответ при переходе со страницы тикета UseDesk на создание ТТН в DPD
     *
     * @return void
     */
    static function createOrder()
    {
        Log::info(Log::START, 'Форма прислана для отправки в DPD');

        $form = Input::getFormData();

        $ticketId = $form[TICKET_ID_KEY_NAME]; // Для лога

        $arRequest = Input::getDataToSendToCreateOrder($form);

        echo DpdApi::createOrder($ticketId, $arRequest);
    }

    /**
     * Выводит форму для создания заказа на отправку в DPD
     *
     * @return void
     */
    static function generateForm()
    {
        Log::info(Log::START, 'Переход из UseDesk на форму создания ТТН');

        $ticketId = $_GET[TICKET_ID_KEY_NAME];

        // Прекращаем выполнение, если айди тикета из адресной строки не найден
        if (empty($ticketId)) {
            Log::error(Log::INPUT, TICKET_ID_KEY_NAME . " не был найден");
            echo "Это страница 404 :)"; #TODO может реальный 404?
            exit();
        }

        Log::info(Log::INPUT, "Прислан " . TICKET_ID_KEY_NAME . ": " . $ticketId);

        echo require $_SERVER['DOCUMENT_ROOT'] . "../../views/dpd-create-order-form.php";
    }


    /**
     * Возвращает HTML-содержимое блока в интерфейсе UseDesk
     *
     * Временное решение до лучших идей (нужно вернуть как строку, с подменной переменных)  #TODO
     *
     * @param int $postTicketId
     *
     * @return string
     */
    private static function getBlockHtml(int $postTicketId)
    {
        $ticketIdKeyName = TICKET_ID_KEY_NAME;
        $urlScriptPhp = URL_SCRIPT_PHP;
        return "<a class='btn btn-green' href='$urlScriptPhp?$ticketIdKeyName=$postTicketId'>Оформить ТТН</a>";
    }
}