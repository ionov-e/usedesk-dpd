<?php

namespace App\Helper;

use App\Log;

/**
 * Обрабатывает полученные данные от пользователя
 */

class Input
{

    /**
     * Возвращает ID Тикета, если находит внутри Post-запроса
     *
     * @return int
     *
     * @throws \Exception
     */
    static function getTicketId(): int
    {
        Log::debug(LOG::INPUT, "Пробуем получить  ID Тикета из Post-запроса");

        $errorMsg = 'ID Тикета не найден';

        try {
            $postJson = file_get_contents('php://input');
            $data = json_decode($postJson);
            $ticketId = intval($data->{TICKET_ID_KEY_NAME});
            if (!empty($ticketId)) { // Здесь может быть и "0" - нас это тоже не устраивает
                Log::info(LOG::INPUT,"ID Тикета:" . $ticketId);
                return $ticketId;
            }
        }  catch (\Exception $e) {
            $errorMsg .= ". Exception: " . $e->getMessage();
        }

        if (empty($postJson)) {
            Log::warning(LOG::INPUT,"Ничего не было прислано");
        } else {
            Log::warning(LOG::INPUT,"Были присланы данные:" . PHP_EOL . $postJson);
        }

        throw new \Exception($errorMsg);
    }
}