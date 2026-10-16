<?php
$pageTitle = $pageTitle ?? 'Sining Banwa';
$navUser = current_user();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Web-Based Event Management System for Sining Banwa with SMS Notification.">
    <title><?= e($pageTitle) ?> | Sining Banwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= e(app_url('/index.php')) ?>">Sining Banwa</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <?php if ($navUser !== null): ?>
                        <a class="nav-link" href="<?= e(app_url('/dashboard.php')) ?>">Dashboard</a>
                        <a class="nav-link" href="<?= e(app_url('/events.php')) ?>">Events</a>
                        <?php if (in_array($navUser['role'], ['admin', 'staff'], true)): ?>
                            <a class="nav-link" href="<?= e(app_url('/manage_events.php')) ?>">Manage events</a>
                        <?php endif; ?>
                        <form method="post" action="<?= e(app_url('/logout.php')) ?>" class="d-inline">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <button class="btn btn-outline-light btn-sm" type="submit">Log out</button>
                        </form>
                    <?php else: ?>
                        <a class="nav-link" href="<?= e(app_url('/events.php')) ?>">Events</a>
                        <a class="nav-link" href="<?= e(app_url('/login.php')) ?>">Log in</a>
                        <a class="btn btn-light btn-sm" href="<?= e(app_url('/register.php')) ?>">Create account</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
<main class="site-main">
