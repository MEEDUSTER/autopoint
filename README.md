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

Посилання відносні, зовнішніх CDN немає, тому переходи працюють офлайн.

## ЛР6 — PHP

GitHub Pages не виконує PHP, тому сторінки `survey.php` та `admin.php` працюють лише на PHP-сервері:

```
php -S localhost:8000 -t . router.php
```

На Windows: розпакуйте PHP 8.x (x64 Non Thread Safe, zip) у папку `php\` і запустіть `start-php.bat`.
Потрібні розширення: `pdo_sqlite`, `sqlite3`, `mbstring`. Файли анкет зберігаються в `survey/`, база — `db/survey.sqlite` (створюється автоматично).
