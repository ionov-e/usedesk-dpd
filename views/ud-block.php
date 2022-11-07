<?php
/**
 * Содержимое для HTML-блока UseDesk
 *
 * @var $args array Аргументы извне. Сейчас выглядят: $ticketId => [[], []]
 * */
$ticketId = array_key_first($args);
$ticketArray = $args[$ticketId];
?>

    <div id="dpd-default-dynamic">
        <?php if (empty($ticketArray)) : ?>

            <div class="alert alert-warning" role="alert">
                Не было добавлено никаких заказов DPD
            </div>

        <?php else: ?>
            <h4>Созданные заказы DPD:</h4>

            <?php foreach ($ticketArray as $ttnArray): ?>

                <?php
                if (!empty($ttnArray[LAST_KEY_NAME])) {
                    switch ($ttnArray[LAST_KEY_NAME]) {
                        case LAST_DELIVERED:
                            $alertStyle = 'success';
                            break;
                        case LAST_NEW_ORDER_BY_CLIENT:
                        case LAST_LOST:
                        case LAST_PROBLEM:
                        case LAST_RETURNED_FROM_DELIVERY:
                        case LAST_NEW_ORDER_BY_DPD:
                            $alertStyle = 'danger';
                            break;
                        case LAST_NOT_DONE:
                            $alertStyle = 'warning';
                            break;
                        case LAST_ON_TERMINAL_PICKUP:
                        case LAST_ON_ROAD:
                        case LAST_ON_TERMINAL:
                        case LAST_ON_TERMINAL_DELIVERY:
                        case LAST_DELIVERING:
                            $alertStyle = 'info';
                            break;
                        default:
                            $alertStyle = 'minimal';
                    }
                } else {
                    switch ($ttnArray[STATE_KEY_NAME]) {
                        case ORDER_OK:
                            $alertStyle = 'info';
                            break;
                        case ORDER_PENDING:
                        case ORDER_CANCELED:
                            $alertStyle = 'warning';
                            break;
                        case ORDER_NOT_FOUND:
                        case ORDER_WRONG:
                        case ORDER_DUPLICATE:
                            $alertStyle = 'danger';
                            break;
                        case ORDER_DELETED:
                        default:
                            $alertStyle = 'minimal';
                    }
                }
                ?>

                <div class="alert alert-<?= $alertStyle ?>" role="alert">

                    <div>

                        <?php if (!empty($ttnArray[TTN_KEY_NAME])) : ?>
                            ТТН № "<b><?= $ttnArray[TTN_KEY_NAME] ?></b>"
                        <?php else: ?>
                            Внутренний № "<b><?= $ttnArray[INTERNAL_KEY_NAME] ?></b>"
                        <?php endif; ?>

                        — Добавлен <b><?= $ttnArray[DATE_KEY_NAME] ?></b>
                        . Статус: <b><?= $ttnArray[STATE_READABLE_KEY_NAME] ?></b>
                    </div>
                    <?php if (!in_array($ttnArray[LAST_KEY_NAME], [LAST_DELIVERED]) && !in_array($ttnArray[STATE_KEY_NAME], [ORDER_DELETED])) : // Если не пустое содержимое последнего статуса?>
                        <a class='btn btn-red' target="_blank"
                           href='<?= URL_SCRIPT_PHP ?>?<?= DELETE_TICKET_ID_KEY_NAME ?>=<?= $ticketId ?>&<?= INTERNAL_KEY_NAME ?>=<?= $ttnArray[INTERNAL_KEY_NAME] ?>'
                           onclick="document.querySelector('#dpd-default-dynamic').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">Отвязать
                            заказ от заявки</a>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <div id="dpd-after" style="display: none">
        <h4>Запрос отправлен. Нажмите кнопку для обновления данного блока:</h4>
        <button class="btn btn-info"
                onclick="document.querySelector('#dpd-after').closest('.dynamic-block').querySelector('a.block-reload-button').click()">
            Обновить статус
        </button>
    </div>

    <h4>Привязать новый заказ DPD:</h4>

<?php if (!RETURN_ORDER_MODE) : // Это для режима обычного заказа?>
    <div>
        <a class='btn btn-green' target=”_blank”
           href='<?= URL_SCRIPT_PHP ?>?<?= TICKET_ID_KEY_NAME ?>=<?= $ticketId ?>'>Оформить ТТН</a>
    </div>
<?php else : // Это для режима возврата ?>
    <form id="dpd-form" method="get" action="<?= URL_SCRIPT_PHP ?>" target="_blank">
        <div class="form-group">
            <label class="form-label">Внутренний номер посылки (разрешены латиница, цифры и дефис)</label>
            <input oninput="this.value=this.value.replace(/[^A-Za-z0-9-\s]/g,'');" name="<?= INTERNAL_KEY_NAME ?>"
                   type="text" class="form-control" value="" required>
        </div>
        <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $ticketId ?>">
        <div class="form-group">
            <button class='btn btn-success' type="submit"
                    onclick="document.querySelector('#dpd-form').style.display = 'none';document.querySelector('#dpd-alert').style.display = 'none';document.querySelector('#dpd-after').style.display = 'block'">
                Создать ТТН
            </button>
            <a href="#spoiler-1" data-toggle="collapse" class="btn btn-primary">Показать инструкцию привязки нового
                заказа</a>
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

<?php endif; ?>