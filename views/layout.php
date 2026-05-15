<?php
/** @var string $__content */
$activeTab = $activeTab ?? '';
$flashMsg  = flash();
$bodyClass = $bodyClass ?? '';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subcontractor Invoice Matcher</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body class="<?= h($bodyClass) ?>">
<?php if ($bodyClass === 'login'): ?>
    <div class="login">
        <?= $__content ?>
    </div>
<?php else: ?>
    <div class="wrap">
        <header class="app">
            <div>
                <h1 class="title">Subcontractor Invoice Matcher</h1>
                <p class="lead">Upload subcontractor invoices, compare them to job sheets, and manage operator pay settings in one place.</p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem;">
                <nav class="nav">
                    <a href="/"          class="<?= $activeTab === 'invoices'  ? 'active' : '' ?>">Invoices</a>
                    <a href="/operators" class="<?= $activeTab === 'operators' ? 'active' : '' ?>">Operators &amp; Rates</a>
                </nav>
                <a href="/logout" style="font-size:0.8rem;color:var(--muted);">Logout</a>
            </div>
        </header>

        <?php if ($flashMsg): ?>
            <div class="flash"><?= h($flashMsg) ?></div>
        <?php endif; ?>

        <?= $__content ?>
    </div>
<?php endif; ?>
</body>
</html>
