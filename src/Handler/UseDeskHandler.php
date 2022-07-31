<?php

namespace App\Handler;

use App\Helper\InputHelper;
use App\Log;
use App\Service\DpdApi;

class UseDeskHandler
{

    /**
     * Возвращает HTML содержимое для отображения в UseDesk-е на страницах тикета (при включенном блоке)
     *
     * @return void
     */
    public static function generateUsedeskBlockHtml(): void
    {
        Log::info(Log::UD_BLOCK, 'Старт');

        header("Content-Type: application/json");
        try {
            $postTicketId = InputHelper::getTicketIdFromPostJson();

            $htmlString = self::getBlockHtml($postTicketId);

        } catch (\Exception $e) {
            Log::error(Log::UD_BLOCK, "Exception: " . $e->getMessage());
            $htmlString = 'Произошла ошибка'; #TODO может реальный 404?
        }

        echo json_encode(array('html' => $htmlString)); // Вывод web-блока UseDesk
    }

    /**
     * Создает ТТН в DPD используя данные из заполненной формы
     *
     * @return void
     */
    public static function createDpdOrder(): void
    {
        Log::info(Log::DPD_ORDER, 'Старт');

        $form = InputHelper::getFormData();

        $ticketId = $form[TICKET_ID_KEY_NAME];

        $arRequest = InputHelper::getDataToSendToCreateOrder($form);

        echo DpdApi::createOrder($ticketId, $arRequest);
    }

    /**
     * Выводит форму для создания заказа на отправку в DPD
     *
     * @return void
     */
    public static function generateFormForOrder(): void
    {
        Log::info(Log::DPD_FORM, 'Старт');

        $ticketId = $_GET[TICKET_ID_KEY_NAME];
        Log::info(Log::DPD_FORM, "Прислан " . TICKET_ID_KEY_NAME . ": " . $ticketId);

        // Прекращаем выполнение, если айди тикета из адресной строки не найден
        if (empty($ticketId)) {
            Log::error(Log::DPD_FORM, "Не был прислан " . TICKET_ID_KEY_NAME);
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