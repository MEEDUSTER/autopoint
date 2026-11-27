<?php
/**
 * AutoPoint — ЛР8 (бонус 3): отримує дані з json.php по HTTP (curl_exec() або file_get_contents())
 * і перетворює їх у HTML-таблицю.
 *
 * Вбудований сервер PHP (php -S) обробляє лише ОДИН запит одночасно, тому сторінка не може
 * звернутися сама до себе — запит «завис» би до тайм-ауту. Тому start-php.bat запускає другий
 * екземпляр сервера на порту 8001 («JSON-сервіс»), і preview.php звертається до http://127.0.0.1:8001/json.php.
 * На повноцінному веб-сервері (Apache, nginx) або з PHP_CLI_SERVER_WORKERS > 1 (Linux/macOS)
 * використовується адреса json.php на тому ж сайті.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/http.php';

const JSON_SERVICE_PORT = 8001;

$source = ($_GET['source'] ?? 'files') === 'db' ? 'db' : 'files';
$query = $source === 'db' ? '?' . http_build_query(['source' => 'db']) : '';

// ---- Адреси json.php, які можна спробувати ----
$port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$candidates = [];
if (PHP_SAPI === 'cli-server') {
    if ($port !== JSON_SERVICE_PORT) {
        $candidates[] = 'http://127.0.0.1:' . JSON_SERVICE_PORT . $dir . '/json.php' . $query;   // окремий екземпляр php -S
    }
    if ((int) getenv('PHP_CLI_SERVER_WORKERS') > 1) {
        $candidates[] = 'http://127.0.0.1:' . $port . $dir . '/json.php' . $query;              // кілька робочих процесів
    }
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $candidates[] = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/json.php' . $query;
}

// ---- Запит до json.php ----
$res = null;
$url = '';
$tries = [];
foreach ($candidates as $url) {
    $res = http_get($url, 5);
    $tries[] = $url . ' → ' . ($res['status'] ?: 'немає з’єднання');
    if ($res['ok']) {
        break;
    }
}

$error = '';
$responses = [];
if ($res === null) {
    $error = 'Немає адреси JSON-сервісу: запустіть сайт через start-php.bat (сервер на порту ' . JSON_SERVICE_PORT . ').';
} elseif (!$res['ok']) {
    $error = $res['status'] > 0
        ? 'json.php повернув помилку HTTP ' . $res['status'] . '.'
        : 'Не вдалося з’єднатися з json.php. ' . $res['error'];
} else {
    $responses = json_decode($res['body']);                 // масив об’єктів stdClass
    if (!is_array($responses)) {
        $error = 'json.php повернув некоректні дані: ' . json_last_error_msg() . '.';
        $responses = [];
    }
}

// Стовпці відповідей: ключі з поля answers усіх анкет (назви — короткі підписи питань ЛР6)
$answerKeys = [];
foreach ($responses as $r) {
    foreach (get_object_vars($r->answers ?? new stdClass()) as $key => $v) {
        $answerKeys[$key] = isset(QUESTIONS[$key]) ? short_label(QUESTIONS[$key]['label']) : $key;
    }
}

function preview_value(mixed $v): string
{
    if (is_array($v)) {
        return implode(', ', array_map('strval', $v));
    }
    return $v === null ? '' : (string) $v;
}

$pageTitle   = 'JSON у таблицю';
$brandSub    = 'JSON preview';
$extraCss    = ['css/survey.css', 'css/rest.css'];
$footerLabel = 'JSON · PREVIEW · PHP';
$lab8Active  = 'preview.php';

include __DIR__ . '/templates/header.php';
?>
    <div class="layout">
<?php include __DIR__ . '/templates/sidebar_lab8.php'; ?>

      <main class="main">
        <div class="panel">
          <p class="kicker">ЛР8 · бонус · preview.php</p>
          <h2>Анкети ЛР6 з json.php.</h2>
          <p>Сторінка отримує JSON від скрипту <a href="json.php<?= h($query) ?>">json.php</a> HTTP-запитом (як від стороннього REST-сервісу), декодує його функцією <code>json_decode()</code> і будує таблицю. Джерело даних: <a href="preview.php"<?= $source === 'files' ? ' class="active"' : '' ?>>текстові файли survey/</a> · <a href="preview.php?source=db">база SQLite</a>.</p>

          <dl class="req-info">
            <dt>Запит</dt><dd><code>GET <?= h($url ?: '—') ?></code></dd>
            <dt>Функція</dt><dd><code><?= h($res['method'] ?? '—') ?>()</code></dd>
            <dt>Відповідь</dt><dd><?php if ($res && $res['status'] > 0): ?><span class="<?= $res['status'] === 200 ? 'status-ok' : 'status-bad' ?>">HTTP <?= $res['status'] ?></span> · <?= strlen($res['body']) ?> байт<?php else: ?><span class="status-bad">немає відповіді</span><?php endif; ?></dd>
            <dt>Джерело</dt><dd><?= $source === 'db' ? 'база даних SQLite (db/survey.sqlite)' : 'текстові файли у папці survey/' ?></dd>
          </dl>

<?php if ($error !== ''): ?>
          <div class="alert alert-error">
            <p><b>Не вдалося отримати дані з json.php.</b> <?= h($error) ?></p>
            <p>Спроби: <?= h(implode('; ', $tries) ?: '—') ?>.</p>
            <p>Вбудований сервер PHP обробляє один запит одночасно, тому preview.php звертається до окремого екземпляра на порту <?= JSON_SERVICE_PORT ?>. Запустіть сайт через start-php.bat — він запускає обидва сервери.</p>
          </div>
<?php elseif (!$responses): ?>
          <div class="alert alert-success"><p>json.php відповів успішно, але анкет ще немає. <a href="survey.php">Заповніть анкету</a> і оновіть сторінку.</p></div>
<?php else: ?>
          <p>Анкет у JSON: <b><?= count($responses) ?></b>.</p>
          <div class="table-wrap">
            <table class="data-table rest-table">
              <thead>
                <tr>
                  <th>№</th><th>Дата і час</th><th>Ім’я</th><th>Email</th>
<?php foreach ($answerKeys as $label): ?>
                  <th><?= h($label) ?></th>
<?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
<?php foreach ($responses as $i => $r): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= h((string) ($r->submitted_at ?? '')) ?></td>
                  <td><?= h((string) ($r->name ?? '')) ?></td>
                  <td><?= h((string) ($r->email ?? '')) ?></td>
<?php foreach ($answerKeys as $key => $label): ?>
                  <td><?= h(preview_value($r->answers->$key ?? null)) ?></td>
<?php endforeach; ?>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
<?php endif; ?>
        </div>
      </main>
    </div>
<?php include __DIR__ . '/templates/footer.php'; ?>
