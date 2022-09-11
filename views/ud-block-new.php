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
    <form id="dpd-form" method="get" action="<?= URL_SCRIPT_PHP ?>" target="_blank">
        <div class="form-group">
            <label class="form-label">Внутренний номер посылки</label>
            <input name="<?= INTERNAL_KEY_NAME ?>" type="text" class="form-control" value="" required>
        </div>
        <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $args[TICKET_ID_KEY_NAME] ?>">
        <div class="form-group">
            <button class='btn btn-success' type="submit"
                    onclick="document.querySelector('#dpd-form').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">
                Создать ТТН
            </button>
            <a href="#spoiler-1" data-toggle="collapse" class="btn btn-primary">Показать инструкцию к использованию</a>
            <div class="collapse" id="spoiler-1">
                <div class="well">
                    <div class="list-group">
                        <li class="list-group-item">
                            <h4 class="list-group-item-heading">Если заказ еще не создан</h4>
                            <p class="list-group-item-text">
                            <ol>
                                <li>Введите в поле "Внутренний номер посылки"</li>
                                <li>Нажать кнопку "Создать ТТН"</li>
                                <li>Система в новой вкладке откроет для вас страницу заполнения формы для оформления
                                    возврата. Согласитесь на возможное предупреждение от вашего браузера
                                </li>
                                <li>Заполните форму оформления возврата</li>
                                <li>Все готово! После успешного заполнения формы DPD: либо заново откройте эту страницу,
                                    либо нажмите на появившуюся кнопку "Обновить статус"
                                </li>
                            </ol>
                            </p>
                        </li>
                        <li class="list-group-item">
                            <h4 class="list-group-item-heading">Заказ в DPD уже создан</h4>
                            <p class="list-group-item-text">
                                Введите </p>
                        </li>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <div id="dpd-after" style="display: none">
        <h3>После создания заявки на возврат нажмите кнопку</h3>
        <button onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
            Обновить статус
        </button>
    </div>

<?php endif; ?>

