<?php
/** Підвал сторінки. Очікує $footerLabel (необов'язково). Підключається через include. */
$footerLabel = $footerLabel ?? 'PHP';
?>
    <footer class="site-footer">
      <div>AUTOPOINT · <?= h($footerLabel) ?> · <?= date('Y') ?></div>
      <div><a href="index.html">На головну</a></div>
    </footer>
  </div>
</body>
</html>
