<?php
declare(strict_types=1);

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function render(string $view, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    $__content = (function () use ($view, $vars) {
        extract($vars, EXTR_SKIP);
        ob_start();
        require VIEW_DIR . '/' . $view . '.php';
        return ob_get_clean();
    })();
    require VIEW_DIR . '/layout.php';
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return $v !== false && $v !== '' ? $v : $default;
}

function to_float($v, float $default = 0.0): float
{
    if ($v === null || $v === '') {
        return $default;
    }
    if (is_numeric($v)) {
        return (float) $v;
    }
    return $default;
}

function normalise_date(?string $value): ?string
{
    if (!$value) {
        return null;
    }
    $value = trim($value);
    foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y'] as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $value);
        if ($dt && $dt->format($fmt) === $value) {
            return $dt->format('Y-m-d');
        }
    }
    $ts = strtotime($value);
    return $ts ? date('Y-m-d', $ts) : null;
}
