<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
redirect_authenticated_user();
header('Referrer-Policy: no-referrer');

$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $errorMessage = 'Your form session expired. Please try again.';
    } elseif (strlen($password) < 8 || !hash_equals($password, $confirmation)) {
        $errorMessage = 'Passwords must match and be at least 8 characters long.';
    } elseif (preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
        $errorMessage = 'This reset link is invalid or has expired. Request a new one.';
    } else {
        $connection = db();
        $connection->beginTransaction();
        $statement = $connection->prepare(
            'SELECT u.id FROM password_reset_tokens AS t '
            . 'INNER JOIN users AS u ON u.id = t.user_id '
            . 'WHERE t.token_hash = :token_hash AND t.expires_at > CURRENT_TIMESTAMP AND u.is_active = 1 '
            . 'LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $account = $statement->fetch();

        if ($account === false) {
            $connection->rollBack();
            $errorMessage = 'This reset link is invalid or has expired. Request a new one.';
        } else {
            $update = $connection->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $account['id'],
            ]);
            $delete = $connection->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
            $delete->execute(['user_id' => (int) $account['id']]);
            $connection->commit();
            set_flash('success', 'Your password has been reset. You can now log in.');
            redirect('/login.php');
        }
    }
}

$tokenIsValidFormat = preg_match('/^[a-f0-9]{64}$/D', $token) === 1;
$pageTitle = 'Reset password';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <p class="eyebrow">Account recovery</p>
        <h1 class="h2 mb-2">Choose a new password</h1>
        <p class="text-muted mb-4">Use at least 8 characters. This reset link can only be used once.</p>
        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($tokenIsValidFormat): ?>
            <form method="post" action="<?= e(app_url('/reset_password.php')) ?>" autocomplete="on">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Reset password</button>
            </form>
        <?php else: ?>
            <p class="mb-0"><a href="<?= e(app_url('/forgot_password.php')) ?>">Request a new reset link</a></p>
        <?php endif; ?>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>