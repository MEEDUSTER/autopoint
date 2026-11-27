<?php
/**
 * AutoPoint — ЛР8 (бонус 1–2): усі відповіді анкети ЛР6 у форматі JSON.
 * Кожна анкета — окремий елемент масиву; ім’я, пошта та відповіді — окремі параметри JSON.
 * Джерело за замовчуванням — текстові файли survey/*.json (їх парсинг — бонус 2);
 * json.php?source=db — ті самі дані з бази SQLite.
 * Дані особисті, тому скрипт відповідає лише на запити з цього ж комп’ютера.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Лише локальні запити (браузер на цьому ж ПК або preview.php)
$client = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($client, ['127.0.0.1', '::1', $_SERVER['SERVER_ADDR'] ?? ''], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Доступ дозволено лише з локального комп’ютера.'], JSON_FLAGS);
    exit;
}

/** Ключ питання за його текстом (у файлах анкет зберігається текст питання). */
function question_key(string $label, int $index): string
{
    foreach (QUESTIONS as $key => $q) {
        if ($q['label'] === $label) {
            return $key;
        }
    }
    return 'question_' . ($index + 1);
}

/** Відповідь у JSON: для питань з кількома варіантами — масив, інакше рядок. */
function answer_value(string $key, string $text): string|array
{
    if ((QUESTIONS[$key]['type'] ?? '') === 'checkbox') {
        return $text === '' ? [] : explode(', ', $text);
    }
    return $text;
}

/** Бонус 2: парсинг текстових файлів survey/*.json, створених у ЛР6. */
function responses_from_files(array &$skipped): array
{
    $result = [];
    $files = glob(SURVEY_DIR . '/*.json') ?: [];
    sort($files);                                            // імена файлів — дата й час, тому це сортування за часом
    foreach ($files as $path) {
        $file = basename($path);
        $data = json_decode((string) file_get_contents($path));      // об’єкт stdClass
        if (!is_object($data) || !isset($data->name, $data->email, $data->answers) || !is_array($data->answers)) {
            $skipped[] = $file;                              // пошкоджений або чужий файл — пропускаємо
            continue;
        }
        $answers = [];
        foreach ($data->answers as $i => $a) {
            $key = question_key((string) ($a->question ?? ''), $i);
            $answers[$key] = answer_value($key, (string) ($a->answer ?? ''));
        }
        $result[] = [
            'id'           => pathinfo($file, PATHINFO_FILENAME),
            'submitted_at' => (string) ($data->submitted_at ?? ''),
            'name'         => (string) $data->name,
            'email'        => (string) $data->email,
            'answers'      => $answers,
            'file'         => 'survey/' . $file,
        ];
    }
    return $result;
}

/** Додатково: ті самі анкети з бази даних SQLite (json.php?source=db). */
function responses_from_db(): array
{
    $result = [];
    foreach (array_reverse(db_all_responses()) as $row) {
        $answers = [];
        foreach (QUESTIONS as $key => $q) {
            $answers[$key] = answer_value($key, answer_label($key, $row[$key]));
        }
        $result[] = [
            'id'           => (int) $row['id'],
            'submitted_at' => $row['created_at'],
            'name'         => $row['name'],
            'email'        => $row['email'],
            'answers'      => $answers,
            'file'         => 'survey/' . $row['file'],
        ];
    }
    return $result;
}

$source = ($_GET['source'] ?? 'files') === 'db' ? 'db' : 'files';
$skipped = [];

try {
    $responses = $source === 'db' ? responses_from_db() : responses_from_files($skipped);
} catch (Throwable $e) {
    error_log('json.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Не вдалося прочитати відповіді анкети.'], JSON_FLAGS);
    exit;
}

header('X-Survey-Source: ' . $source);
header('X-Survey-Count: ' . count($responses));
if ($skipped) {
    header('X-Survey-Skipped: ' . count($skipped));
}

echo json_encode($responses, JSON_FLAGS);                   // масив анкет: [ {...}, {...} ]
