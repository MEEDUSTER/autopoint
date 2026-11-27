<?php
/** Підвал сторінки. Очікує $footerLabel і $extraJs (необов'язково). Підключається через include. */
$footerLabel = $footerLabel ?? 'PHP';
$extraJs     = $extraJs ?? [];   // ЛР7: додаткові скрипти сторінки
?>
    <footer class="site-footer">
      <div>AUTOPOINT · <?= h($footerLabel) ?> · <?= date('Y') ?></div>
      <div><a href="index.html">На головну</a></div>
    </footer>
  </div>
<?php foreach ($extraJs as $js): ?>
  <script src="<?= h($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
