<?php
/**
 * Маршрутизатор для вбудованого сервера PHP (php -S), який не читає .htaccess.
 * Забороняє доступ через браузер до службових папок з даними й кодом.
 * Запуск: php -S localhost:8000 -t . router.php
 */

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

if (preg_match('#^/(db|survey|inc|templates|php)(/|$)#i', $path) || preg_match('#/\.#', $path)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 — доступ заборонено';
    return true;
}

return false; // інші файли сервер віддає як звичайно (HTML, CSS, зображення, PHP-сторінки)
