# Реализация работы динамического блока Usedesk для DPD доставки (используя PHP)

## Описание

Скрипт реализует возможность создания и трекинга товарно-транспортной накладной (**ТТН**) созданной
в [ТК DPD](https://www.dpd.ru/) для каждого отдельного запроса/**тикета** в интерфейсе [Usedesk](https://usedesk.ru).

## Использование

Пользователь взаимодействует с работой этого проекта в 3 местах:

- [Динамический блок на странице тикета Usedesk](#Динамический-блок-на-странице-тикета-Usedesk)
- [Страница создания ТТН](#Страница-создания-ТТН)
- [Обновление списка городов от DPD](#Обновление-списка-городов-от-DPD)

#### Динамический блок на странице тикета Usedesk

На странице каждого тикета на сайте Usedesk справа в динамическом блоке отображается:

- **Случай, если еще не создавали ТТН для этого тикета из интерфейса:**

  Кнопка, ведущая на [страницу оформления ТТН](#Страница-создания-ттн) для DPD

  ![Динамический блок: нет ТТН](https://www.dropbox.com/s/khc0c8v3n4wp709/usedesk-block-0.png?dl=0)

- **Если уже создана ТТН, осуществив оформление в предыдущем пункте:**

  Отображение актуального статуса оформления ТТН или содержимое из прошлого пункта (если заказ был отменен DPD по
  каким-либо причинам)

  ![Динамический блок: Статус ТТН DPD - OK](https://www.dropbox.com/s/6a54ozzz3oanqj4/usedesk-block-1.png?dl=0)
  ![Динамический блок: Статус ТТН DPD - Pending](https://www.dropbox.com/s/v7idenbdt82uzux/usedesk-block-2.png?dl=0)

При открытии страницы тикета - запрос успевает отсылаться сразу на DPD, и вернуть статус заказа для перепроверки на
всякий случай. Т.е. пользователь видит актуальную информацию. Выходит, что пользователь либо увидит зеленый блок с
номером ТТН, либо желтый с уведомлением о внутренней доработке
заказа DPD (может быть, если введенный адрес не был одобрен системой DPD автоматически)

#### Страница создания ТТН

От пользователя требуется заполнить все необходимые поля (помечены звездочке в начале заголовка).

![Форма создания заказа DPD](https://www.dropbox.com/s/u50dsiriocv8m4w/usedesk-dpd-form.png?dl=0)

**Особенности:**

- Некоторые поля предзаполнены - можно изменить содержимое.
- При заполнении поля "Населенный пункт" ниже высветится список с подходящими к введенному запросу населенными пунктами.
  Пользователю необходимо будет кликнуть на подходящий ему выбор из списка (сделано для того, чтобы пользователь с
  меньшей вероятностью совершил ошибку в названии населенного пункта, его типа и названии региона)
- После заполнения всех полей пользователю следует нажать на кнопку внизу "Отправить". После чего он увидит ответ от DPD
    - 3 возможных варианта:
        - Успешное получение ТТН с выводом номера
        - Заказ на доработке DPD (если адрес автоматически ими не одобрен)
        - Произошла ошибка с выводом этой ошибки. Например, была введена несуществующая улица

      Первые 2 случая заносятся в базу. И при следующем посещении страницы тикета Usedesk - в соответствующем блоке
      будет отображаться актуальная информация о статусе ТТН. В последнем случае - пользователь может совершить
      оформление заново: либо обновив страницу, либо заново перейдя по кнопке оформления заказа из страницы тикета
      Usedesk.

#### Обновление списка городов от DPD

Использование этой возможности имеет смысл только в том случае, если выбран [режим поиска городов](#search_mode)
последний (использование актуального списка населенных пунктов с возможностью доставки курьером от DPD)

Переход по следующей ссылке произведет обновление автоматически и выведет на экран `Обновление успешно` при успешном
завершении.

`http://your.domain/dpd-update-city-list.php` - т.е. такой же адрес как и у основного выполняющегося
файла `usedesk-dpd.php`. Отличается лишь название файла.

На вышеуказанный адресс можно повесить CRON-задачу, либо на свое усмотрение раз в какой-то период перейти по ссылке -
больше ничего. На сервере обновятся или создадутся (если впервые запущен) файлы, где хранится актуальный вышеупомянутый
список.

## Особенности использования скрипта

### Установка на сервер

1. Выполняем `git clone https://github.com/ionov-e/usedesk-dpd.git .` в соответствующей созданной папке для проекта на
   своем хостинге/сервере
2. В настройках Nginx/Apache в файле конфигурации проекта указываем папку **public** как root. Например, для nginx
   строка вида: `root /var/www/your_folder/public;` (вместо пути "/var/www/your_folder/" - указываете собственный к
   проекту).
3. Зайти в папку с файлами и выполнить `composer update`
4. Перейти к следующему пункту - создание файла **.env**

### Создание/редактирование файла окружения **.env**

В содержимом репозитория есть файл **.env.example** - делаем его копию (в той же папке) с названием **.env**

Внутри файла **.env**:

- **CLIENT_NUMBER** - клиентский номер в DPD (номер договора, указан вверху-справа в личном кабинете DPD)
- **CLIENT_KEY** - уникальный ключ авторизации, полученный у сотрудника DPD. В примере (**.env.example**) для первых
  двух значений введены данные для доступа к тестовому аккаунту от DPD
- **URL_SCRIPT_DOMAIN** - URL-адрес откуда будут доступны вложенные в скрипт файлы (без '/' на конце)
- **URL_DPD_DOMAIN** - Сервер DPD. Первый - тестовый, второй - рабочий. Один закомментировать. Используется при создании
  ТТН и обновлении списка их городов (последнее выполняется лишь, если в следующем параметре **CITY_LIST_SEARCH_MODE**
  использовать последний режим)
- **CITY_LIST_SEARCH_MODE** <a id="search_mode" /> - 3 варианта поиска населенных пунктов в форме создания ТТН в DPD
    - **0 - используя базу городов и регионов от Dadata (дефолтный выбор)**

      (работает быстрее, с более умным поиском и сортировкой)
    - **1 - используя выкачанную и проверенную базу городов от DPD**

      (работает медленнее, неумная сортировка, зато
      уверенность, что в населенном пункте доступна доставка курьером. Правда, последнее преимущество немного
      сомнительно - наверно не потребуется доставка из населенного пункта без возможности обслуживания DPD курьером)
    - **2 - актуальную от DPD**

      (те же особенности, как и прошлом пункте, но с обновленной базой - обновляют практически каждый день. Но нет
      уверенности, что не изменятся условия предоставления этой базы населенных пунктов от DPD - может измениться
      предоставляемое соединение к FTP от DPD, может значительно измениться расположение/имя файла, кодировка, новое
      значение вдруг добавят. От того что добавят новую доступную автотрассу для доставки - вряд ли есть в этом польза
      для работы. Хотя на момент написания режим 100% полностью имплементирован и работоспособен. Если выбран этот
      режим, то хотя бы единоразово необходимо
      выполнить [Обновление списка городов от DPD](#Обновление-списка-городов-от-DPD))
- **DADATA_API_KEY** - Dadata персональный ключ. Необходим только если в **CITY_LIST_SEARCH_MODE** выбрать режим 0 (
  дефолтный выбор)
- **FTP_SERVER**, **FTP_USER**, **FTP_PASSWORD** - необходимы только если в **CITY_LIST_SEARCH_MODE** выбран последний
  режим)
- **LOG_MIN_LEVEL** - Минимальный порог для логирования. В файле (**.env.example**) перечислены варианты

### Создание динамического блока внутри Usedesk

1. Авторизуемся в Usedesk
2. Переходим на [страницу Блоки](https://secure.usedesk.ru/settings/blocks)

   ![Подключение в UseDesk Динамического Блока](https://www.dropbox.com/s/7i66wuej7d8n1dh/usedesk-blocks.png?dl=0)
4. Убеждаемся, что среди списка предложенных уже нет созданного блока для доставки DPD
5. Нажимаем на кнопку "Добавить динамический блок"
6. Заполняем поля:
    - **Имя** - Произвольно выбираем.
      Например: "`DPD доставка`". Отображается лишь на странице Блоков (мы с этой страницы начинали установку)
    - **Заголовок** - Произвольно выбираем.
      Например: "`DPD доставка`". Это поле будет отображаться в заголовке нашего создаваемого блока на странице каждого
      тикета (заявки)
    - **Содержание блока** - Оставляем пустым
    - **URL** - Путь к основному выполняемому файлу. Например: `http://your.domain/usedesk-dpd.php`
    - **Включен** - ставим галочку, если хотим включить. Убираем, если отключить

### Включение и отключение блока внутри Usedesk

1. Авторизируемся в Usedesk
2. Переходим на [страницу Блоки](https://secure.usedesk.ru/settings/blocks)
3. Находим среди уже созданных блоков искомый (в названии скорее всего будет фигурировать `DPD`)
4. В поле **Включен** - ставим галочку, если хотим включить. Убираем, если отключить