<?php
/**
 * AutoPoint — ЛР7: AJAX-обробник анкети. Приймає FormData методом POST
 * (надсилає js/survey-ajax.js через XMLHttpRequest) і повертає JSON:
 *   успіх:   {"ok": true, "filled_at": "...", "filled_at_text": "...", "name": "...", "file": "survey/...json", "db": true, "answers": [...], "count": N}
 *   помилки: {"ok": false, "errors": {"поле": "текст помилки"}, "csrf": "..."} з кодом 422 (валідація) або 403 (CSRF).
 * Логіка збереження та сама, що й у survey.php — функція process_survey() з inc/functions.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(['ok' => false, 'errors' => ['form' => 'Дозволено лише метод POST.']], 405);
}

try {
    $res = process_survey($_POST);
} catch (Throwable $e) {
    error_log('Survey error: ' . $e->getMessage());
    json_response(['ok' => false, 'errors' => ['form' => 'Не вдалося зберегти анкету. Спробуйте пізніше.']], 500);
}

if (!$res['ok']) {
    json_response([
        'ok'     => false,
        'errors' => $res['errors'],
        'csrf'   => csrf_token(),           // актуальний токен — форму можна надіслати повторно
    ], $res['status']);
}

json_response([
    'ok'             => true,
    'errors'         => [],
    'filled_at'      => date('Y-m-d H:i:s', $res['time']),
    'filled_at_text' => format_date_uk($res['time']) . ', ' . date('H:i:s', $res['time']),
    'name'           => $res['data']['name'],
    'file'           => 'survey/' . $res['file'],
    'db'             => $res['db'],
    'answers'        => survey_answers_list($res['data']['answers']),
    'count'          => responses_count(),
]);
