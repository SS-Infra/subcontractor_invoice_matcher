<?php
/** @var string $__content */
$reqPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$activeTab = str_starts_with($reqPath, '/operators') ? 'operators' : 'invoices';
$flashMsg  = flash();
$isLogin   = ($bodyClass ?? '') === 'login' || str_starts_with($reqPath, '/login');
$cssV      = @filemtime(BASE_DIR . '/styles.css') ?: time();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OnPoint SE · Invoice Matcher</title>
    <link rel="stylesheet" href="/styles.css?v=<?= (int) $cssV ?>">
</head>
<body class="<?= $isLogin ? 'login' : '' ?>">
<?php if ($isLogin): ?>
    <div class="login-wrap">
        <?= $__content ?>
    </div>
<?php else: ?>
    <header class="topnav">
        <span class="brand">On<span class="dot">Point</span>&nbsp;SE</span>
        <span class="pill">Invoice Matcher</span>
        <span class="spacer"></span>
        <nav class="links">
            <a href="/"          class="<?= $activeTab === 'invoices'  ? 'active' : '' ?>">Invoices</a>
            <a href="/operators" class="<?= $activeTab === 'operators' ? 'active' : '' ?>">Operators</a>
        </nav>
        <span class="user-chip">admin</span>
        <a href="/logout" class="signout">Sign out</a>
    </header>

    <div class="wrap">
        <?php if ($flashMsg): ?>
            <div class="flash"><?= h($flashMsg) ?></div>
        <?php endif; ?>
        <?= $__content ?>
    </div>
<?php endif; ?>
</body>
</html>
