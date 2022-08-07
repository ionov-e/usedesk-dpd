<?php

namespace App\Handler;

use App\Log;
use App\Service\DpdOrder;
use App\Service\UsedeskBlock;

class UseDeskHandler
{

    /**
     * Возвращает HTML содержимое для отображения в UseDesk-е на страницах тикета (при включенном блоке)
     *
     * @return void
     */
    public static function generateUsedeskBlockHtml(): void
    {
        Log::info(Log::UD_BLOCK, "Старт. IP: " . $_SERVER["REMOTE_ADDR"]);

        header("Content-Type: application/json");
        try {
            $ticketId = UsedeskBlock::getTicketIdFromPostJson();
            $htmlString = UsedeskBlock::getBlockHtml($ticketId);

        } catch (\Exception $e) {
            Log::error(Log::UD_BLOCK, "Exception: " . $e->getMessage());
            $htmlString = 'Произошла ошибка';
        }

        echo json_encode(array('html' => $htmlString), JSON_UNESCAPED_UNICODE); // Вывод web-блока UseDesk
    }

    /**
     * Создает ТТН в DPD используя данные из заполненной формы
     *
     * @return void
     */
    public static function createDpdOrder(): void
    {
        Log::info(Log::DPD_ORDER, "Старт. IP: " . $_SERVER["REMOTE_ADDR"]);
        try {
            echo DpdOrder::createOrder();
        } catch (\SoapFault $e) {
            Log::error(Log::DPD_ORDER, "Попытались создать ТТН. Получили Exception: " . $e->getMessage());
        } catch (\Exception $e) {
            echo "Произошла ошибка";
        }
    }

    /**
     * Выводит форму для создания заказа на отправку в DPD
     *
     * @return void
     */
    public static function generateFormForOrder(): void
    {
        Log::info(Log::DPD_FORM, "Старт. IP: " . $_SERVER["REMOTE_ADDR"]);

        // Прекращаем выполнение, если айди тикета из адресной строки не найден
        if (empty($ticketId = $_GET[TICKET_ID_KEY_NAME])) {
            Log::warning(Log::DPD_FORM, "Не был прислан " . TICKET_ID_KEY_NAME);
            echo "Не были переданы все обязательные параметры";
            exit();
        }

        Log::info(Log::DPD_FORM, "Прислан " . TICKET_ID_KEY_NAME . ": " . $ticketId);

        echo require PROJECT_DIR . "/views/dpd-create-order-form.php"; // Тут используется переменная $ticketId
    }
}