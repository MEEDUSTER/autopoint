<?php
/** Бокова панель сторінок ЛР6. Підключається через include_once. */
?>
      <aside class="sidebar">
        <div class="idx">06 / PHP</div>
        <h2>Опитування</h2>
        <ol>
          <li>Заповніть ім’я та email</li>
          <li>Дайте відповідь на 5 питань</li>
          <li>Натисніть «Надіслати»</li>
        </ol>
        <div class="note">Уже відповіли: <b><?= responses_count() ?></b>. Кожна анкета зберігається окремим файлом у папці survey і в базі даних SQLite.</div>
        <div class="note"><a href="admin.php">Адмін-панель</a> · <a href="index.html">Повернутися на сайт</a></div>
      </aside>
