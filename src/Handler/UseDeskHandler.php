<?php

namespace App\Handler;

use App\Helper\InputHelper;
use App\Log;
use App\Service\DpdApi;

class UseDeskHandler
{

    /**
     * Выодит на экран... #TODO добавить
     *
     * Ответ при переходе в UseDesk на страницу тикета (при включенном блоке)
     *
     * @return void
     */
    static function respondToBlock(): void
    {
        Log::info(Log::START, 'Ответ на Пост-запрос от UseDesk блока');

        header("Content-Type: application/json");
        try {
            $postTicketId = InputHelper::getTicketId();

            $htmlString = self::getBlockHtml($postTicketId);

        } catch (\Exception $e) {
            Log::error(Log::INPUT, "Exception: " . $e->getMessage());
            $htmlString = 'Произошла ошибка'; #TODO может реальный 404?
        }

        echo json_encode(array('html' => $htmlString)); // Вывод web-блока UseDesk
    }

    /**
     * Ответ при переходе со страницы тикета UseDesk на создание ТТН в DPD #TODO исправить
     *
     * @return void
     */
    static function createOrder(): void
    {
        Log::info(Log::START, 'Форма прислана для отправки в DPD');

        $form = InputHelper::getFormData();

        $ticketId = $form[TICKET_ID_KEY_NAME]; // Для лога

        $arRequest = InputHelper::getDataToSendToCreateOrder($form);

        echo DpdApi::createOrder($ticketId, $arRequest);
    }

    /**
     * Выводит форму для создания заказа на отправку в DPD
     *
     * @return void
     */
    static function generateForm(): void
    {
        Log::info(Log::START, 'Переход из UseDesk на форму создания ТТН');

        $ticketId = $_GET[TICKET_ID_KEY_NAME];
        Log::info(Log::INPUT, "Прислан " . TICKET_ID_KEY_NAME . ": " . $ticketId);

        // Прекращаем выполнение, если айди тикета из адресной строки не найден
        if (empty($ticketId)) {
            Log::error(Log::INPUT, "Не был прислан " . TICKET_ID_KEY_NAME);
            echo "Не были переданы все обязательные параметры";
        }

        echo require PROJECT_DIR . "/views/dpd-create-order-form.php";
    }


    /**
     * Возвращает HTML-содержимое блока в интерфейсе UseDesk
     *
     * Временное решение до лучших идей (нужно вернуть как строку, с подменной переменных)  #TODO референс - Yii2 проект
     *
     * @param int $postTicketId
     *
     * @return string
     */
    private static function getBlockHtml(int $postTicketId): string
    {
        $ticketIdKeyName = TICKET_ID_KEY_NAME;
        $urlScriptPhp = URL_SCRIPT_PHP;
        return "<a class='btn btn-green' href='$urlScriptPhp?$ticketIdKeyName=$postTicketId'>Оформить ТТН</a>";
    }
}