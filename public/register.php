<?php
require_once dirname(__DIR__) . '/app/auth.php';
redirect_authenticated_user();

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Enter a name between 2 and 100 characters.';
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($phone !== '' && mb_strlen($phone) > 30) {
        $errors[] = 'The phone number must be 30 characters or fewer.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Your password must be at least 8 characters long.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'The password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $statement = db()->prepare(
                'INSERT INTO users (name, email, phone, password_hash, role) '
                . 'VALUES (:name, :email, :phone, :password_hash, :role)'
            );
            $statement->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone === '' ? null : $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'customer',
            ]);

            set_flash('success', 'Your customer account has been created. You can now sign in.');
            redirect('/login.php');
        } catch (PDOException $error) {
            if (is_unique_violation($error)) {
                $errors[] = 'An account with that email address already exists.';
            } else {
                throw $error;
            }
        }
    }
}

$pageTitle = 'Create account';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="auth-wrap">
    <div class="auth-card">
        <p class="eyebrow">Customer registration</p>
        <h1 class="h2 mb-2">Create your account</h1>
        <p class="text-muted mb-4">Registration creates a customer account. Staff and venue accounts are provisioned separately.</p>
        <?php require PROJECT_ROOT . '/app/views/flash.php'; ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/register.php')) ?>" autocomplete="on">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e($name) ?>" autocomplete="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" id="email" name="email" type="email" maxlength="190" value="<?= e($email) ?>" autocomplete="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="phone">Phone number <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="phone" name="phone" type="tel" maxlength="30" value="<?= e($phone) ?>" autocomplete="tel">
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <input class="form-control" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-pressed="false">Show</button>
                </div>
                <div class="form-text">Use at least 8 characters.</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Create customer account</button>
        </form>
        <p class="text-center mt-4 mb-0">Already registered? <a href="<?= e(app_url('/login.php')) ?>">Log in</a></p>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
