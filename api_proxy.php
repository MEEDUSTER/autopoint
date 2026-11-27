<?php
/**
 * AutoPoint — ЛР7 (бонус): проксі до API http://lab.vntu.org/api-server/lab7.php.
 * Сторінка api.html звертається до цього файлу на тому ж сервері, тому браузер
 * не блокує запит через CORS або змішаний HTTP/HTTPS-вміст.
 * Успіх: JSON від API без змін. Помилка: HTTP 502 і {"error": "...", "upstream_status": код}.
 */

declare(strict_types=1);

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

$status = 0;
$body = false;

if (function_exists('curl_init')) {
    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => API_TIMEOUT,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_USERAGENT      => 'AutoPoint-lab7/1.0',
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
} else {
    // запасний варіант без розширення curl
    $context = stream_context_create(['http' => [
        'timeout'       => API_TIMEOUT,
        'ignore_errors' => true,
        'header'        => "Accept: application/json\r\nUser-Agent: AutoPoint-lab7/1.0\r\n",
    ]]);
    $body = @file_get_contents(API_URL, false, $context);
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
}

if ($body === false || $status === 0) {
    proxy_fail('Немає з’єднання з сервером lab.vntu.org.', 0);
}
if ($status !== 200) {
    proxy_fail('Сервер lab.vntu.org повернув HTTP ' . $status . '.', $status);
}

json_decode($body);
if (json_last_error() !== JSON_ERROR_NONE) {
    proxy_fail('Сервер lab.vntu.org повернув не JSON.', $status);
}

echo $body; // передаємо JSON без змін
