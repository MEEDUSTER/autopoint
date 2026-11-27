@echo off
rem AutoPoint - ЛР6-ЛР8: запуск сайту з PHP на вбудованому сервері (Windows)
rem Портативний PHP має лежати в папці php\ поруч із цим файлом (php\php.exe).
chcp 65001 >nul
cd /d "%~dp0"

if not exist "php\php.exe" (
  echo [!] Не знайдено php\php.exe
  echo     Завантажте PHP 8.x для Windows x64 Non Thread Safe ^(zip^) з https://windows.php.net/download/
  echo     і розпакуйте вміст архіву в папку "%~dp0php"
  pause
  exit /b 1
)

set "PHP_ARGS=-d extension_dir="%~dp0php\ext""
if not exist "php\php.ini" (
  rem Якщо php.ini немає - вмикаємо потрібні розширення з командного рядка
  set "PHP_ARGS=%PHP_ARGS% -d extension=pdo_sqlite -d extension=sqlite3 -d extension=mbstring -d extension=curl"
)

echo AutoPoint: http://localhost:8000/survey.php   (адмін-панель: http://localhost:8000/admin.php)
echo ЛР7: http://localhost:8000/jokes.html   http://localhost:8000/api.html
echo ЛР8: http://localhost:8000/rest.php   http://localhost:8000/json.php   http://localhost:8000/preview.php
echo Щоб зупинити сервер, закрийте це вікно або натисніть Ctrl+C.
rem ЛР8: другий екземпляр сервера на порту 8001 - JSON-сервіс для preview.php.
rem Вбудований сервер PHP обробляє один запит одночасно, тому preview.php не може звернутися
rem сам до себе на порту 8000 (а PHP_CLI_SERVER_WORKERS у Windows не підтримується).
rem Сервер на 8001 працює у цьому ж вікні і зупиняється разом із ним.
start "AutoPoint JSON 8001" /b php\php.exe %PHP_ARGS% -S 127.0.0.1:8001 -t . router.php
start "" cmd /c "timeout /t 2 >nul & start http://localhost:8000/survey.php"
php\php.exe %PHP_ARGS% -S localhost:8000 -t . router.php
pause
