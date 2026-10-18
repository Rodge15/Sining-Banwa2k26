<?php
declare(strict_types=1);

function db_driver(): string
{
    $driver = strtolower(trim((string) env_value('DB_DRIVER', 'mysql')));
    if (!in_array($driver, ['mysql', 'pgsql'], true)) {
        throw new RuntimeException('Database driver configuration is invalid.');
    }

    return $driver;
}

function is_unique_violation(PDOException $error): bool
{
    $state = (string) $error->getCode();
    $driverCode = (int) ($error->errorInfo[1] ?? 0);

    return $state === '23505' || ($state === '23000' && $driverCode === 1062);
}

function db_last_insert_id(PDO $connection): int
{
    if (db_driver() === 'pgsql') {
        $value = $connection->query('SELECT LASTVAL()')->fetchColumn();
        if ($value === false) {
            throw new RuntimeException('The database did not return a new record id.');
        }

        return (int) $value;
    }

    return (int) $connection->lastInsertId();
}

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $driver = db_driver();
    $host = env_value('DB_HOST');
    $port = env_value('DB_PORT', $driver === 'pgsql' ? '5432' : '3306');
    $name = env_value('DB_NAME');
    $user = env_value('DB_USER');
    $password = env_value('DB_PASS');

    if ($host === null || $host === '' || $name === null || $name === '' || $user === null || $password === null) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    if ($port !== null && $port !== '' && !ctype_digit($port)) {
        throw new RuntimeException('Database port configuration is invalid.');
    }

    if ($driver === 'pgsql') {
        $sslMode = strtolower(trim((string) env_value('DB_SSLMODE', 'require')));
        if (!in_array($sslMode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) {
            throw new RuntimeException('Database SSL mode configuration is invalid.');
        }
        $dsn = sprintf('pgsql:host=%s;dbname=%s;sslmode=%s', $host, $name, $sslMode);
    } else {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
    }
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
