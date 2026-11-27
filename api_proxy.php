<?php
/**
 * AutoPoint — ЛР7 (бонус): проксі до API http://lab.vntu.org/api-server/lab7.php.
 * Сторінка api.html звертається до цього файлу на тому ж сервері, тому браузер
 * не блокує запит через CORS або змішаний HTTP/HTTPS-вміст.
 * Успіх: JSON від API без змін. Помилка: HTTP 502 і {"error": "...", "upstream_status": код}.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/http.php';   // ЛР8: спільна функція http_get()

const API_URL = 'http://lab.vntu.org/api-server/lab7.php';
const API_TIMEOUT = 10; // секунд

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function proxy_fail(string $message, int $upstreamStatus): never
{
    http_response_code(502);
    echo json_encode(['error' => $message, 'upstream_status' => $upstreamStatus], JSON_UNESCAPED_UNICODE);
    exit;
}

$res = http_get(API_URL, API_TIMEOUT);   // curl_exec() або file_get_contents() — див. inc/http.php
$status = $res['status'];
$body = $res['body'];

if ($status === 0) {
    proxy_fail('Немає з’єднання з сервером lab.vntu.org. ' . $res['error'], 0);
}
if ($status !== 200) {
    proxy_fail('Сервер lab.vntu.org повернув HTTP ' . $status . '.', $status);
}

json_decode($body);
if (json_last_error() !== JSON_ERROR_NONE) {
    proxy_fail('Сервер lab.vntu.org повернув не JSON.', $status);
}

echo $body; // передаємо JSON без змін
