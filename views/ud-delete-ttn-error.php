<?php
/**
 * Страница после удаления ТТН из "БД" в случае Ошибки
 *
 * @var $ticketId string ID Тикета для удаления
 * */ ?>
<!doctype html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>DPD Создание ТТН</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
              integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
              crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
                integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
                crossorigin="anonymous"></script>
        <link rel="stylesheet" href="<?= URL_SCRIPT_ROOT ?>/assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="alert alert-danger" role="alert">
                Не удалось удалить ТТН для тикета: <?= $ticketId ?>. <br> <br>
                Скорее всего уже удален. <br> <br>
                Если после обновления страницы тикета в Usedesk продолжаете видеть кнопку удаления ТТН и переход по ней
                (кнопке удаления) показывает лишь эту страницу ошибки – сообщите об этом разработчику используемого
                динамического блока Usedesk для DPD.
            </div>
        </div>
    </body>
</html>