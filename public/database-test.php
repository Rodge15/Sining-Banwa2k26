<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

if (env_value('APP_ENV') === 'production' || env_value('VERCEL') === '1') {
    http_response_code(404);
    exit;
}

$connected = false;
try {
    db();
    $connected = true;
} catch (Throwable $error) {
    log_application_error($error);
}

http_response_code($connected ? 200 : 503);
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database connection test | Sining Banwa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(app_url('/assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<main class="container content-section">
    <section class="panel">
        <?php if ($connected): ?>
            <p class="eyebrow">Connection test</p>
            <h1 class="h3">Database connection successful</h1>
            <p class="mb-0">The application connected to the configured database. This test does not check that migrations have been applied.</p>
        <?php else: ?>
            <p class="eyebrow">Connection test</p>
            <h1 class="h3">Database connection failed</h1>
            <p class="mb-0">Check your .env settings and make sure the configured database is running. Technical details were saved to the private application log when logging is available.</p>
        <?php endif; ?>
        <a class="btn btn-primary mt-4" href="<?= e(app_url('/index.php')) ?>">Return to homepage</a>
    </section>
</main>
</body>
</html>
