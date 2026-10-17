<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';

$user = require_login();
$errors = [];
$name = $user['name'];
$phone = $user['phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
    $changingPassword = $currentPassword !== '' || $newPassword !== '' || $passwordConfirmation !== '';

    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Enter a name between 2 and 100 characters.';
    }
    if ($phone !== '' && mb_strlen($phone) > 30) {
        $errors[] = 'The phone number must be 30 characters or fewer.';
    }
    if ($changingPassword) {
        if ($currentPassword === '' || $newPassword === '' || $passwordConfirmation === '') {
            $errors[] = 'Enter your current password, a new password, and the confirmation to change your password.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'Your new password must be at least 8 characters long.';
        } elseif ($newPassword !== $passwordConfirmation) {
            $errors[] = 'The new password confirmation does not match.';
        }
    }

    if ($errors === []) {
        $statement = db()->prepare(
            'SELECT password_hash, is_active FROM users WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $user['id']]);
        $account = $statement->fetch();
        if ($account === false || (int) $account['is_active'] !== 1) {
            unset($_SESSION['user_id']);
            set_flash('warning', 'Your account is no longer active. Please sign in again.');
            redirect('/login.php');
        }
        if ($changingPassword && !password_verify($currentPassword, (string) $account['password_hash'])) {
            $errors[] = 'The current password is not correct.';
        }
    }

    if ($errors === []) {
        $parameters = [
            'name' => $name,
            'phone' => $phone === '' ? null : $phone,
            'id' => $user['id'],
        ];
        $passwordSql = '';
        if ($changingPassword) {
            $parameters['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordSql = ', password_hash = :password_hash';
        }

        $update = db()->prepare(
            'UPDATE users SET name = :name, phone = :phone' . $passwordSql
            . ' WHERE id = :id AND is_active = 1'
        );
        $update->execute($parameters);
        $confirm = db()->prepare('SELECT is_active FROM users WHERE id = :id LIMIT 1');
        $confirm->execute(['id' => $user['id']]);
        $saved = $confirm->fetch();
        if ($saved === false || (int) $saved['is_active'] !== 1) {
            unset($_SESSION['user_id']);
            set_flash('warning', 'Your account is no longer active. Please sign in again.');
            redirect('/login.php');
        }

        if ($changingPassword) {
            session_regenerate_id(true);
        }
        set_flash('success', $changingPassword || $name !== $user['name'] || $phone !== $user['phone']
            ? 'Your profile has been updated.'
            : 'Your profile is already up to date.');
        redirect('/profile.php');
    }
}

$pageTitle = 'Profile';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <div class="auth-card mx-auto">
        <p class="eyebrow">Your account</p>
        <h1 class="h2 mb-2">Profile</h1>
        <p class="text-muted mb-4">Update your name and phone number. Your email and role cannot be changed here.</p>
        <?php require PROJECT_ROOT . '/app/views/flash.php'; ?>
        <?php if ($errors !== []): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?= e(app_url('/profile.php')) ?>" autocomplete="on">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= e($name) ?>" autocomplete="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control" id="email" type="email" value="<?= e($user['email']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label" for="role">Account role</label>
                <input class="form-control" id="role" type="text" value="<?= e(role_label($user['role'])) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label" for="phone">Phone number <span class="text-muted">(optional)</span></label>
                <input class="form-control" id="phone" name="phone" type="tel" maxlength="30" value="<?= e($phone) ?>" autocomplete="tel">
            </div>
            <fieldset class="border rounded p-3 mb-4">
                <legend class="float-none w-auto px-2 fs-6">Change password</legend>
                <p class="form-text">Leave these blank if you only want to update your name or phone number.</p>
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="new_password">New password</label>
                    <div class="input-group">
                        <input class="form-control" id="new_password" name="new_password" type="password" minlength="8" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" data-password-toggle="new_password" aria-pressed="false">Show</button>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="password_confirmation">Confirm new password</label>
                    <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password">
                </div>
            </fieldset>
            <button class="btn btn-primary w-100" type="submit">Save profile</button>
        </form>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
