<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом "ОК"
 *
 * @var $args array Аргументы извне
 * */ ?>
<div id="dpd-default-dynamic">
    <div class="alert alert-success" role="alert">
        Номер ТТН: <b><?= $args[TTN_KEY_NAME] ?></b> (от <?= $args[DATE_KEY_NAME] ?>)
    </div>
    <a class='btn btn-red'
       href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'
       onclick="document.querySelector('#dpd-default-dynamic').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">Отвязать
        созданную ТТН</a>
</div>

<div id="dpd-after" style="display: none">
    <h3>Запрос на отвязку от заявки отправлена. Нажмите кнопку для обновления данного блока:</h3>
    <button onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
        Обновить статус
    </button>
</div>