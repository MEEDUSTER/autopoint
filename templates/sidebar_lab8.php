<?php
/** Бокова панель сторінок ЛР8 (REST API, JSON). Очікує $lab8Active — поточна сторінка. */
$lab8Active = $lab8Active ?? '';
$lab8Pages = [
    'rest.php'    => 'Дані з REST API lab.vntu.org',
    'json.php'    => 'json.php — анкети ЛР6 у JSON',
    'preview.php' => 'preview.php — JSON у таблицю',
];
?>
      <aside class="sidebar">
        <div class="idx">08 / REST</div>
        <h2>REST і JSON</h2>
        <ol>
<?php foreach ($lab8Pages as $href => $title): ?>
          <li><?php if ($href === $lab8Active): ?><b><?= h($title) ?></b><?php else: ?><a href="<?= h($href) ?>"><?= h($title) ?></a><?php endif; ?></li>
<?php endforeach; ?>
        </ol>
        <div class="note">Дані між додатками передаються по HTTP у форматі JSON. PHP отримує їх функціями <code>curl_exec()</code> або <code>file_get_contents()</code> і перетворює на об’єкти через <code>json_decode()</code>.</div>
        <div class="note"><a href="survey.php">Анкета ЛР6</a> · <a href="index.html">Повернутися на сайт</a></div>
      </aside>
