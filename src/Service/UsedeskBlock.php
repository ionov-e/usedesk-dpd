<?php

/**
 *  Класс для генерирования содержимого блока HTML на странице тикета UseDesk
 */

namespace App\Service;

use App\Log;

class UsedeskBlock
{
    /**
     * Возвращает ID Тикета, если находит внутри Post-запроса
     *
     * @return int
     *
     * @throws \Exception
     */
    public static function getTicketIdFromPostJson(): int
    {
        Log::debug(LOG::UD_BLOCK, "Пробуем получить  ID Тикета из Post-запроса");

        $errorMsg = 'ID Тикета не найден'; // Переменная будет использоваться, только если не найден ID

        try {
            $postJson = file_get_contents('php://input');
            $data = json_decode($postJson);
            $ticketId = intval($data->{TICKET_ID_KEY_NAME});
            if (!empty($ticketId)) { // Здесь может быть и "0" - нас это тоже не устраивает
                Log::info(LOG::UD_BLOCK, "ID Тикета:" . $ticketId);
                return $ticketId;
            }
        } catch (\Exception $e) {
            $errorMsg .= $e->getMessage();
        }

        if (empty($postJson)) {
            Log::warning(LOG::UD_BLOCK, "Ничего не было прислано");
        } else {
            Log::warning(LOG::UD_BLOCK, "Вместо ID тикета Было прислано:" . PHP_EOL . $postJson);
        }

        throw new \Exception($errorMsg);
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
    public static function getBlockHtml(int $postTicketId): string
    {
        $ticketIdKeyName = TICKET_ID_KEY_NAME;
        $urlScriptPhp = URL_SCRIPT_PHP;
        return "<a class='btn btn-green' href='$urlScriptPhp?$ticketIdKeyName=$postTicketId'>Оформить ТТН</a>";
    }
}