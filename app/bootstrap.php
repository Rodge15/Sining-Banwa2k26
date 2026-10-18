<?php
declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/app/env.php';
load_environment(PROJECT_ROOT . '/.env');
require_once PROJECT_ROOT . '/config/database.php';
require_once PROJECT_ROOT . '/app/helpers.php';
require_once PROJECT_ROOT . '/app/session.php';
require_once PROJECT_ROOT . '/app/storage.php';

date_default_timezone_set('Asia/Manila');

function log_application_error(Throwable $error): void
{
    $message = sprintf(
        "[%s] %s in %s:%d\n",
        date(DATE_ATOM),
        $error->getMessage(),
        $error->getFile(),
        $error->getLine()
    );
    $previous = $error->getPrevious();
    while ($previous !== null) {
        $message .= sprintf("Caused by: %s\n", $previous->getMessage());
        $previous = $previous->getPrevious();
    }

    $logFile = PROJECT_ROOT . '/storage/logs/app.log';
    if (@file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX) === false) {
        error_log(trim($message));
    }
}

set_exception_handler(static function (Throwable $error): void {
    log_application_error($error);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Service unavailable</title>';
    echo '<body><main><h1>We could not complete that request.</h1>';
    echo '<p>Please check the application setup and try again.</p></main></body></html>';
});

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
$configuredBaseUrl = trim((string) env_value('APP_BASE_URL', ''));
if ($configuredBaseUrl !== '') {
    $baseUrl = '/' . trim($configuredBaseUrl, '/');
} elseif (preg_match('~^(.*?/public)(?:/|$)~', $scriptName, $matches) === 1) {
    $baseUrl = $matches[1];
} else {
    $baseUrl = '';
}
define('BASE_URL', $baseUrl);

$isHttps = request_is_https();
if (session_status() !== PHP_SESSION_ACTIVE) {
    if (session_driver() === 'cookie') {
        session_set_save_handler(new CookieSessionHandler(), true);
        ob_start();
        register_shutdown_function(static function (): void {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
        });
    }
    session_name('sining_banwa_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
