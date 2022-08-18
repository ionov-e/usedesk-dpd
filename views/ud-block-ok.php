<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом "ОК"
 *
 * @var $args array Аргументы извне
 * */ ?>
<div class="alert alert-success" role="alert">
    Номер ТТН: <?= $args[TTN_JSON_KEY] ?>
</div>
<a class='btn btn-red' target=”_blank” href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Отвязать созданную ТТН</a>