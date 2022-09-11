<?php
/**
 * Содержимое для HTML-блока UseDesk в случае, если ТТН для тикета нет в "БД"
 *
 * @var $args array Аргументы извне
 * */ ?>

<?php if ($args[ALERT_TEXT_KEY_NAME]) : // Если есть что сообщить пользователю ?>
    <div id="dpd-alert" class="alert alert-warning" role="alert"><?= $args[ALERT_TEXT_KEY_NAME] ?></div>
<?php endif; ?>

<?php if (!RETURN_ORDER_MODE) : // Это для режима обычного заказа?>
    <div>
        <a class='btn btn-green' target=”_blank”
           href='<?= URL_SCRIPT_PHP ?>?<?= TICKET_ID_KEY_NAME ?>=<?= $args[TICKET_ID_KEY_NAME] ?>'>Оформить ТТН</a>
    </div>
<?php else : // Это для режима возврата ?>
    <form id="dpd-form" method="get" action="<?= URL_SCRIPT_PHP ?>" target="_blank">
        <div class="form-group">
            <label class="form-label">Внутренний номер посылки (разрешены латиница, цифры и дефис)</label>
            <input oninput="this.value=this.value.replace(/[^A-Za-z0-9-\s]/g,'');" name="<?= INTERNAL_KEY_NAME ?>" type="text" class="form-control" value="" required>
        </div>
        <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $args[TICKET_ID_KEY_NAME] ?>">
        <div class="form-group">
            <button class='btn btn-success' type="submit"
                    onclick="document.querySelector('#dpd-form').style.display = 'none';document.querySelector('#dpd-alert').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">
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
                                <li>Заполните поле "Внутренний номер посылки"</li>
                                <li>Нажать кнопку "Создать ТТН"</li>
                                <li>Система в новой вкладке откроет для вас страницу заполнения формы для оформления
                                    возврата. Если будет предупреждение от вашего браузера на незащищенный переход -
                                    согласитесь
                                </li>
                                <li>Заполните форму оформления возврата</li>
                                <li>Все готово! После успешного заполнения формы DPD: либо заново
                                    откройте эту страницу (на которой читаете эту инструкцию), либо (если не закрыли
                                    случайно эту страницу) нажмите на появившуюся кнопку "Обновить статус" (появляется
                                    после нажатия кнопки "Создать ТТН")
                                </li>
                            </ol>
                            </p>
                        </li>
                        <li class="list-group-item">
                            <h4 class="list-group-item-heading">Заказ в DPD уже создан и у вас есть внутренний №
                                заказа</h4>
                            <p class="list-group-item-text">
                            <ol>
                                <li>Заполните поле "Внутренний номер посылки"</li>
                                <li>Нажать кнопку "Создать ТТН"</li>
                                <li>Система в новой вкладке откроет для вас страницу заполнения формы для оформления
                                    возврата. Если будет предупреждение от вашего браузера на незащищенный переход -
                                    согласитесь
                                </li>
                                <li>В открывшейся странице DPD ничего не заполняйте, уже все готово! Либо заново
                                    откройте эту страницу (на которой читаете эту инструкцию), либо (если не закрыли
                                    случайно эту страницу) нажмите на появившуюся кнопку "Обновить статус" (появляется
                                    после нажатия кнопки "Создать ТТН")
                                </li>
                            </ol>
                            </p>
                        </li>
                        <li class="list-group-item">
                            <h4 class="list-group-item-heading">Заказ в DPD уже создан и у вас есть только номер
                                ТТН</h4>
                            <p class="list-group-item-text">
                            <ol>
                                <li>Чтобы получить "Внутренний номер посылки" к ТТН, который у вас уже есть - перейдите
                                    по ссылке <a target=”_blank” href='https://www.dpd.ru/ols/trace2/standard.do2'>https://www.dpd.ru/ols/trace2/standard.do2</a>
                                </li>

                                <li>В поле "Номер отправки" введите номер полученного ТТН и нажмите стрелочку справа для
                                    получения информации об отправке
                                </li>
                                <li>Из полученной таблицы скопируйте значение поля "Номер заказа Клиента"</li>
                                <li>Вернитесь на страницу заявки в Usedesk (страницу, на которой читаете эту инструкцию
                                    сейчас).
                                </li>
                                <li>Вставьте скопированное в поле "Внутренний номер посылки"</li>
                                <li>Нажать кнопку "Создать ТТН"</li>
                                <li>Система в новой вкладке откроет для вас страницу заполнения формы для оформления
                                    возврата. Если будет предупреждение от вашего браузера на незащищенный переход -
                                    согласитесь
                                </li>
                                <li>В открывшейся странице DPD ничего не заполняйте, уже все готово! Либо заново
                                    откройте эту страницу (на которой читаете эту инструкцию), либо (если не закрыли
                                    случайно эту страницу) нажмите на появившуюся кнопку "Обновить статус" (появляется
                                    после нажатия кнопки "Создать ТТН")
                                </li>
                            </ol>
                            </p>
                        </li>
                    </div>
                </div>
            </div>
        </div>
    </form>


    <div id="dpd-after" style="display: none">
        <h4>После создания заявки на возврат нажмите кнопку</h4>
        <button class="btn btn-info"
                onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
            Обновить статус
        </button>
    </div>

<?php endif; ?>

