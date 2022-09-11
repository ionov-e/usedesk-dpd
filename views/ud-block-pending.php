<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом Pending
 *
 * @var $args array Аргументы извне
 * */ ?>

<div id="dpd-default-dynamic">
    <div class="alert alert-warning" role="alert">
        Заказ с внутренним номером <b><?= $args[INTERNAL_KEY_NAME] ?></b> (от <b><?= $args[DATE_KEY_NAME] ?></b>)
        обрабатывается сотрудниками DPD. Обновление статуса придет сюда. Можно отвязать, чтобы создать новую заявку
    </div>
    <form method="get" action="<?= URL_SCRIPT_PHP ?>">
        <input type="hidden" name="<?= DELETE_TICKET_ID_KEY_NAME ?>" value="<?= $args[TICKET_ID_KEY_NAME] ?>">
        <button type="submit" class="btn btn-red" onclick="document.querySelector('#dpd-default-dynamic').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">Отвязать заказ от заявки</button>
    </form>
</div>

<div id="dpd-after" style="display: none">
    <h3>Запрос на отвязку от заявки отправлена. Нажмите кнопку для обновления данного блока:</h3>
    <button onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
        Обновить статус
    </button>
</div>