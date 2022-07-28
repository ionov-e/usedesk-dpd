<?php

// !!! Путь и название к настоящему файлу используется в файле для формирования HTML доп блока в UseDesk

include_once "../common.php";

// Первоначальная запись в лог
logMsg(str_repeat("-", 20) . ' Переход из UseDesk на форму ' . str_repeat("-", 20));

$ticketId = $_GET[TICKET_ID_KEY_NAME];

// Прекращаем выполнение, если айди тикета из адресной строки не найден
if (empty($ticketId)) {
    logMsg(TICKET_ID_KEY_NAME . " не был найден");
    echo "Это страница 404 :)";
    exit();
}


logMsg("В get был прислан " . TICKET_ID_KEY_NAME . " " . $ticketId);

include_once "dpd_send_form.php"; #TODO html-форму сюда подключаем вообще-то, а потом уже этот файл