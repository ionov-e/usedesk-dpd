<?php

/**
 *  Класс для генерирования содержимого блока HTML на странице тикета UseDesk
 */

namespace App\Service;

use App\DB;
use App\Log;

class UsedeskBlock
{
    const UD_BLOCK_VIEW_PATH = PROJECT_DIR . '/views/ud-block.php';

    /**
     * Возвращает ID Тикета, если находит внутри Post-запроса
     *
     * @return int
     *
     * @throws \Exception
     */
    public static function getTicketIdFromPostJson(): int
    {
        Log::debug(Log::UD_BLOCK, "Пробуем получить  ID Тикета из Post-запроса");

        $errorMsg = 'ID Тикета не найден'; // Переменная будет использоваться, только если не найден ID

        try {
            $postJson = file_get_contents('php://input');
            $data = json_decode($postJson);
            $ticketId = intval($data->{TICKET_ID_KEY_NAME});
            if (!empty($ticketId)) { // Здесь может быть и "0" - нас это тоже не устраивает
                Log::info(Log::UD_BLOCK, "ID Тикета:" . $ticketId);
                return $ticketId;
            }
        } catch (\Exception $e) {
            $errorMsg .= $e->getMessage();
        }

        if (empty($postJson)) {
            Log::warning(Log::UD_BLOCK, "Ничего не было прислано");
        } else {
            Log::warning(Log::UD_BLOCK, "Вместо ID тикета Было прислано:" . PHP_EOL . $postJson);
        }

        throw new \Exception($errorMsg);
    }

    /**
     * Возвращает HTML-содержимое блока в интерфейсе UseDesk
     *
     * @param int $ticketId
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getBlockHtml(int $ticketId): string
    {
        $ticketArray = DB::getTicketArray($ticketId);

        foreach ($ticketArray as $key => $singleTtn) {

            // Имеет смысл проверять изменения статуса СОЗДАНИЯ заказа только у этих статусов создания (в случае ОК - еще и статус выполнения возвращает):
            if (in_array($singleTtn[STATE_KEY_NAME], [ORDER_PENDING, ORDER_UNCHECKED, ORDER_NOT_FOUND])
                || ($singleTtn[STATE_KEY_NAME] === ORDER_OK && !in_array($singleTtn[LAST_KEY_NAME], [LAST_DELIVERED, LAST_LOST, LAST_NEW_ORDER_BY_DPD]))) {
                $ticketArray[$key] = DpdOrder::checkOrder($ticketId, $singleTtn);
            }

            // Превращение статуса выполнения заказа в понятный вид
            if (!empty($ticketArray[$key][LAST_KEY_NAME])) {
                $ticketArray[$key][STATE_READABLE_KEY_NAME] = self::getLastStateReadable($ticketArray[$key][LAST_KEY_NAME]);
            } else { // Если пустой - будет отображать статус создания, а не прогресса доставки
                $ticketArray[$key][STATE_READABLE_KEY_NAME] = self::getCreationStateReadable($ticketArray[$key][STATE_KEY_NAME]);
            }

        }

        return UsedeskBlock::renderPhp(self::UD_BLOCK_VIEW_PATH, [$ticketId => $ticketArray]);
    }

    /**
     * Отправляет комментарий на страницу тикета в UseDesk
     *
     * @param int $ticketId
     * @param string $ttnNumber
     *
     * @return void
     */
    public static function postCommentToUsedesk(int $ticketId, string $ttnNumber): void
    {
        Log::debug(Log::UD_BLOCK, "Отправка комментария в UseDesk для тикета: $ticketId. ТТН №$ttnNumber");

        try {
            $data = array(
                'message' => "Успешно создан в DPD ТТН с номером: $ttnNumber",
                'user_id' => '169500',
                'from' => 'user',
                'ticket_id' => $ticketId,
                'type' => 'public',
                'api_token' => USEDESK_API_KEY,
            );
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, 'https://api.usedesk.ru/create/comment');
            curl_setopt($curl, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            $result = curl_exec($curl);

            $decoded = json_decode($result, true);

            if (empty($decoded['status']) || $decoded['status'] !== 'success') {
                Log::error(Log::UD_BLOCK, "Ошибка: Получили в ответ: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            } else {
                Log::info(Log::UD_BLOCK, "Отправлен комментарий в UseDesk для тикета: $ticketId");
            }

        } catch (\Exception $e) {
            Log::error(Log::UD_BLOCK, "Exception при отправки комментария в UseDesk: " . json_encode($result, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * Возвращает значение статуса выполнения заказа в понятном виде
     *
     * @param string $lastState
     *
     * @return string
     */
    private static function getLastStateReadable(string $lastState): string
    {
        switch ($lastState) {
            case LAST_NEW_ORDER_BY_CLIENT:
                return 'оформлен новый заказ по инициативе клиента';
            case LAST_NOT_DONE:
                return "заказ отменен";
            case LAST_ON_TERMINAL_PICKUP:
                return "посылка находится на терминале приема отправления";
            case LAST_ON_ROAD:
                return "посылка находится в пути (внутренняя перевозка DPD)";
            case LAST_ON_TERMINAL:
                return "посылка находится на транзитном терминале";
            case LAST_ON_TERMINAL_DELIVERY:
                return "посылка находится на терминале доставки";
            case LAST_DELIVERING:
                return "посылка выведена на доставку";
            case LAST_DELIVERED:
                return "посылка доставлена получателю";
            case LAST_LOST:
                return "посылка утеряна";
            case LAST_PROBLEM:
                return "с посылкой возникла проблемная ситуация";
            case LAST_RETURNED_FROM_DELIVERY:
                return "посылка возвращена с доставки";
            case LAST_NEW_ORDER_BY_DPD:
                return "оформлен новый заказ по инициативе DPD";
            default:
                Log::critical(Log::UD_BLOCK, "Непредвиденный статус выполнения заказа: $lastState");
                return "Не получилось узнать последний статус";
        }
    }

    private static function getCreationStateReadable(string $state): string
    {
        switch ($state) {
            case ORDER_OK:
                return "Еще не прибыл на терминал DPD от отправителя";
            case ORDER_PENDING:
                return "Обрабатывается сотрудниками DPD";
            case ORDER_DELETED:
                return "Откреплен"; #TODO Как-то реально удалять наверно
            case ORDER_CANCELED:
                return "Отменен";
            case ORDER_WRONG:
            case ORDER_NOT_FOUND:
                return "В DPD такой номер не найден";
            case ORDER_DUPLICATE:
                return "Заказ с таким номером уже существует, создайте новый";
            default:
                Log::critical(Log::UD_BLOCK, "Непредвиденный статус создания заказа: $state");
                return "Не получилось получить статус";
        }
    }

    /**
     * Возвращает отрендеренный PHP-файл. Можно в файл передать аргументы
     *
     * @param string $path
     * @param array $args
     *
     * @return string
     */
    private static function renderPhp(string $path, array $args = []): string
    {
        ob_start();
        include($path);
        $var = ob_get_contents();
        ob_end_clean();
        if (empty($var)) {
            Log::critical(Log::UD_BLOCK, "Не вышло отрендерить файл: $path с аргументами: " . json_encode($args, JSON_UNESCAPED_UNICODE));
        }
        return $var;
    }
}