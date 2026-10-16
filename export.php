<?php
/**
 * AutoPoint — ЛР6 (бонус): експорт відповідей у CSV (для Excel) або JSON.
 * Доступно лише адміністратору.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

require_admin();

$format = $_GET['format'] ?? 'csv';
$rows = db_all_responses();
$stamp = date('Y-m-d_H-i-s');

// Перетворюємо коди варіантів на зрозумілі назви
$export = [];
foreach ($rows as $row) {
    $item = [
        'id'         => (int) $row['id'],
        'created_at' => $row['created_at'],
        'name'       => $row['name'],
        'email'      => $row['email'],
    ];
    foreach (QUESTIONS as $key => $q) {
        $item[$key] = answer_label($key, $row[$key]);
    }
    $item['file'] = $row['file'];
    $export[] = $item;
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="survey_' . $stamp . '.json"');
    echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="survey_' . $stamp . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");                 // BOM, щоб Excel правильно показав кирилицю
$header = ['№', 'Дата і час', 'Ім’я', 'Email'];
foreach (QUESTIONS as $q) {
    $header[] = short_label($q['label']);
}
$header[] = 'Файл';
fputcsv($out, $header, ';', '"', '');        // «;» — роздільник для Excel з українськими налаштуваннями
foreach ($export as $item) {
    fputcsv($out, array_values($item), ';', '"', '');
}
fclose($out);
