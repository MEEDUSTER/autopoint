<?php
/**
 * AutoPoint — ЛР6–ЛР7: сторінка опитування «Яке авто обирають студенти».
 * ЛР7: з увімкненим JavaScript форма надсилається через XMLHttpRequest (js/survey-ajax.js → survey_ajax.php)
 * без перезавантаження сторінки; без JavaScript — звичайним POST на цю ж сторінку.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

start_session();

$old = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Звичайне надсилання форми (без JavaScript). З JavaScript форма йде через AJAX у survey_ajax.php.
    $res = process_survey($_POST);
    $old = $res['data'];
    $errors = $res['errors'];

    if ($res['ok']) {
        $_SESSION['last_survey'] = [
            'time'    => $res['time'],
            'name'    => $old['name'],
            'answers' => $old['answers'],
            'file'    => $res['file'],
            'db'      => $res['db'],
        ];
        redirect('survey.php?done=1');                    // POST → Redirect → GET
    }
    http_response_code($res['status']);
}

$result = isset($_GET['done']) ? ($_SESSION['last_survey'] ?? null) : null;

$pageTitle   = 'опитування';
$brandSub    = 'Student car survey';
$extraCss    = ['css/survey.css'];
$footerLabel = 'SURVEY · PHP · AJAX';
$extraJs     = ['js/survey-ajax.js'];

include __DIR__ . '/templates/header.php';
?>
    <div class="layout">
<?php include_once __DIR__ . '/templates/sidebar.php'; ?>

      <main class="main">
        <div class="panel">
          <p class="kicker">ЛР6–ЛР7 · PHP · POST через AJAX</p>
          <h2><?= h(SURVEY_TITLE) ?>.</h2>
<?php if ($result): ?>
          <div class="alert alert-success">
            <p class="done-title">Дякуємо, <?= h($result['name']) ?>! Анкету прийнято.</p>
            <p class="done-time">Дата і час заповнення: <b><?= h(format_date_uk($result['time'])) ?>, <?= date('H:i:s', $result['time']) ?></b></p>
            <p class="done-file">Створено файл: <code>survey/<?= h($result['file']) ?></code><?= $result['db'] ? ' · запис додано в базу даних' : ' · база даних тимчасово недоступна' ?></p>
          </div>
          <table class="data-table answers-table">
            <thead><tr><th>Питання</th><th>Ваша відповідь</th></tr></thead>
            <tbody>
<?php foreach (QUESTIONS as $key => $q): ?>
              <tr><td><?= h($q['label']) ?></td><td><?= h(answer_label($key, $result['answers'][$key])) ?></td></tr>
<?php endforeach; ?>
            </tbody>
          </table>
          <p><a class="btn" href="survey.php">Заповнити ще одну анкету</a></p>
<?php else: ?>
          <p>Допоможіть зібрати статистику для AutoPoint: яке авто студенти вважають оптимальним як перше. Поля з * обов’язкові. Дані зберігаються лише для навчального проєкту.</p>
          <div id="surveyResult" hidden></div>
<?php require __DIR__ . '/templates/survey_form.php'; ?>
<?php endif; ?>
        </div>
      </main>
    </div>
<?php include __DIR__ . '/templates/footer.php'; ?>
