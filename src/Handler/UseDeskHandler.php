<?php

namespace App\Handler;

use App\DB;
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
        Log::info(Log::UD_BLOCK, "Старт");

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
     * Добавляет в БД присланный внутренний номер заказа для возврата в DPD
     *
     * @return void
     */
    public static function addCreatedReturnOrder(): void
    {
        Log::info(Log::UD_ADD_TTN, "Старт");

        try {
            Log::error(Log::UD_ADD_TTN, "Было прислано: " . json_encode($_GET), JSON_UNESCAPED_UNICODE);
            $ticketId = $_GET[TICKET_ID_KEY_NAME];
            $internalId = $_GET[INTERNAL_KEY_NAME];
            DB::saveTicketToDb($ticketId, $internalId, ORDER_UNCHECKED, null, null, Log::UD_ADD_TTN);
            header(sprintf("Location: https://www.dpd.ru/return.do2?1002029585$%s", $internalId));
        } catch (\Exception $e) {
            Log::error(Log::UD_ADD_TTN, "Exception: " . $e->getMessage());
        }
    }

    /**
     * Создает ТТН в DPD используя данные из заполненной формы
     *
     * @return void
     */
    public static function createDpdOrder(): void
    {
        Log::info(Log::DPD_ORDER, "Старт");
        try {
            DpdOrder::createOrder();
        } catch (\Exception $e) { // Можно конечно отдельно обрабатывать SoapFault исключения
            Log::error(Log::DPD_ORDER, "Попытались создать ТТН. Получили Exception: " . $e->getMessage());
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
        Log::info(Log::DPD_FORM, "Старт");

        $ticketId = $_GET[TICKET_ID_KEY_NAME];

        Log::info(Log::DPD_FORM, "Прислан " . TICKET_ID_KEY_NAME . ": " . $ticketId);

        require PROJECT_DIR . "/views/dpd-create-order-form.php"; // Тут используется переменная $ticketId
    }

    /**
     * Удаляет присланный тикет из БД
     *
     * @return void
     */
    public static function deleteFromDb(): void
    {
        Log::info(Log::UD_DEL_TTN, "Старт");
        try {
            $error = false;
            $ticketId = $_GET[DELETE_TICKET_ID_KEY_NAME];
            $dataArrays = DB::getDbAsArray(Log::UD_DEL_TTN);
            if (!DB::changeTicketState($dataArrays, $ticketId, ORDER_DELETED, Log::UD_DEL_TTN)) {
                Log::warning(Log::UD_DEL_TTN, "Не был найден тикет: $ticketId");
                $error = true;
            }

            if (!$error && DB::overwriteDb($dataArrays, Log::UD_DEL_TTN)) {
                Log::info(Log::UD_DEL_TTN, "Успешно поменяли на удаленный статус тикету: $ticketId");
                echo "Успешно отвязана заказ DPD от заявки. Можете закрыть эту страницу"; #TODO Удалить после SSL
                return;
            }
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
        }

        $logMsg = "Не вышло удалить тикет: $ticketId";
        if (!empty($exceptionMsg)) {
            $logMsg .= ". Словили Exception: " . $exceptionMsg;
        }
        echo "Не удалось отвязать заказ DPD от заявки. Можете закрыть эту страницу. Если после обновления страницы заявки 
        в Usedesk и там все еще есть кнопка 'Отвязать заказ от заявки' - свяжитесь с разработчиками DPD-модуля для Usedesk"; #TODO Удалить после SSL
        Log::error(Log::UD_DEL_TTN, $logMsg);
    }

    /**
     * Выводит сообщение при запросе GET без необходимых параметров
     *
     * @return void
     */
    public static function generateNothing(): void
    {
        Log::info(Log::UNKNOWN, "Непредвиденный Get-запрос с содержимым: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));
        echo "Не были переданы все обязательные параметры";
    }
}