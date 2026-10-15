<?php
require_once dirname(__DIR__) . '/app/auth.php';
redirect_authenticated_user();

$errorMessage = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $errorMessage = 'Your form session expired. Please try again.';
    } else {
        $statement = db()->prepare(
            'SELECT id, password_hash, is_active FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $account = $statement->fetch();
        if (
            $account !== false
            && (int) $account['is_active'] === 1
            && password_verify($password, (string) $account['password_hash'])
        ) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $account['id'];
            unset($_SESSION['_csrf_token']);
            redirect('/dashboard.php');
        }

        $errorMessage = 'The email and password did not match an active account.';
    }
}

$pageTitle = 'Log in';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <p class="eyebrow">Welcome back</p>
        <h1 class="h2 mb-2">Log in</h1>
        <p class="text-muted mb-4">Sign in to open the dashboard for your account role.</p>
        <?php require PROJECT_ROOT . '/app/views/flash.php'; ?>
        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/login.php')) ?>" autocomplete="on">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="username" required>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-pressed="false">Show</button>
                </div>
            </div>
            <button class="btn btn-primary w-100" type="submit">Log in</button>
        </form>
        <p class="text-center mt-4 mb-0">Need a customer account? <a href="<?= e(app_url('/register.php')) ?>">Register</a></p>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
