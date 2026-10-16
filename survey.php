<?php
/**
 * AutoPoint — ЛР6: сторінка опитування «Яке авто обирають студенти».
 * Форма надсилається методом POST на цю ж сторінку.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

start_session();

$old = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$old, $errors] = validate_survey($_POST);

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Сторінка застаріла, надішліть форму ще раз.';
    }

    if (!$errors) {
        $time = time();                                   // момент заповнення
        $old['submitted_at'] = date('Y-m-d H:i:s', $time);

        $file = save_survey_file($old, $time);            // основне завдання: файл у папці survey

        $savedToDb = true;
        try {
            db_insert_response($old, $file);              // бонус: запис у базу даних
        } catch (Throwable $e) {
            $savedToDb = false;
            error_log('DB error: ' . $e->getMessage());
        }

        $_SESSION['last_survey'] = [
            'time'    => $time,
            'name'    => $old['name'],
            'answers' => $old['answers'],
            'file'    => $file,
            'db'      => $savedToDb,
        ];
        redirect('survey.php?done=1');                    // POST → Redirect → GET
    }
    http_response_code(422);
}

$result = isset($_GET['done']) ? ($_SESSION['last_survey'] ?? null) : null;

$pageTitle   = 'опитування';
$brandSub    = 'Student car survey';
$extraCss    = ['css/survey.css'];
$footerLabel = 'SURVEY · PHP';

include __DIR__ . '/templates/header.php';
?>
    <div class="layout">
<?php include_once __DIR__ . '/templates/sidebar.php'; ?>

      <main class="main">
        <div class="panel">
          <p class="kicker">ЛР6 · PHP · метод POST</p>
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
<?php require __DIR__ . '/templates/survey_form.php'; ?>
<?php endif; ?>
        </div>
      </main>
    </div>
<?php include __DIR__ . '/templates/footer.php'; ?>
