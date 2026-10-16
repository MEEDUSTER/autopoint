<?php
/**
 * AutoPoint — ЛР6 (бонус): адмін-панель з усіма відповідями.
 * Доступ лише після входу (логін і пароль), стан входу зберігається в $_SESSION.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

start_session();

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        if ($action !== 'login') {
            flash('Помилка безпеки: недійсний CSRF-токен. Дію скасовано.');
            redirect('admin.php');
        }
        http_response_code(403);
        $loginError = 'Сторінка застаріла (недійсний CSRF-токен), спробуйте ще раз.';
    } elseif ($action === 'login') {
        if (admin_login(trim((string) ($_POST['login'] ?? '')), (string) ($_POST['password'] ?? ''))) {
            flash('Ви увійшли як ' . ADMIN_LOGIN . '.');
            redirect('admin.php');
        }
        http_response_code(401);
        $loginError = 'Неправильний логін або пароль.';
    } elseif ($action === 'delete' && is_admin()) {
        $id = (int) ($_POST['id'] ?? 0);
        $row = db_find_response($id);
        if ($row) {
            $fileDeleted = delete_survey_file($row['file']);
            db_delete_response($id);
            flash('Відповідь №' . $id . ' видалено' . ($fileDeleted ? ' разом із файлом ' . $row['file'] : '') . '.');
        } else {
            flash('Відповідь не знайдено.');
        }
        redirect('admin.php');
    } else {
        redirect('admin.php');
    }
}

$message = flash();
$isAdmin = is_admin();
$rows = $isAdmin ? db_all_responses() : [];

$pageTitle   = 'адмін-панель';
$brandSub    = 'Survey admin';
$extraCss    = ['css/survey.css'];
$footerLabel = 'ADMIN · PHP';

include __DIR__ . '/templates/header.php';
?>
    <main class="admin-main">
      <div class="panel">
<?php if ($message): ?>
        <div class="alert <?= str_starts_with($message, 'Помилка') ? 'alert-error' : 'alert-success' ?>"><?= h($message) ?></div>
<?php endif; ?>

<?php if (!$isAdmin): ?>
        <p class="kicker">Адмін-панель</p>
        <h2>Вхід.</h2>
        <form class="login-form" action="admin.php" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">
          <label for="login">Логін</label>
          <input id="login" name="login" type="text" autocomplete="username" required>
          <label for="password">Пароль</label>
          <input id="password" name="password" type="password" autocomplete="current-password" required>
<?php if ($loginError): ?>
          <p class="field-error"><?= h($loginError) ?></p>
<?php endif; ?>
          <button class="btn" type="submit">Увійти</button>
        </form>
        <p class="hint"><a href="survey.php">← До опитування</a></p>
<?php else: ?>
        <div class="admin-head">
          <div>
            <p class="kicker">Адмін-панель · <?= h($_SESSION['admin']) ?> · вхід о <?= h(substr($_SESSION['login_time'], 11)) ?></p>
            <h2>Відповіді: <?= count($rows) ?>.</h2>
          </div>
          <div class="admin-actions">
            <a class="btn" href="export.php?format=csv">Експорт CSV</a>
            <a class="btn btn-ghost" href="export.php?format=json">Експорт JSON</a>
            <a class="btn btn-ghost" href="logout.php">Вийти</a>
          </div>
        </div>
<?php if (!$rows): ?>
        <p>Поки що немає жодної відповіді. <a href="survey.php">Заповнити анкету</a></p>
<?php else: ?>
        <div class="table-wrap">
          <table class="data-table admin-table">
            <thead>
              <tr>
                <th>№</th><th>Дата і час</th><th>Ім’я / email</th>
<?php foreach (QUESTIONS as $q): ?>
                <th><?= h(short_label($q['label'])) ?></th>
<?php endforeach; ?>
                <th>Файл</th><th></th>
              </tr>
            </thead>
            <tbody>
<?php foreach ($rows as $row): ?>
              <tr>
                <td><?= (int) $row['id'] ?></td>
                <td class="nowrap"><?= h($row['created_at']) ?></td>
                <td><?= h($row['name']) ?><br><a href="mailto:<?= h($row['email']) ?>"><?= h($row['email']) ?></a></td>
<?php foreach (QUESTIONS as $key => $q): ?>
                <td><?= h(answer_label($key, $row[$key])) ?></td>
<?php endforeach; ?>
                <td class="file-cell"><code><?= h($row['file']) ?></code></td>
                <td>
                  <form action="admin.php" method="post" onsubmit="return confirm('Видалити відповідь №<?= (int) $row['id'] ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <button class="btn-danger" type="submit">Видалити</button>
                  </form>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>
<?php endif; ?>
      </div>
    </main>
<?php include __DIR__ . '/templates/footer.php'; ?>
