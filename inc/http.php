<?php
/**
 * AutoPoint — ЛР8: HTTP-запити до інших додатків (REST API).
 * Якщо підключено розширення curl — використовується curl_exec(),
 * інакше — file_get_contents() з контекстом потоку і тайм-аутом.
 */

declare(strict_types=1);

/**
 * Виконує GET-запит і повертає масив:
 *   ok (bool), status (HTTP-код, 0 — відповіді немає), body (string), error (текст помилки), method (curl_exec|file_get_contents), time (сек.)
 */
function http_get(string $url, int $timeout = 10): array
{
    $start = microtime(true);
    $result = ['ok' => false, 'status' => 0, 'body' => '', 'error' => '', 'method' => '', 'time' => 0.0];

    if (function_exists('curl_init')) {
        $result['method'] = 'curl_exec';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,              // повернути відповідь рядком, а не вивести
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_USERAGENT      => 'AutoPoint-lab/1.0',
        ]);
        $body = curl_exec($ch);
        $result['status'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($body === false) {
            $result['error'] = 'curl: ' . curl_error($ch) . ' (код ' . curl_errno($ch) . ')';
        } else {
            $result['body'] = (string) $body;
        }
    } else {
        $result['method'] = 'file_get_contents';
        $context = stream_context_create(['http' => [
            'method'        => 'GET',
            'timeout'       => $timeout,
            'ignore_errors' => true,                     // отримати тіло і код навіть для 4xx/5xx
            'header'        => "Accept: application/json\r\nUser-Agent: AutoPoint-lab/1.0\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        // Заголовки відповіді: PHP 8.4+ — http_get_last_response_headers();
        // у старіших версіях — локальна змінна http_response_header (у PHP 8.5 її пряме використання застаріле)
        $headers = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : (get_defined_vars()['http_response_header'] ?? []);
        if (isset($headers[0]) && preg_match('#^HTTP/\S+\s+(\d{3})#', $headers[0], $m)) {
            $result['status'] = (int) $m[1];
        }
        if ($body === false) {
            $err = error_get_last();
            $result['error'] = 'file_get_contents: ' . ($err['message'] ?? 'немає з’єднання');
        } else {
            $result['body'] = $body;
        }
    }

    $result['ok'] = $result['error'] === '' && $result['status'] === 200;
    if ($result['error'] === '' && $result['status'] !== 200) {
        $result['error'] = 'Сервер повернув HTTP ' . $result['status'] . '.';
    }
    $result['time'] = round(microtime(true) - $start, 2);
    return $result;
}
