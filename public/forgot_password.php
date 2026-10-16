<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
redirect_authenticated_user();

$email = '';
$message = null;
$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $errorMessage = 'Your form session expired. Please try again.';
    } else {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190) {
            $statement = db()->prepare(
                'SELECT id, email FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $account = $statement->fetch();

            if ($account !== false) {
                $appUrl = trim((string) env_value('APP_URL', ''));
                $urlParts = parse_url($appUrl);
                $fromEmail = trim((string) env_value('MAIL_FROM_EMAIL', ''));
                $fromName = str_replace(["\r", "\n"], '', trim((string) env_value('MAIL_FROM_NAME', 'Sining Banwa')));
                if (
                    $urlParts === false
                    || !in_array($urlParts['scheme'] ?? '', ['http', 'https'], true)
                    || empty($urlParts['host'])
                    || isset($urlParts['user'])
                    || isset($urlParts['pass'])
                    || isset($urlParts['query'])
                    || isset($urlParts['fragment'])
                    || filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false
                ) {
                    log_application_error(new RuntimeException('Password reset email configuration is invalid.'));
                } else {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $connection = db();
                    $connection->beginTransaction();
                    $delete = $connection->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
                    $delete->execute(['user_id' => (int) $account['id']]);
                    $insert = $connection->prepare(
                        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) '
                        . 'VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR))'
                    );
                    $insert->execute([
                        'user_id' => (int) $account['id'],
                        'token_hash' => $tokenHash,
                    ]);
                    $connection->commit();

                    $resetUrl = rtrim($appUrl, '/') . '/reset_password.php?token=' . rawurlencode($token);
                    $subject = 'Reset your Sining Banwa password';
                    $body = "We received a request to reset the password for your Sining Banwa account.\n\n"
                        . "Use this link within one hour to choose a new password:\n"
                        . $resetUrl . "\n\n"
                        . "If you did not request this, you can ignore this email.";
                    $headers = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, "\\\""), $fromEmail)
                        . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                    if (!mail((string) $account['email'], $subject, $body, $headers)) {
                        log_application_error(new RuntimeException('Password reset email could not be sent. Check the PHP mail transport configuration.'));
                    }
                }
            }
        }

        $message = 'If an active account uses that email address, you will receive a password reset link shortly. Check your spam folder or contact the site administrator if no email arrives.';
    }
}

$pageTitle = 'Forgot password';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <p class="eyebrow">Account recovery</p>
        <h1 class="h2 mb-2">Forgot password?</h1>
        <p class="text-muted mb-4">Enter your account email and we will send a password reset link if it matches an active account.</p>
        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($message !== null): ?>
            <div class="alert alert-info" role="status"><?= e($message) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/forgot_password.php')) ?>" autocomplete="on">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-4">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="username" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Send reset link</button>
        </form>
        <p class="text-center mt-4 mb-0"><a href="<?= e(app_url('/login.php')) ?>">Back to log in</a></p>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>