<?php
/**
 * Страница с формой для создания заказа в DPD
 *
 * @var string $ticketId ID тикета/запроса из ссылки
 */

$modifyDays = 1;
?>

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
            <h1 class="text-center">Оформление заказа на доставку DPD</h1>
            <h4>Отправка произведется по тарифу DPD OPTIMUM. Вид доставки Двери-Двери</h4>
            <form action="" method="post" class="was-validated custom-form">
                <input type="hidden" name="<?= TICKET_ID_KEY_NAME ?>" value="<?= $ticketId ?>">
                <div class="mb-3">
                    <label class="form-label" for="senderAddress[datePickup]"><strong>*</strong> Дата планируемой
                        отгрузки:</label>
                    <input type="date"
                           min="<?= $minDate = (new DateTime())->modify("+ {$modifyDays} days")->format("Y-m-d") ?>"
                           class="form-control" placeholder="Выберите дату отгрузки" name="senderAddress[datePickup]"
                           id="senderAddress[datePickup]" value="<?= $minDate ?>" required>
                    <div id="my-listen-invalid" class="invalid-feedback">Обязательно для заполнения.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="senderAddress[pickupTimePeriod]"><strong>*</strong> Интервалы времени
                        приёма</label>
                    <select class="form-select" name="senderAddress[pickupTimePeriod]"
                            id="senderAddress[pickupTimePeriod]">
                        <option>9-18</option>
                        <option>9-13</option>
                        <option>13-18</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="orderNumberInternal"><strong>*</strong> Внутренний номер посылки (не
                        больше 20 символов)</label>
                    <input name="orderNumberInternal" id="orderNumberInternal" placeholder="220620-12312" type="text"
                           maxlength="20" class="form-control" required>
                    <div class="invalid-feedback">Должно быть не больше 20 символов</div>
                </div>
                <div class="mb-3" hidden>
                    <label class="form-label" for="cargoNumPack"><strong>*</strong> Количество посылок в
                        отправке</label>
                    <input name="cargoNumPack" id="cargoNumPack" value="1" type="number" min="1" class="form-control">
                </div>
                <div class="mb-3" hidden>
                    <label class="form-label" for="cargoWeight"><strong>*</strong> Вес посылки (в кг)</label>
                    <input name="cargoWeight" id="cargoWeight" value="<?= DPD_ORDER_WEIGHT ?>" type="number" step="0.001" class="form-control"
                           required>
                </div>
                <div class="mb-3" hidden>
                    <label class="form-label" for="cargoVolume"><strong>*</strong> Объем посылки (в метрах
                        кубических)</label>
                    <input name="cargoVolume" id="cargoVolume" value="<?= DPD_ORDER_VOLUME ?>" type="number" step="0.01" class="form-control"
                           required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cargoValue"><strong>*</strong> Оценочная стоимость посылки</label>
                    <input name="cargoValue" id="cargoValue" placeholder="60000" type="number" step="0.01" class="form-control"
                           required>
                </div>
                <div class="mb-3" hidden>
                    <label class="form-label" for="cargoCategory"><strong>*</strong> Категория содержимого</label>
                    <input name="cargoCategory" id="cargoCategory" value="Товары" type="text" class="form-control">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Отправитель</h5>
                    </div>
                    <div class="col-md-6">
                        <h5>Получатель</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[name]"><strong>*</strong> Имя/Название организации</label>
                        <input name="senderAddress[name]" id="senderAddress[name]" placeholder="Илья Отправитель"
                               type="text" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[name]"><strong>*</strong> Имя/Название
                            организации </label>
                        <input name="receiverAddress[name]" id="receiverAddress[name]" value="ООО 'ФИРМЕННЫЕ РЕШЕНИЯ'"
                               type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[contactFio]"><strong>*</strong> ФИО</label>
                        <input name="senderAddress[contactFio]" id="senderAddress[contactFio]"
                               placeholder="Смирнов Игорь Николаевич" type="text" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[contactFio]"><strong>*</strong> ФИО</label>
                        <input name="receiverAddress[contactFio]" id="receiverAddress[contactFio]"
                               value="Сотрудник склада" type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[contactPhone]"><strong>*</strong> Контактный
                            телефон</label>
                        <input name="senderAddress[contactPhone]" id="senderAddress[contactPhone]"
                               placeholder="89165555555" type="tel" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[contactPhone]"><strong>*</strong> Контактный
                            телефон</label>
                        <input name="receiverAddress[contactPhone]" id="receiverAddress[contactPhone]" value="244 68 04"
                               type="tel" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[index]">Почтовый индекс</label>
                        <input name="senderAddress[index]" id="senderAddress[index]" placeholder="103426" type="number"
                               class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[index]">Почтовый индекс</label>
                        <input name="receiverAddress[index]" id="receiverAddress[index]" placeholder="196642"
                               type="number" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col" id="senderCityListParent">
                        <label class="form-label" for="senderCityFront"><strong>*</strong> Населенный пункт</label>
                        <input id="senderCityFront" placeholder="Люберцы" type="text" class="form-control" required>
                    </div>
                    <div class="col" id="receiverCityListParent">
                        <label class="form-label" for="receiverCityFront"><strong>*</strong> Город</label>
                        <input id="receiverCityFront" value="Петро-Славянка" type="text" class="form-control">
                    </div>
                </div>
                <input hidden name="senderAddress[city]" id="senderCity" type="text" class="form-control">
                <input hidden name="senderAddress[region]" id="senderRegion" type="text" class="form-control">
                <input hidden name="receiverAddress[city]" id="receiverCity" value="Петро-Славянка" type="text"
                       class="form-control">
                <input hidden name="receiverAddress[region]" id="receiverRegion" value="г Санкт-Петербург" type="text"
                       class="form-control">
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[street]"><strong>*</strong> Наименование
                            улицы</label>
                        <input id="senderAddress[street]" name="senderAddress[street]" placeholder="Авиаторов"
                               type="text" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[street]"><strong>*</strong> Наименование
                            улицы</label>
                        <input name="receiverAddress[street]" id="receiverAddress[street]" value="Софийская" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[streetAbbr]"><strong>*</strong> Аббревиатура улицы</label>
                        <input name="senderAddress[streetAbbr]" id="senderAddress[streetAbbr]" placeholder="ул"
                               type="text" class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[streetAbbr]"><strong>*</strong> Аббревиатура
                            улицы</label>
                        <input name="receiverAddress[streetAbbr]" id="receiverAddress[streetAbbr]" value="ул"
                               type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[house]"><strong>*</strong> Номер дома</label>
                        <input name="senderAddress[house]" id="senderAddress[house]" placeholder="1" type="text"
                               class="form-control" required>
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[house]"><strong>*</strong> Номер дома</label>
                        <input name="receiverAddress[house]" id="receiverAddress[house]" value="118" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[houseKorpus]">Корпус</label>
                        <input name="senderAddress[houseKorpus]" id="senderAddress[houseKorpus]" placeholder=""
                               type="text" class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[houseKorpus]">Корпус</label>
                        <input name="receiverAddress[houseKorpus]" id="receiverAddress[houseKorpus]" value="5"
                               type="text" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[str]">Строение</label>
                        <input name="senderAddress[str]" id="senderAddress[str]" placeholder="" type="text"
                               class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[str]">Строение</label>
                        <input name="receiverAddress[str]" id="receiverAddress[str]" placeholder="" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[office]">Офис </label>
                        <input name="senderAddress[office]" id="senderAddress[office]" placeholder="" type="text"
                               class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[office]">Офис </label>
                        <input name="receiverAddress[office]" id="receiverAddress[office]" placeholder="" type="text"
                               class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <label class="form-label" for="senderAddress[flat]">Квартира</label>
                        <input name="senderAddress[flat]" id="senderAddress[flat]" placeholder="" type="text"
                               class="form-control">
                    </div>
                    <div class="col">
                        <label class="form-label" for="receiverAddress[flat]">Квартира</label>
                        <input name="receiverAddress[flat]" id="receiverAddress[flat]" placeholder="" type="text"
                               class="form-control">
                    </div>
                </div>
                <button id="my-listen-btn-submit" type="submit" name="submit" class="btn btn-primary">
                    Отправить
                </button>
            </form>
        </div>

        <script>
            document.querySelector('#senderCityFront').addEventListener('keyup', searching);
            document.querySelector('#receiverCityFront').addEventListener('keyup', searching);

            function searching(e) {
                let keywordsStr = e.target.value;

                let ids = {};

                if (e.target.id === 'senderCityFront') {
                    ids = {
                        'City': 'senderCity',
                        'CityFront': 'senderCityFront',
                        'CityList': 'senderCityList',
                        'CityListParent': 'senderCityListParent',
                        'Region': 'senderRegion'
                    };
                }
                if (e.target.id === 'receiverCityFront') {
                    ids = {
                        'City': 'receiverCity',
                        'CityFront': 'receiverCityFront',
                        'CityList': 'receiverCityList',
                        'CityListParent': 'receiverCityListParent',
                        'Region': 'receiverRegion'
                    };
                }

                document.querySelector(`#${ids.CityFront}`).setCustomValidity("Not valid");

                if (keywordsStr.length < 3) {
                    return;
                }
                fetch('<?= INDEX_FILE_USEDESK ?>?<?= CITY_SEARCH_KEY_NAME ?>=' + encodeURI(keywordsStr))
                    .then((response) => response.json())
                    .then((data) => {

                        let previousDiv = document.querySelector(`#${ids.CityList}`);
                        if (previousDiv) {
                            previousDiv.remove();
                        }

                        if (data.length === 0) {
                            return;
                        }

                        let newDiv = document.createElement('div');
                        newDiv.setAttribute('id', `${ids.CityList}`);
                        document.querySelector(`#${ids.CityListParent}`).appendChild(newDiv);

                        let header = document.createElement('h5');
                        header.append("Выберите из списка:")
                        newDiv.appendChild(header);

                        let newUl = document.createElement('ul');
                        newUl.setAttribute('class', 'list-group');
                        newDiv.appendChild(newUl);

                        data.forEach((cityArray) => {
                                let newA = document.createElement('a');
                                newA.setAttribute('class', 'list-group-item list-group-item-action cursor-pointer');
                                newA.dataset.abrv = cityArray[0];
                                newA.dataset.city = cityArray[1];
                                newA.dataset.region = cityArray[2];
                                newA.append(`${cityArray[0]}. ${cityArray[1]} (${cityArray[2]})`);
                                document.querySelector(`#${ids.CityList}`).appendChild(newA);
                                newA.addEventListener('click', function () {
                                    document.querySelector(`#${ids.CityFront}`).value = `${this.dataset.abrv}. ${this.dataset.city} (${this.dataset.region})`;
                                    document.querySelector(`#${ids.City}`).value = this.dataset.city;
                                    document.querySelector(`#${ids.Region}`).value = this.dataset.region;
                                    document.querySelector(`#${ids.CityFront}`).setCustomValidity("");
                                    document.querySelector(`#${ids.CityList}`).remove();
                                });
                            }
                        );
                    });
            }
        </script>
    </body>
</html>