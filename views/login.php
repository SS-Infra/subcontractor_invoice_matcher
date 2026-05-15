<?php
/** @var ?string $error */
$bodyClass = 'login';
?>
<div class="login-card">
    <div class="brand"><span class="dot"></span>STOCK</div>
    <h1>Admin Sign In</h1>
    <p class="subtitle">Sign in with your username and password.</p>

    <form method="post" action="/login" class="stack">
        <div class="field">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <button type="submit" class="btn primary btn-block">Sign in</button>
    </form>
</div>
