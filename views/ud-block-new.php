<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета нет в "БД"
 *
 * @var $args array Аргументы извне
 * */ ?>

<?php if ($args[ALERT_TEXT_KEY_NAME]) : // Если есть что сообщить пользователю ?>
    <div class="alert alert-warning" role="alert"><?= $args[ALERT_TEXT_KEY_NAME] ?></div>
<?php endif; ?>

<?php if (!RETURN_ORDER_MODE) : // Это для режима обычного заказа?>
    <div>
        <a class='btn btn-green' target=”_blank”
           href='<?= URL_SCRIPT_PHP ?>?<?= TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Оформить ТТН</a>
    </div>
<?php else : // Это для режима возврата ?>
    <form id="dpd-form" method="post" action="<?= URL_SCRIPT_PHP ?>" target="_blank">
        <div class="input-group">
            <span class="input-group-text">Внутренний номер посылки</span>
            <input name="<?= INTERNAL_KEY_NAME ?>" type="text" class="form-control" value="" required>
        </div>
        <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $args[TICKET_ID_KEY_NAME] ?>">
        <button class='btn btn-green' type="submit" onclick="document.querySelector('#dpd-form').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">Создать ТТН</button>
    </form>
    <div id="dpd-after" style="display: none">
       <h3>После создания заявки на возврат нажмите кнопку</h3>
       <button onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">Обновить статус</button>
    </div>

<?php endif; ?>

