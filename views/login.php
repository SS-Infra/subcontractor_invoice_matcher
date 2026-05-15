<?php
/** @var ?string $error */
$bodyClass = 'login';
?>
<div class="card">
    <h1 class="title" style="font-size:1.6rem;">Subcontractor Invoice Matcher</h1>
    <p class="lead" style="margin-bottom:1.4rem;">Internal tool. Sign in to upload invoices and manage operator rates.</p>

    <form method="post" action="/login" class="stack">
        <label class="field">
            <span>Username</span>
            <input type="text" name="username" autocomplete="username" required>
        </label>
        <label class="field">
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>

        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn">Sign in</button>

        <p style="font-size:0.8rem;color:var(--muted);margin:0;">
            Default: <strong>admin / admin123</strong> (change in <code>src/bootstrap.php</code>).
        </p>
    </form>
</div>
