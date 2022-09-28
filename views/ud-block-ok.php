<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета в "БД" найден со статусом "ОК"
 *
 * @var $args array Аргументы извне
 * */ ?>
<div id="dpd-default-dynamic">
    <div class="alert alert-success" role="alert">
        Заказ DPD создан. Номер ТТН: <b><?= $args[TTN_KEY_NAME] ?></b> (от <?= $args[DATE_KEY_NAME] ?>)

        <br>Статус выполнения заказа: <b>
            <?php if (!empty($args[LAST_KEY_NAME])) :?>
                <?= $args[LAST_KEY_NAME] ?>
            <?php else: ?>
                Еще не прибыл на терминал DPD от отправителя
            <?php endif; ?>
        </b>
    </div>
    <?php if ($args[LAST_KEY_NAME] != "посылка доставлена получателю") : // Если не пустое содержимое последнего статуса?>
        <a class='btn btn-red' target="_blank"
           href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'
           onclick="document.querySelector('#dpd-default-dynamic').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">Отвязать
            заказ от заявки</a>
    <?php endif; ?>
</div>
<div id="dpd-after" style="display: none">
    <h4>Запрос на отвязку от заявки отправлена. Нажмите кнопку для обновления данного блока:</h4>
    <button class="btn btn-info"
            onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
        Обновить статус
    </button>
</div>