<?php
/** AutoPoint — ЛР6: вихід з адмін-панелі (знищення сесії). */

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';

admin_logout();
header('Location: admin.php', true, 303);
exit;
