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
            <form action="" method="post" class="was-validated" enctype="multipart/form-data">
                <input type="hidden" name="<?=TICKET_ID_KEY_NAME?>" value="<?=$ticketId?>">
                <div class="form-group">
                    <label for="senderAddress[datePickup]">Дата планируемой отгрузки:</label>
                    <input type="date"
                           min="<?php echo (new DateTime())->modify("+ {$modifyDays} days")->format("Y-m-d") ?>"
                           class="form-control" id="senderAddress[datePickup]" placeholder="Выберите дату отгрузки"
                           name="senderAddress[datePickup]" required>
                    <div id="my-listen-invalid" class="invalid-feedback">Обязательно для заполнения.</div>
                </div>
                <div class="form-group">
                    <label for="orderNumberInternal">Внутренний номер посылки</label>
                    <input id="orderNumberInternal" placeholder="220620-12312" type="text" class="form-control">
                </div>

                <div class="form-group">
                    <label for="cargoNumPack">Количество мест в посылке</label>
                    <input id="cargoNumPack" placeholder="1" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cargoWeight">Вес посылки (в кг)</label>
                    <input id="cargoWeight" placeholder="60" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cargoVolume">Объем посылки (в метрах кубических)</label>
                    <input id="cargoVolume" placeholder="5" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cargoValue">Оценочная стоимость посылки</label>
                    <input id="cargoValue" placeholder="60000" type="text" class="form-control">
                </div>
                <div class="form-group">
                    <label for="cargoCategory">Категория содержимого</label>
                    <input id="cargoCategory" placeholder="Товары" type="text" class="form-control">
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
                        <input id="senderAddress[name]" placeholder="Илья Отправитель" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[name]">Имя/Название организации</label>
                        <input id="receiverAddress[name]" placeholder="ООО 'ФИРМЕННЫЕ РЕШЕНИЯ'" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[contactFio]">ФИО</label>
                        <input id="senderAddress[contactFio]" placeholder="Смирнов Игорь Николаевич" type="text"
                               class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[contactFio]">ФИО</label>
                        <input id="receiverAddress[contactFio]" placeholder="Сотрудник склада" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[contactPhone]">Контактный телефон</label>
                        <input id="senderAddress[contactPhone]" placeholder="89165555555" type="text"
                               class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[contactPhone]">Контактный телефон</label>
                        <input id="receiverAddress[contactPhone]" placeholder="244 68 04" type="text"
                               class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[city]">Город</label>
                        <input id="senderAddress[city]" placeholder="Люберцы" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[city]">Город</label>
                        <input id="receiverAddress[city]" placeholder="Петро-Славянка" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[region]">Регион</label>
                        <input id="senderAddress[region]" placeholder="Московская обл." type="text"
                               class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[region]">Регион</label>
                        <input id="receiverAddress[region]" placeholder="Санкт-Петербург" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[street]">Наименование улицы</label>
                        <input id="senderAddress[street]" placeholder="Авиаторов" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[street]">Наименование улицы</label>
                        <input id="receiverAddress[street]" placeholder="Софийская" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[streetAbbr]">Аббревиатура улицы</label>
                        <input id="senderAddress[streetAbbr]" placeholder="ул" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[streetAbbr]">Аббревиатура улицы</label>
                        <input id="receiverAddress[streetAbbr]" placeholder="ул" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[houseNo]">Номер дома</label>
                        <input id="senderAddress[houseNo]" placeholder="1" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[house]">Номер дома</label>
                        <input id="receiverAddress[house]" placeholder="118" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[houseKorpus]">Корпус</label>
                        <input id="senderAddress[houseKorpus]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[houseKorpus]">Корпус</label>
                        <input id="receiverAddress[houseKorpus]" placeholder="5" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[str]">Строение</label>
                        <input id="senderAddress[str]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[str]">Строение</label>
                        <input id="receiverAddress[str]" placeholder="" type="text" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[office]">Офис </label>
                        <input id="senderAddress[office]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[office]">Офис </label>
                        <input id="receiverAddress[office]" placeholder="" type="text" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col">
                        <label for="senderAddress[flat]">Квартира</label>
                        <input id="senderAddress[flat]" placeholder="" type="text" class="form-control">
                    </div>
                    <div class="form-group col">
                        <label for="receiverAddress[flat]">Квартира</label>
                        <input id="receiverAddress[flat]" placeholder="" type="text" class="form-control">
                    </div>
                </div>
                <button id="my-listen-btn-submit" type="submit" name="submit" class="btn btn-primary my-btn-listen">
                    Отправить
                </button>
            </form>
        </div>

        <script>

            $(document).ready(function () {
                $('.preloader').hide();
            })

            $("#senderAddress[datePickup]").on('change', function () {
                $("#my-listen-btn-submit").removeAttr('disabled');
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