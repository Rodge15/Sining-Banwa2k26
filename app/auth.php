<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;

    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) {
        return null;
    }

    $statement = db()->prepare(
        'SELECT id, name, email, phone, role, is_active FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => (int) $userId]);
    $record = $statement->fetch();
    if ($record === false || (int) $record['is_active'] !== 1) {
        unset($_SESSION['user_id']);
        return null;
    }

    $user = [
        'id' => (int) $record['id'],
        'name' => (string) $record['name'],
        'email' => (string) $record['email'],
        'phone' => $record['phone'] === null ? '' : (string) $record['phone'],
        'role' => (string) $record['role'],
    ];

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        set_flash('warning', 'Please sign in to continue.');
        redirect('/login.php');
    }

    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        require PROJECT_ROOT . '/app/views/forbidden.php';
        exit;
    }

    return $user;
}

function redirect_authenticated_user(): void
{
    if (current_user() !== null) {
        redirect('/dashboard.php');
    }
}
