<?php
declare(strict_types=1);

function is_authed(): bool
{
    return !empty($_SESSION['authed']);
}

function require_auth(): void
{
    if (!is_authed()) {
        redirect('/login');
    }
}

function attempt_login(string $user, string $pass): bool
{
    if (hash_equals(APP_USER, $user) && hash_equals(APP_PASS, $pass)) {
        session_regenerate_id(true);
        $_SESSION['authed'] = true;
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
