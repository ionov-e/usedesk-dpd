<?php

namespace App\Handler;

use App\DB;
use App\Log;
use App\Service\DpdOrder;
use App\Service\UsedeskBlock;

class UseDeskHandler
{

    const UD_DELETE_TTN_SUCCESS_PATH = PROJECT_DIR . '/views/ud-delete-ttn-success.php';
    const UD_DELETE_TTN_ERROR_PATH = PROJECT_DIR . '/views/ud-delete-ttn-error.php';

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
        Log::info(Log::DPD_FORM, "Старт. IP: " . $_SERVER["REMOTE_ADDR"]);

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
        Log::info(Log::UD_DEL_TTN, "Старт. IP: " . $_SERVER["REMOTE_ADDR"]);
        try {
            $error = false;
            $ticketId = $_GET[DELETE_TICKET_ID_KEY_NAME];
            $dataArrays = DB::getDbAsArray(Log::UD_DEL_TTN);
            if (!DB::removeTicketFromArray($dataArrays, $ticketId, Log::UD_DEL_TTN)) {
                Log::warning(Log::UD_DEL_TTN, "Не был найден тикет: $ticketId");
                $error = true;
            }

            if (!$error && DB::overwriteDb($dataArrays, Log::UD_DEL_TTN)) {
                Log::info(Log::UD_DEL_TTN, "Успешно удален тикет: $ticketId");
                include(self::UD_DELETE_TTN_SUCCESS_PATH);
                return;
            }
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
        }

        $logMsg = "Не вышло удалить тикет: $ticketId";
        if (!empty($exceptionMsg)) {
            $logMsg .= ". Словили Exception: " . $exceptionMsg;
        }
        Log::error(Log::UD_DEL_TTN, $logMsg);
        include(self::UD_DELETE_TTN_ERROR_PATH);
    }

    /**
     * Выводит сообщение при запросе GET без необходимых параметров
     *
     * @return void
     */
    public static function generateNothing(): void
    {
        Log::info(Log::UNKNOWN, "Непредвиденный Get-запрос с IP: " . $_SERVER["REMOTE_ADDR"] . " С содержимым: " . json_encode($_GET, JSON_UNESCAPED_UNICODE));
        echo "Не были переданы все обязательные параметры";
    }
}