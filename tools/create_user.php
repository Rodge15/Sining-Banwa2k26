<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script can only be run from the command line.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

function prompt_hidden_password(string $prompt): string
{
    fwrite(STDERR, $prompt);

    if (PHP_OS_FAMILY === 'Windows') {
        $command = '$secure = Read-Host -Prompt "Password" -AsSecureString; '
            . '$ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); '
            . 'try { [Console]::Out.Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)) } '
            . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr) }';
        $process = proc_open(
            ['powershell.exe', '-NoProfile', '-Command', $command],
            [0 => ['file', 'NUL', 'r'], 1 => ['pipe', 'w'], 2 => ['file', 'NUL', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start the secure password prompt.');
        }
        $password = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $status = proc_close($process);
        if ($status !== 0 || $password === false) {
            throw new RuntimeException('Could not read the password.');
        }
        return $password;
    }

    $tty = function_exists('stream_isatty') && stream_isatty(STDIN);
    $terminalState = $tty ? trim((string) shell_exec('stty -g < /dev/tty 2>/dev/null')) : '';
    if ($terminalState !== '') {
        shell_exec('stty -echo < /dev/tty 2>/dev/null');
    }
    try {
        $password = fgets(STDIN);
    } finally {
        if ($terminalState !== '') {
            shell_exec('stty ' . escapeshellarg($terminalState) . ' < /dev/tty 2>/dev/null');
            fwrite(STDERR, PHP_EOL);
        }
    }
    if ($password === false) {
        throw new RuntimeException('Could not read the password.');
    }
    return rtrim($password, "\r\n");
}

if ($argc !== 4) {
    fwrite(STDERR, "Usage: php tools/create_user.php <admin|staff|venue> <email> <name>\n");
    exit(1);
}

[$script, $role, $email, $name] = $argv;
$name = trim($name);
$email = strtolower(trim($email));
if (!in_array($role, ['admin', 'staff', 'venue'], true)) {
    fwrite(STDERR, "Role must be admin, staff, or venue. Customer accounts use public registration.\n");
    exit(1);
}
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
    fwrite(STDERR, "Provide a valid email address (190 characters maximum).\n");
    exit(1);
}
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    fwrite(STDERR, "Name must be between 2 and 100 characters.\n");
    exit(1);
}

try {
    $password = prompt_hidden_password('Enter password (hidden): ');
    fwrite(STDERR, PHP_EOL . 'Confirm password (hidden): ');
    $confirmation = prompt_hidden_password('');
    if (strlen($password) < 8 || !hash_equals($password, $confirmation)) {
        fwrite(STDERR, PHP_EOL . "Password confirmation failed or password is shorter than 8 characters.\n");
        exit(1);
    }

    $statement = db()->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);
    fwrite(STDOUT, sprintf("%s account created for %s.\n", ucfirst($role), $email));
} catch (PDOException $error) {
    if (is_unique_violation($error)) {
        fwrite(STDERR, "An account with that email address already exists.\n");
        exit(1);
    }
    log_application_error($error);
    fwrite(STDERR, "Could not create the account. Check database configuration and the private application log.\n");
    exit(1);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
