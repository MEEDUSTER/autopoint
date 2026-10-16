<?php
/**
 * Шапка сторінки. Очікує змінні: $pageTitle, $brandSub (необов'язково), $extraCss (масив).
 * Підключається через include.
 */
$pageTitle = $pageTitle ?? SITE_NAME;
$brandSub  = $brandSub ?? 'PHP survey desk';
$extraCss  = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(SITE_NAME . ' — ' . $pageTitle) ?></title>
  <link rel="icon" href="data:,">
  <link rel="stylesheet" href="css/style.css">
<?php foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= h($css) ?>">
<?php endforeach; ?>
</head>
<body>
  <div class="page">
    <header class="site-header">
      <div class="brand">
        <div class="mark">AP</div>
        <div>
          <h1 class="logo">Auto<span>Point</span></h1>
          <small><?= h($brandSub) ?></small>
        </div>
      </div>
      <nav class="main-nav">
        <ul class="menu">
<?= render_nav() ?>
        </ul>
      </nav>
    </header>
