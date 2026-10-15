<?php
declare(strict_types=1);

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = env_value('DB_HOST');
    $port = env_value('DB_PORT', '3306');
    $name = env_value('DB_NAME');
    $user = env_value('DB_USER');
    $password = env_value('DB_PASS');

    if ($host === null || $host === '' || $name === null || $name === '' || $user === null || $password === null) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    if ($port !== null && $port !== '' && !ctype_digit($port)) {
        throw new RuntimeException('Database port configuration is invalid.');
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
    if ($port !== null && $port !== '') {
        $dsn .= ';port=' . $port;
    }
    $connection = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}
