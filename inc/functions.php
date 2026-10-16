<?php
/**
 * AutoPoint — ЛР6: загальні функції (безпека, анкета, файли, сесія, шаблони).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ======================= Безпека =======================

/** Екранування виводу — захист від XSS. */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_name('AUTOPOINTSESS');
        session_start();
    }
}

/** CSRF-токен, що зберігається в сесії. */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    start_session();
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ======================= Адміністратор =======================

function is_admin(): bool
{
    start_session();
    return !empty($_SESSION['admin']) && $_SESSION['admin'] === ADMIN_LOGIN;
}

function admin_login(string $login, string $password): bool
{
    start_session();
    if (hash_equals(ADMIN_LOGIN, $login) && password_verify($password, ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);           // новий id сесії після входу
        unset($_SESSION['csrf']);              // і новий CSRF-токен
        $_SESSION['admin'] = ADMIN_LOGIN;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        return true;
    }
    return false;
}

function admin_logout(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: admin.php');
        exit;
    }
}

function redirect(string $url): void
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Повідомлення, яке показується один раз після перенаправлення. */
function flash(?string $message = null): ?string
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = $message;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ======================= Анкета =======================

/** Перевіряє дані форми. Повертає [очищені дані, масив помилок]. */
function validate_survey(array $post): array
{
    $errors = [];
    $data = [
        'name'    => trim((string) ($post['name'] ?? '')),
        'email'   => trim((string) ($post['email'] ?? '')),
        'answers' => [],
    ];

    $nameLength = mb_strlen($data['name']);
    if ($data['name'] === '') {
        $errors['name'] = 'Вкажіть ім’я.';
    } elseif ($nameLength < 2 || $nameLength > 60) {
        $errors['name'] = 'Ім’я має містити від 2 до 60 символів.';
    }

    if ($data['email'] === '') {
        $errors['email'] = 'Вкажіть email.';
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Email має неправильний формат.';
    }

    foreach (QUESTIONS as $key => $q) {
        $value = $post[$key] ?? null;
        switch ($q['type']) {
            case 'radio':
            case 'select':
                if (!is_string($value) || !array_key_exists($value, $q['options'])) {
                    $errors[$key] = 'Оберіть один із варіантів.';
                    $value = '';
                }
                break;
            case 'checkbox':
                $value = is_array($value) ? array_values(array_unique($value)) : [];
                $value = array_values(array_filter($value, fn($v) => is_string($v) && isset($q['options'][$v])));
                if (count($value) === 0) {
                    $errors[$key] = 'Оберіть хоча б один варіант.';
                } elseif (count($value) > $q['max']) {
                    $errors[$key] = 'Можна обрати не більше ' . $q['max'] . ' варіантів.';
                }
                break;
            case 'textarea':
                $value = trim((string) $value);
                if ($value === '') {
                    $errors[$key] = 'Напишіть кілька слів.';
                } elseif (mb_strlen($value) > $q['maxlength']) {
                    $errors[$key] = 'Не більше ' . $q['maxlength'] . ' символів.';
                }
                break;
        }
        $data['answers'][$key] = $value;
    }

    return [$data, $errors];
}

/** Людський текст відповіді (назва варіанта замість коду). */
function answer_label(string $key, $value): string
{
    $q = QUESTIONS[$key];
    if ($q['type'] === 'textarea') {
        return (string) $value;
    }
    $values = is_array($value) ? $value : explode(',', (string) $value);
    $labels = [];
    foreach ($values as $v) {
        if ($v !== '') {
            $labels[] = $q['options'][$v] ?? $v;
        }
    }
    return implode(', ', $labels);
}

/**
 * Створює у папці survey файл з назвою за датою й часом заповнення,
 * наприклад survey/2026-10-16_11-45-03_ab12.json. Повертає ім'я файлу.
 */
function save_survey_file(array $data, int $time): string
{
    if (!is_dir(SURVEY_DIR)) {
        mkdir(SURVEY_DIR, 0775, true);
    }
    $fileName = date('Y-m-d_H-i-s', $time) . '_' . bin2hex(random_bytes(2)) . '.json';

    $content = [
        'submitted_at' => date('Y-m-d H:i:s', $time),
        'name'         => $data['name'],
        'email'        => $data['email'],
        'answers'      => [],
    ];
    foreach (QUESTIONS as $key => $q) {
        $content['answers'][] = [
            'question' => $q['label'],
            'answer'   => answer_label($key, $data['answers'][$key]),
        ];
    }

    $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents(SURVEY_DIR . '/' . $fileName, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Не вдалося створити файл анкети.');
    }
    return $fileName;
}

/** Видаляє файл анкети лише всередині папки survey (захист від ../). */
function delete_survey_file(string $fileName): bool
{
    $safe = basename($fileName);
    $path = SURVEY_DIR . '/' . $safe;
    if ($safe === '' || !preg_match('/^[\w-]+\.json$/', $safe) || !is_file($path)) {
        return false;
    }
    return unlink($path);
}

/** Українська дата: «16 жовтня 2026 р.» */
function format_date_uk(int $time): string
{
    $months = ['січня', 'лютого', 'березня', 'квітня', 'травня', 'червня',
               'липня', 'серпня', 'вересня', 'жовтня', 'листопада', 'грудня'];
    return date('j', $time) . ' ' . $months[(int) date('n', $time) - 1] . ' ' . date('Y', $time) . ' р.';
}

// ======================= Шаблони (динамічні частини) =======================

/** Пункти верхнього меню — ті самі, що в HTML-сторінках. */
function nav_items(): array
{
    return [
        'index.html'    => 'На головну',
        'about.html'    => 'Про сайт',
        'catalog.html'  => 'Каталог',
        'service.html'  => 'Обслуговування',
        'contacts.html' => 'Контакти',
    ];
}

function render_nav(string $active = ''): string
{
    $html = '';
    foreach (nav_items() as $href => $title) {
        $class = $href === $active ? ' class="active"' : '';
        $html .= '          <li><a' . $class . ' href="' . h($href) . '">' . h($title) . '</a></li>' . "\n";
    }
    return $html;
}

/** Виводить одне питання анкети з урахуванням раніше введених даних і помилок. */
function render_question(string $key, array $q, array $old, array $errors): string
{
    $err = isset($errors[$key]) ? '<p class="field-error">' . h($errors[$key]) . '</p>' : '';
    $value = $old['answers'][$key] ?? ($q['type'] === 'checkbox' ? [] : '');
    $html = '<fieldset class="question' . ($err ? ' has-error' : '') . '">';
    $html .= '<legend>' . h($q['label']) . '</legend>';

    switch ($q['type']) {
        case 'radio':
        case 'checkbox':
            $name = $q['type'] === 'checkbox' ? $key . '[]' : $key;
            $html .= '<div class="choices">';
            foreach ($q['options'] as $code => $label) {
                $checked = $q['type'] === 'checkbox'
                    ? in_array($code, (array) $value, true)
                    : $value === $code;
                $html .= '<label class="choice"><input type="' . $q['type'] . '" name="' . h($name) . '" value="' . h($code) . '"'
                       . ($checked ? ' checked' : '') . '> <span>' . h($label) . '</span></label>';
            }
            $html .= '</div>';
            break;
        case 'select':
            $html .= '<select name="' . h($key) . '" id="' . h($key) . '">';
            $html .= '<option value="">— оберіть —</option>';
            foreach ($q['options'] as $code => $label) {
                $html .= '<option value="' . h($code) . '"' . ($value === $code ? ' selected' : '') . '>' . h($label) . '</option>';
            }
            $html .= '</select>';
            break;
        case 'textarea':
            $html .= '<textarea name="' . h($key) . '" id="' . h($key) . '" rows="3" maxlength="' . (int) $q['maxlength'] . '"'
                   . ' placeholder="' . h($q['placeholder'] ?? '') . '">' . h((string) $value) . '</textarea>';
            break;
    }
    return $html . $err . '</fieldset>';
}

/** Кількість відповідей у базі — для бокової панелі. */
function responses_count(): int
{
    try {
        return (int) db()->query('SELECT COUNT(*) FROM responses')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Коротка назва питання для заголовків таблиці. */
function short_label(string $label): string
{
    $map = [
        'Який бюджет'        => 'Бюджет',
        'Який тип кузова'    => 'Кузов',
        'Який тип двигуна'   => 'Двигун',
        'Що для вас'         => 'Пріоритети',
        'Яку конкретну'      => 'Модель і чому',
    ];
    foreach ($map as $start => $short) {
        if (str_starts_with($label, $start)) {
            return $short;
        }
    }
    return $label;
}
