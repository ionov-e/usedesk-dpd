<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом "ОК"
 *
 * @var $args array Аргументы извне
 * */ ?>
<div class="alert alert-success" role="alert">
    Номер ТТН: <b><?= $args[TTN_KEY_NAME] ?></b> (от <?= $args[DATE_KEY_NAME] ?>)
</div>
<a class='btn btn-red' href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Отвязать созданную ТТН</a>