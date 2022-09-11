<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом Pending
 *
 * @var $args array Аргументы извне
 * */ ?>
<div class="alert alert-warning" role="alert">
    Заказ с внутренним №<?= $args[INTERNAL_KEY_NAME] ?> (от <?= $args[DATE_KEY_NAME] ?>) обрабатывается сотрудниками
    DPD. Обновление статуса придет сюда. Можно отвязать, чтобы создать новую заявку
</div>
<a class='btn btn-red'
         href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Отвязать заказ
    от заявки</a>