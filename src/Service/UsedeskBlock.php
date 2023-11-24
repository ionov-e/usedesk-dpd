<?php

/**
 *  Класс для генерирования содержимого блока HTML на странице тикета UseDesk
 */

namespace App\Service;

use App\DB;
use App\Log;

class UsedeskBlock
{
    const UD_BLOCK_NEW_VIEW = PROJECT_DIR . '/views/ud-block-new.php';
    const UD_BLOCK_OK = PROJECT_DIR . '/views/ud-block-ok.php';
    const UD_BLOCK_PENDING = PROJECT_DIR . '/views/ud-block-pending.php';

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
        $ttns = DB::getTtnArray($ticketId);

        if (empty($ttns)) { // Если для тикета еще ничего не создавалось - сразу возвращаем HTML для создания заказа доставки
            return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => '']);
        }

        $alertText = []; // Содержимое alert блока (Bootstrap) для UD_BLOCK_NEW_VIEW (может быть пустым)

        $keys = array_keys($ttns);
        for ($i = 0; $i < count($keys); $i++){
            $ttn = $ttns[$keys[$i]];
            // Имеет смысл проверять изменения статуса СОЗДАНИЯ заказа только у этих статусов создания (в случае ОК - еще и статус выполнения возвращает):
            if (in_array($ttn[STATE_KEY_NAME], [ORDER_OK, ORDER_PENDING, ORDER_UNCHECKED, ORDER_NOT_FOUND])) {
                $ttn = DpdOrder::checkOrder($ticketId, $ttn);
            }

            // Если вернулся пустой массив - опять отрендерим буд-то в БД ничего и не было. Не запланированный случай, в лог сохраняется
//            if (empty($ttn)) {
//                return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => '']);
//            }

            switch ($ttn[STATE_KEY_NAME]) {
                case ORDER_OK: // Случай, если ТТН со статусом ОК
                    if (!empty($ttns[LAST_KEY_NAME])){ // Превращение статуса выполнения заказа в понятный вид (могли создать временную переменную, а не использовать элемент массива)
                        $ttn[LAST_KEY_NAME] = self::getLastStateReadable($ttns[LAST_KEY_NAME]);
                    }
                    $alertText[] = UsedeskBlock::renderPhp(self::UD_BLOCK_OK, [TTN_KEY_NAME => $ttns[TTN_KEY_NAME], DATE_KEY_NAME => $ttns[DATE_KEY_NAME], TICKET_ID_KEY_NAME => $ticketId, LAST_KEY_NAME => $ttns[LAST_KEY_NAME]]);
                    break;
                case ORDER_PENDING: // Случай: OrderPending. Номер не ТТН, а внутренний передается
                    $alertText[] = UsedeskBlock::renderPhp(self::UD_BLOCK_PENDING, [INTERNAL_KEY_NAME => $ttns[INTERNAL_KEY_NAME], DATE_KEY_NAME => $ttns[DATE_KEY_NAME], TICKET_ID_KEY_NAME => $ticketId]);
                    break;
                case ORDER_DELETED:
                    if (!empty($ttn[TTN_KEY_NAME])) {
                        $alertText[] = "Прошлая ТТН для этого запроса (№ {$ttns[TTN_KEY_NAME]}) <b>была откреплена</b>";
                    } else { // Случай если ТТН еще не создался, т.е. был получен статус Pending перед удалением
                        $alertText[] = "Прошлый внутренний номер ({$ttns[INTERNAL_KEY_NAME]}) для этого запроса <b>был откреплен</b>";
                    }
                    break;
                case ORDER_CANCELED:
                    if (!empty($ttn[TTN_KEY_NAME])) {
                        $alertText[] = "Прошлая ТТН для этого запроса (№ {$ttns[TTN_KEY_NAME]}) <b>была отменена</b>";
                    } else { // Случай если ТТН еще не создался, т.е. был получен статус Pending перед удалением
                        $alertText[] = "Прошлый заказ на доставку ( {$ttns[INTERNAL_KEY_NAME]} ) <b>был отменен</b>";
                    }
                    break;
                case ORDER_WRONG:
                case ORDER_NOT_FOUND:
                    $alertText[] = "Для этого запроса был добавлен внутренний номер заказа: {$ttns[INTERNAL_KEY_NAME]}<br><b>В службе DPD такой номер не найден</b>";
                    break;
                case ORDER_DUPLICATE:
                    $alertText[] = "Для этого запроса был добавлен внутренний номер заказа: {$ttns[INTERNAL_KEY_NAME]}<br><b>Заказ с таким номером уже существует, создайте новый заказ с другим внутренним номером</b>";
                    break;
            }
        }

//        return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => $alertText]);
        return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [BLOCKS => $alertText]);
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