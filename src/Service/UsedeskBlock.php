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
        $ttnArray = DB::getTtnArray($ticketId);

        if (empty($ttnArray)) { // Если для тикета еще ничего не создавалось - сразу возвращаем HTML
            return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => '']);
        }

        // Имеет смысл проверять изменения статуса только у этих статусов:
        if (in_array($ttnArray[STATE_KEY_NAME], [ORDER_OK, ORDER_PENDING, ORDER_UNCHECKED, ORDER_NOT_FOUND])) {
            $ttnArray = DpdOrder::checkOrder($ticketId, $ttnArray);
        }

        if (empty($ttnArray)) { // Если вернулся пустой массив - опять отрендерим буд-то в БД ничего и не было. Не запланированный случай, в лог сохраняется
            return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => '']);
        }

        $alertText = ''; // Содержимое alert блока (Bootstrap) для UD_BLOCK_NEW_VIEW (может быть пустым)

        switch ($ttnArray[STATE_KEY_NAME]) {
            case ORDER_OK: // Случай, если ТТН со статусом ОК
                return UsedeskBlock::renderPhp(self::UD_BLOCK_OK, [TTN_KEY_NAME => $ttnArray[TTN_KEY_NAME], DATE_KEY_NAME => $ttnArray[DATE_KEY_NAME], TICKET_ID_KEY_NAME => $ticketId]);
            case ORDER_PENDING: // Случай: OrderPending. Номер не ТТН, а внутренний передается
                return UsedeskBlock::renderPhp(self::UD_BLOCK_PENDING, [INTERNAL_KEY_NAME => $ttnArray[INTERNAL_KEY_NAME], DATE_KEY_NAME => $ttnArray[DATE_KEY_NAME], TICKET_ID_KEY_NAME => $ticketId]);
            case ORDER_DELETED:
                if (!empty($ttnArray[TTN_KEY_NAME])) {
                    $alertText = "Прошлая ТТН для этого запроса (№ {$ttnArray[TTN_KEY_NAME]}) <b>была откреплена</b>";
                } else { // Случай если ТТН еще не создался, т.е. был получен статус Pending перед удалением
                    $alertText = "Прошлый внутренний номер ({$ttnArray[INTERNAL_KEY_NAME]}) для этого запроса <b>был откреплен</b>";
                }
                break;
            case ORDER_CANCELED:
                if (!empty($ttnArray[TTN_KEY_NAME])) {
                    $alertText = "Прошлая ТТН для этого запроса (№ {$ttnArray[TTN_KEY_NAME]}) <b>была отменена</b>";
                } else { // Случай если ТТН еще не создался, т.е. был получен статус Pending перед удалением
                    $alertText = "Прошлый заказ на доставку ( {$ttnArray[INTERNAL_KEY_NAME]} ) <b>был отменен</b>";
                }
                break;
            case ORDER_WRONG:
                $alertText = "Для этого запроса был добавлен внутренний номер заказа '{$ttnArray[INTERNAL_KEY_NAME]}'. <b>Такой номер не существует</b>";
                break;
            case ORDER_NOT_FOUND:
                $alertText = "Для этого запроса был добавлен внутренний номер заказа '{$ttnArray[INTERNAL_KEY_NAME]}'. <b>В службе DPD такой номер не найден</b>";
                break;
            case ORDER_DUPLICATE:
                $alertText = "Для этого запроса был добавлен внутренний номер заказа '{$ttnArray[INTERNAL_KEY_NAME]}'. <b>Заказ с таким номером уже существует, создайте новый заказ с другим внутренним номером</b>";
                break;
        }
        return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId, ALERT_TEXT_KEY_NAME => $alertText]);
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