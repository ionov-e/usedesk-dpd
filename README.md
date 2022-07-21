# Скрипт связка Usedesk и DPD

## Описание

На данный момент скрипт выполняет очень конкретное действие. И планов расширять функционал пока нет.
Добавляет интеграцию в интерфейс сервиса [Usedesk](https://usedesk.ru) функционал для работы с [ТК DPD](https://www.dpd.ru/).
А именно:
- Оформление товарно-транспортной накладной (ТТН) со страницы тикета (заявки) внутри Usedesk
- Отображение связанной ТТН с выбранным тикетом

## Особенности использования скрипта

### Установка блока внутри Usedesk

1. Авторизуемся в Usedesk
2. Переходим на [страницу Блоки](https://secure.usedesk.ru/settings/blocks)
3. Убеждаемся, что среди списка предложенных уже нет созданного блока для доставки DPD
4. Нажимаем на кнопку "Добавить динамический блок"
5. Заполняем поля:
   - **Имя** - Произвольно выбираем. 
Например: "`DPD модуль`". Отображается лишь на странице Блоков (мы с этой страницы начинали установку)
   - **Заголовок** - Произвольно выбираем.
Например: "`DPD доставка`". Это поле будет отображаться в заголовке нашего создаваемого блока на странице каждого тикета (заявки) 
   - **Содержание блока** - Вписываем: `http://areza.tech/usedesk-dpd.php`
   - **Включен** - ставим галочку, если хотим включить. Убираем, если отключить

### Включение и отключение блока внутри Usedesk

1. Авторизируемся в Usedesk
2. Переходим на [страницу Блоки](https://secure.usedesk.ru/settings/blocks)
3. Находим среди уже созданных блоков искомый (в названии скорее всего будет фигурировать `DPD`)
4. В поле **Включен** - ставим галочку, если хотим включить. Убираем, если отключить


