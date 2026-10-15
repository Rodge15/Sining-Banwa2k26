<?php
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <div class="panel text-center">
        <p class="eyebrow">Access restricted</p>
        <h1 class="h2">You do not have permission to view this page.</h1>
        <p class="text-muted">Sign in with an account that has the required access.</p>
        <a class="btn btn-primary" href="<?= e(app_url('/dashboard.php')) ?>">Return to dashboard</a>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
