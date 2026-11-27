<?php
/**
 * AutoPoint — ЛР8: отримання даних JSON з REST API lab.vntu.org.
 * 1) GET-запит з параметрами user і pass через curl_exec() або file_get_contents() (inc/http.php);
 * 2) json_decode() перетворює JSON на об’єкти PHP (stdClass);
 * 3) усі записи про людей з усіх вкладених масивів об’єднуються в один масив і виводяться HTML-таблицею.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/http.php';
require_once __DIR__ . '/inc/rest.php';

const REST_API_URL = 'http://lab.vntu.org/api-server/lab8.php';
const REST_PARAMS  = ['user' => 'student', 'pass' => 'p@ssw0rd'];

// http_build_query() кодує спецсимволи: p@ssw0rd → p%40ssw0rd
$url = REST_API_URL . '?' . http_build_query(REST_PARAMS);

$res = http_get($url, 10);
$error = '';
$people = [];
$groups = [];

if (!$res['ok']) {
    $error = $res['status'] > 0
        ? 'Сервер lab.vntu.org повернув помилку HTTP ' . $res['status'] . '.'
        : 'Не вдалося з’єднатися з сервером lab.vntu.org. ' . $res['error'];
} else {
    $data = json_decode($res['body']);                      // без true — отримуємо об’єкти PHP, а не асоціативні масиви
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Сервер повернув некоректний JSON: ' . json_last_error_msg() . '.';
    } else {
        $people = rest_collect_people($data);               // один масив з усіх груп
        if (!$people) {
            $error = 'У відповіді API не знайдено записів про людей.';
        }
        foreach ($people as $row) {
            $groups[$row['group']] = ($groups[$row['group']] ?? 0) + 1;
        }
    }
}
$columns = rest_columns($people);
$showGroup = count(array_filter(array_keys($groups), fn($g) => $g !== '')) > 0;

$pageTitle   = 'REST API';
$brandSub    = 'REST API desk';
$extraCss    = ['css/survey.css', 'css/rest.css'];
$footerLabel = 'REST API · JSON · PHP';
$lab8Active  = 'rest.php';

include __DIR__ . '/templates/header.php';
?>
    <div class="layout">
<?php include __DIR__ . '/templates/sidebar_lab8.php'; ?>

      <main class="main">
        <div class="panel">
          <p class="kicker">ЛР8 · REST API · JSON</p>
          <h2>Люди з API lab.vntu.org.</h2>
          <p>Сторінка на сервері виконує GET-запит до REST API, перетворює відповідь JSON на об’єкти PHP функцією <code>json_decode()</code>, об’єднує всі записи про людей в один масив і виводить їх таблицею.</p>

          <dl class="req-info">
            <dt>Запит</dt><dd><code>GET <?= h($url) ?></code></dd>
            <dt>Функція</dt><dd><code><?= h($res['method']) ?>()</code></dd>
            <dt>Відповідь</dt><dd><?php if ($res['status'] > 0): ?><span class="<?= $res['status'] === 200 ? 'status-ok' : 'status-bad' ?>">HTTP <?= $res['status'] ?></span><?php else: ?><span class="status-bad">немає відповіді</span><?php endif; ?> · <?= strlen($res['body']) ?> байт · <?= h(number_format($res['time'], 2, ',', '')) ?> с</dd>
            <dt>Час запиту</dt><dd><?= h(format_date_uk(time())) ?>, <?= date('H:i:s') ?></dd>
          </dl>

<?php if ($error !== ''): ?>
          <div class="alert alert-error">
            <p><b>Не вдалося отримати дані з REST API.</b> <?= h($error) ?></p>
            <p>Таблиця з’явиться автоматично, щойно сервер відповість кодом 200 і коректним JSON. Оновіть сторінку пізніше.</p>
          </div>
<?php else: ?>
          <p>Отримано записів: <b><?= count($people) ?></b><?= $showGroup ? ' з ' . count($groups) . ' груп' : '' ?>.</p>
<?php if ($showGroup): ?>
          <ul class="groups">
<?php foreach ($groups as $g => $n): ?>
            <li><?= h($g === '' ? '(верхній рівень)' : $g) ?>: <?= $n ?></li>
<?php endforeach; ?>
          </ul>
<?php endif; ?>
          <div class="table-wrap">
            <table class="data-table rest-table">
              <thead>
                <tr>
                  <th>№</th>
<?php if ($showGroup): ?>
                  <th>Група</th>
<?php endif; ?>
<?php foreach ($columns as $col): ?>
                  <th><?= h($col) ?></th>
<?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
<?php foreach ($people as $i => $row): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
<?php if ($showGroup): ?>
                  <td><?= h($row['group']) ?></td>
<?php endif; ?>
<?php foreach ($columns as $col): ?>
                  <td><?= rest_cell($row['person']->$col ?? null) ?></td>
<?php endforeach; ?>
                </tr>
<?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <details class="raw">
            <summary>Показати отриманий JSON</summary>
            <pre><?= h(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
          </details>
<?php endif; ?>
        </div>
      </main>
    </div>
<?php include __DIR__ . '/templates/footer.php'; ?>
