<?php

/**
 * @var string $ticketId ID тикета/запроса из ссылки
 */

$modifyDays = 1; #TODO Посмотреть на счет этого момента, когда минимальная отправка

#TODO Основательная переделка с использованием файла со всеми городами
?>

<!doctype html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>DPD Создание ТТН</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
        <link rel="stylesheet" href="/assets/css/style.css">
    </head>
    <body>
        <div class="container">
            <h1 class="text-center">Оформление заказа на доставку DPD</h1>
            <h4>Отправка произведется по тарифу DPD OPTIMUM. Вид доставки Двери-Двери</h4>
            <form action="" method="post" class="was-validated" enctype="multipart/form-data">
                <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $ticketId ?>">
                <div class="form-group">
                    <label for="senderAddress[datePickup]">Дата планируемой отгрузки:</label>
                    <input type="date"
                           min="<?php echo (new DateTime())->modify("+ {$modifyDays} days")->format("Y-m-d") ?>"
                           class="form-control" placeholder="Выберите дату отгрузки" name="senderAddress[datePickup]"
                           required>
                    <div id="my-listen-invalid" class="invalid-feedback">Обязательно для заполнения.</div>
                </div>
                <div class="form-group">
                    <label for="senderAddress[pickupTimePeriod]">Интервалы времени приёма</label>
                    <select name="senderAddress[pickupTimePeriod]" class="form-control">
                        <option>9-18</option>
                        <option>9-13</option>
                        <option>13-18</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="orderNumberInternal">Внутренний номер посылки</label>
                    <input name="orderNumberInternal" placeholder="220620-12312" type="text" class="form-control"
                           required>
                </div>

                <div class="form-group">
                    <label for="cargoNumPack">Количество посылок в отправке (если не заполнить - отправиться
                        "1")</label>
                    <input name="cargoNumPack" placeholder="1" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cargoWeight">Вес посылки (в кг)</label>
                    <input name="cargoWeight" placeholder="60" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="cargoVolume">Объем посылки (в метрах кубических)</label>
                    <input name="cargoVolume" placeholder="5" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="cargoValue">Оценочная стоимость посылки</label>
                    <input name="cargoValue" placeholder="60000" type="text" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="cargoCategory">Категория содержимого</label>
                    <input name="cargoCategory" placeholder="Товары" type="text" class="form-control">
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <h5>Отправитель</h5>
                    </div>
                    <div class="form-group col-md-6">
                        <h5>Получатель</h5>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[name]">Имя/Название организации</label>
                        <input name="senderAddress[name]" placeholder="Илья Отправитель" type="text"
                               class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[name]">Имя/Название организации (если не заполнить - отправится "ООО
                            'ФИРМЕННЫЕ РЕШЕНИЯ'")</label>
                        <input name="receiverAddress[name]" placeholder="ООО 'ФИРМЕННЫЕ РЕШЕНИЯ'" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[contactFio]">ФИО</label>
                        <input name="senderAddress[contactFio]" placeholder="Смирнов Игорь Николаевич" type="text"
                               class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[contactFio]">ФИО (если не заполнить - отправится "Сотрудник
                            склада")</label>
                        <input name="receiverAddress[contactFio]" placeholder="Сотрудник склада" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[contactPhone]">Контактный телефон</label>
                        <input name="senderAddress[contactPhone]" placeholder="89165555555" type="text"
                               class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[contactPhone]">Контактный телефон (если не заполнить - отправится
                            "244 68 04")</label>
                        <input name="receiverAddress[contactPhone]" placeholder="244 68 04" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[index]">Почтовый индекс</label>
                        <input name="senderAddress[index]" placeholder="103426" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[index]">Почтовый индекс</label>
                        <input name="receiverAddress[index]" placeholder="196642" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[city]">Город</label>
                        <input name="senderAddress[city]" id="senderAddressCity" placeholder="Люберцы" type="text"
                               class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[city]">Город (если не заполнить - отправится
                            "Петро-Славянка")</label>
                        <input name="receiverAddress[city]" placeholder="Петро-Славянка" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[region]">Регион</label>
                        <input name="senderAddress[region]" placeholder="Московская обл." type="text"
                               class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[region]">Регион (если не заполнить - отправится
                            "Санкт-Петербург")</label>
                        <input name="receiverAddress[region]" placeholder="Санкт-Петербург" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[street]">Наименование улицы</label>
                        <input name="senderAddress[street]" placeholder="Авиаторов" type="text" class="form-control"
                               required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[street]">Наименование улицы (если не заполнить - отправится
                            "Софийская")</label>
                        <input name="receiverAddress[street]" placeholder="Софийская" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[streetAbbr]">Аббревиатура улицы</label>
                        <input name="senderAddress[streetAbbr]" placeholder="ул" type="text" class="form-control"
                               required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[streetAbbr]">Аббревиатура улицы (если не заполнить - отправится
                            "ул")</label>
                        <input name="receiverAddress[streetAbbr]" placeholder="ул" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[house]">Номер дома</label>
                        <input name="senderAddress[house]" placeholder="1" type="text" class="form-control" required>
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[house]">Номер дома (если не заполнить - отправится "118", а в
                            корпусе "5")</label>
                        <input name="receiverAddress[house]" placeholder="118" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[houseKorpus]">Корпус</label>
                        <input name="senderAddress[houseKorpus]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[houseKorpus]">Корпус</label>
                        <input name="receiverAddress[houseKorpus]" placeholder="5" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[str]">Строение</label>
                        <input name="senderAddress[str]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[str]">Строение</label>
                        <input name="receiverAddress[str]" placeholder="" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[office]">Офис </label>
                        <input name="senderAddress[office]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[office]">Офис </label>
                        <input name="receiverAddress[office]" placeholder="" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[flat]">Квартира</label>
                        <input name="senderAddress[flat]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[flat]">Квартира</label>
                        <input name="receiverAddress[flat]" placeholder="" type="text" class="form-control">
                    </div>
                </div>
                <button id="my-listen-btn-submit" type="submit" name="submit" class="btn btn-primary my-btn-listen">
                    Отправить
                </button>
            </form>
        </div>

        <script>


            document.querySelector('#senderAddressCity').addEventListener('keyup', searching);

            function searching(e) {
                var keywordsStr = e.target.value;
                if (keywordsStr.length < 3) {
                    return;
                }
                fetch('http://areza.tech/usedesk-dpd.php?city_search=' + encodeURI(keywordsStr))
                    .then((response) => response.json())
                    // .then((data) => console.log(data));
                    .then((data) => {
                        let result = JSON.parse(data)
                    });
            }

            $(document).ready(function () {
                $('.preloader').hide();
            })


            $('.my-listen-btn').on('click', function () {
                $('.preloader').show();
            })

            $('#my-listen-btn-submit').on('click', function () {
                if ($('#my-listen-invalid').css('display') === 'none') {
                    $('.preloader').show();
                }
            })

        </script>
    </body>
</html>