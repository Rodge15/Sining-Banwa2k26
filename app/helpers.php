<?php
declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'staff' => 'Staff',
        'venue' => 'Venue personnel',
        default => 'Customer',
    };
}

function app_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function redirect(string $path): void
{
    header('Location: ' . app_url($path), true, 303);
    exit;
}

function csrf_token(): string
{
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf_token'])
        && hash_equals($_SESSION['_csrf_token'], $token);
}

function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($flash) ? $flash : null;
}
