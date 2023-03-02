# callbackMangoTelecom
### Данный скрипт позволяет в автоматическом режиме совершать звонок клиенту из системы Битрикс24 посредством телефонии Mango Office

Скрипт автоматически запускается при создании сделки в Битрикс24. Для того, чтобы не ждать, пока кто-то из менеджеров возьмет сделку в работу, идет автоматический звонок группе менеджеров. Как только кто-то ответил на звонок, идет автоматический дозвон клиенту. 

**Механизм работы**:

1. При создании сделки автоматически запускается процесс. Если автоматизация запускается в первый раз, то звонок распределяется на группу стажеров. Если механизм запускается более 1 раза, то звонок уже распределяется на группу опытных сотрудников.
2. На тот случай, если вдруг по одной сделке запустилось 2 и более экзепляра бизнес-процесса обратного звонка, срабатывает прерывание процесса. Если прерывание не добавить, то звонок встанет в очередь согласно количеству запущенных экземпляров процесса.
3. Запускается проверка, что клиент связан со сделкой и у клиента есть номер телефона.
4. Посредством API Mango Office вызываем метод /vpbx/commands/callback_group, которой начинает звонить определенной группе менеджеров.

Решение может работать как на облачных, так и коробочных Битрикс24. 

**Как запустить**:
1. callback.php и auth.php необходимо разместить на хостинге с поддержкой SSL.
2. В разделе "Разработчикам" необходимо создать входящий вебхук с правами на CRM (crm). Подробнее как создать входящий / исходящий вебхук: [Ссылки на документацию 1С-Битрикс](https://github.com/thnik911/getCurrency#%D1%81%D1%81%D1%8B%D0%BB%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B4%D0%BE%D0%BA%D1%83%D0%BC%D0%B5%D0%BD%D1%82%D0%B0%D1%86%D0%B8%D1%8E-1%D1%81-%D0%B1%D0%B8%D1%82%D1%80%D0%B8%D0%BA%D1%81-%D0%B8-%D1%86%D0%B1-%D1%80%D1%84).
3. Полученный "Вебхук для вызова rest api" прописать в auth.php.
4. В строках 18 и 20 необходимо указать внутренние номера групп, на которые необходимо распределить звонок.
5. В строке 92 и 93 необходимо указать Уникальный ключ Вашей АТС и Ключ для создания подписи. Где найти ключи: [Ссылки на документацию 1С-Битрикс](https://github.com/thnik911/getCurrency#%D1%81%D1%81%D1%8B%D0%BB%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B4%D0%BE%D0%BA%D1%83%D0%BC%D0%B5%D0%BD%D1%82%D0%B0%D1%86%D0%B8%D1%8E-1%D1%81-%D0%B1%D0%B8%D1%82%D1%80%D0%B8%D0%BA%D1%81-%D0%B8-%D1%86%D0%B1-%D1%80%D1%84)
6. Делаем POST запрос посредством конструкции Webhook* через робот, или бизнес-процессом: https://yourdomain.com/path/callbackMangoTelecom.php?deal=123&cnt=456&phone=79001234567&prioritet=2

Переменные передаваемые в POST запросе:

yourdomain.com - адрес сайта, на котором размещены скрипты auth.php и invioceAdd.php с поддержкой SSL.

path - путь до скрипта.

deal - ID сделки.

phone - Телефон, который хранится в карточке контакта

prioritet - Если процесс запускается первый раз, то передаем 2, чтобы звонок распределился на стажеров. Если процесс запускается более 1 раза, то можно передать любое число, которое отличается от 1.

### Ссылки на документацию 1С-Битрикс и Mango Office

<details><summary>Развернуть список</summary>

1. Действие Webhook внутри Бизнес-процесса / робота https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=57&LESSON_ID=8551
2. Как создать Webhook https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=8581&LESSON_PATH=8771.8583.8581
3. Документация по работе API Mango Office: https://www.mango-office.ru/upload/medialibrary/68c/MangoOffice_VPBX_API_v1.9.pdf
</details>
