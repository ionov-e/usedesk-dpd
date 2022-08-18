<?php
/**
 * @var $args array Аргументы извне
 * */ ?>
<div class="alert alert-warning" role="alert">
    Заказ: <?= $args[TTN_JSON_KEY] ?> обрабатывается сотрудниками DPD. Обновление статуса придет сюда
</div>
<a class='btn btn-red' target=”_blank” href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Отвязать созданную ТТН</a>