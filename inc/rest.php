<?php
/**
 * AutoPoint — ЛР8: обробка відповіді REST API (об’єкти PHP після json_decode()).
 * Відповідь може містити людей у кількох вкладених масивах (групах) —
 * функція rest_collect_people() обходить усю структуру і збирає всі записи в один масив.
 */

declare(strict_types=1);

/** Чи є значення «простим» (рядок, число, логічне, null). */
function rest_is_scalar(mixed $v): bool
{
    return $v === null || is_scalar($v);
}

/** Чи містить масив хоча б один об’єкт. */
function rest_has_objects(array $list): bool
{
    foreach ($list as $item) {
        if (is_object($item) || (is_array($item) && rest_has_objects($item))) {
            return true;
        }
    }
    return false;
}

/**
 * Рекурсивно збирає записи про людей у ОДИН масив.
 * Кожен елемент: ['group' => шлях до вкладеного масиву (наприклад "students"), 'person' => stdClass].
 *  - масив — обходимо всі елементи;
 *  - об’єкт без простих полів (лише масиви/об’єкти) — це контейнер груп, обходимо кожну групу;
 *  - об’єкт-«обгортка» без поля name, але з масивами об’єктів (status, count, data: [...]) — обходимо масиви;
 *  - інакше об’єкт — це запис про людину.
 */
function rest_collect_people(mixed $node, string $group = ''): array
{
    $people = [];
    if (is_array($node)) {
        foreach ($node as $item) {
            array_push($people, ...rest_collect_people($item, $group));
        }
        return $people;
    }
    if (!is_object($node)) {
        return [];
    }

    $props = get_object_vars($node);
    $hasScalar = false;
    $nested = [];
    foreach ($props as $key => $value) {
        if (rest_is_scalar($value)) {
            $hasScalar = true;
        } elseif ((is_array($value) && rest_has_objects($value)) || is_object($value)) {
            $nested[$key] = $value;
        }
    }

    $isContainer = !$hasScalar && $nested;                                   // {"students": [...], "teachers": [...]}
    $isEnvelope = $hasScalar && !property_exists($node, 'name')
                  && array_filter($nested, fn($v) => is_array($v));          // {"status": "ok", "data": [...]}
    if ($isContainer || $isEnvelope) {
        foreach ($nested as $key => $value) {
            if ($isEnvelope && !is_array($value)) {
                continue;
            }
            $sub = is_int($key) ? $group : ($group === '' ? (string) $key : $group . ' / ' . $key);
            array_push($people, ...rest_collect_people($value, $sub));
        }
        return $people;
    }

    return [['group' => $group, 'person' => $node]];
}

/** Назви стовпців — усі властивості всіх об’єктів у порядку появи (name — першим). */
function rest_columns(array $people): array
{
    $cols = [];
    foreach ($people as $row) {
        foreach (array_keys(get_object_vars($row['person'])) as $key) {
            $cols[(string) $key] = true;
        }
    }
    $cols = array_keys($cols);
    $cols = array_map('strval', $cols);
    if (($i = array_search('name', $cols, true)) !== false && $i > 0) {
        array_splice($cols, $i, 1);
        array_unshift($cols, 'name');
    }
    return $cols;
}

/** HTML-вміст клітинки: прості значення — текстом, вкладені масиви/об’єкти — компактним JSON. */
function rest_cell(mixed $value): string
{
    if ($value === null || $value === '') {
        return '<span class="muted">—</span>';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
        return h((string) $value);
    }
    if (is_array($value) && !rest_has_objects($value) && array_filter($value, 'is_array') === []) {
        return h(implode(', ', array_map(fn($v) => is_bool($v) ? ($v ? 'true' : 'false') : (string) $v, $value)));
    }
    return '<code class="nested">' . h(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '</code>';
}
