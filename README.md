# AutoPoint — лабораторна робота №2

Статичний багатосторінковий сайт. Відкрий `index.html` у браузері і переходь меню зверху.

## Файли

| Файл | Призначення |
|---|---|
| index.html | Головна |
| about.html | Про сайт і навігацію |
| catalog.html | Каталог авто |
| service.html | Обслуговування |
| contacts.html | Контакти і карта сайту |
| css/style.css | Спільні стилі |
| animation.html, js/animation.js | ЛР5: анімовані елементи з керуванням кнопками |
| game.html, js/game.js | ЛР5 (бонус): міні-гра «Нічна траса» |
| css/js-lab.css | Стилі сторінок ЛР5 |
| survey.php | ЛР6: опитування (форма POST, валідація, файл у survey/, запис у SQLite) |
| admin.php, export.php, logout.php | ЛР6 (бонус): адмін-панель з входом, експорт CSV/JSON, видалення |
| inc/config.php, inc/functions.php, inc/db.php | ЛР6: налаштування, функції, робота з БД (PDO SQLite) |
| templates/*.php | ЛР6: шаблони шапки, підвалу, бокової панелі та форми |
| router.php, start-php.bat | ЛР6: маршрутизатор для php -S і запуск на Windows |
| css/survey.css | Стилі сторінок ЛР6 |
| jokes.html, js/jokes.js, jokes.txt | ЛР7: випадковий жарт із текстового файлу через XMLHttpRequest |
| survey_ajax.php, js/survey-ajax.js | ЛР7: надсилання анкети ЛР6 через XMLHttpRequest, відповідь у JSON |
| api.html, js/api.js, api_proxy.php | ЛР7 (бонус): динамічна таблиця з API lab.vntu.org через jQuery, оновлення і сортування |
| js/jquery.min.js | jQuery 3.7.1 (локальна копія, без CDN) |
| css/ajax.css | Стилі сторінок ЛР7 |
| rest.php, inc/rest.php | ЛР8: дані JSON з REST API lab.vntu.org (curl_exec / file_get_contents, json_decode, одна таблиця) |
| inc/http.php | ЛР8: функція http_get() — GET-запит через curl або file_get_contents з тайм-аутом |
| json.php | ЛР8 (бонус): анкети ЛР6 у форматі JSON (парсинг файлів survey/*.json, `?source=db` — з SQLite) |
| preview.php | ЛР8 (бонус): отримує JSON з json.php по HTTP і виводить HTML-таблицю |
| templates/sidebar_lab8.php, css/rest.css | ЛР8: бокова панель і стилі |

Посилання відносні, зовнішніх CDN немає, тому переходи працюють офлайн.

## ЛР6 — PHP

GitHub Pages не виконує PHP, тому сторінки `survey.php` та `admin.php` працюють лише на PHP-сервері:

```
php -S localhost:8000 -t . router.php
```

На Windows: розпакуйте PHP 8.x (x64 Non Thread Safe, zip) у папку `php\` і запустіть `start-php.bat`.
Потрібні розширення: `pdo_sqlite`, `sqlite3`, `mbstring`. Файли анкет зберігаються в `survey/`, база — `db/survey.sqlite` (створюється автоматично).

## ЛР7 — AJAX

- `jokes.html` — статична сторінка, працює і на GitHub Pages: XMLHttpRequest завантажує `jokes.txt` і показує випадковий жарт.
- `survey.php` — з увімкненим JavaScript форма надсилається через XMLHttpRequest у `survey_ajax.php` (JSON), без JavaScript — звичайним POST.
- `api.html` — таблиця з даними `http://lab.vntu.org/api-server/lab7.php` (jQuery). На PHP-сервері запит іде через `api_proxy.php`;
  на GitHub Pages (HTTPS) браузер не дозволяє звертатися до HTTP-API, тому повноцінно сторінка працює локально через `start-php.bat`.

## ЛР8 — REST API і JSON

- `rest.php` — GET-запит до `http://lab.vntu.org/api-server/lab8.php?user=student&pass=p%40ssw0rd`, `json_decode()` в об’єкти,
  усі записи про людей з усіх вкладених масивів — в один масив і в HTML-таблицю. Якщо API не відповідає — повідомлення з кодом помилки.
- `json.php` — усі анкети ЛР6 у JSON (масив, кожна анкета — окремий елемент). Містить особисті дані (ім’я, email),
  тому відповідає лише на запити з цього ж комп’ютера (127.0.0.1 / ::1) і не публікується на GitHub Pages (PHP там не виконується).
- `preview.php` — отримує JSON з `json.php` по HTTP і будує таблицю. Вбудований сервер PHP обробляє один запит одночасно,
  тому `start-php.bat` запускає другий екземпляр на `127.0.0.1:8001`, до якого звертається preview.php.
  Вручну: `php -S 127.0.0.1:8001 -t . router.php` в окремому вікні та `php -S localhost:8000 -t . router.php`.
