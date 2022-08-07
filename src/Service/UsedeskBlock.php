<?php

/**
 *  Класс для генерирования содержимого блока HTML на странице тикета UseDesk
 */

namespace App\Service;

use App\Log;

class UsedeskBlock
{
    const UD_BLOCK_NEW_VIEW = PROJECT_DIR . '/views/ud-block-new.php';
    const UD_BLOCK_OK = PROJECT_DIR . '/views/ud-block-ok.php';

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
        $ttnArray = DpdOrder::checkOrder($ticketId);

        if ($ttnArray[STATE_JSON_KEY] == 'OK') {  // Случай, если ТТН со статусом ОК
            return UsedeskBlock::renderPhp(self::UD_BLOCK_OK, [TTN_JSON_KEY => $ttnArray[TTN_JSON_KEY]]);
        } elseif ($ttnArray[STATE_JSON_KEY] == 'OrderPending') {  // Случай: OrderPending. Номер не ТТН, а внутренний передается
            return UsedeskBlock::renderPhp(self::UD_BLOCK_OK, [TTN_JSON_KEY => $ttnArray[TTN_JSON_KEY]]);
        }
        // Случай, если ТТН не создано для тикета
        return UsedeskBlock::renderPhp(self::UD_BLOCK_NEW_VIEW, [TICKET_ID_KEY_NAME => $ticketId]);

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