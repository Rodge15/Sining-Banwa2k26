<?php
require_once dirname(__DIR__) . '/app/auth.php';
$pageTitle = 'Home';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="hero">
    <div class="container">
        <p class="eyebrow">Arts, culture, community</p>
        <h1 class="display-3 mb-4">Sining Banwa</h1>
        <p class="hero-copy fs-5 mb-4">Web-Based Event Management System for Sining Banwa with SMS Notification</p>
        <p class="hero-copy mb-4">A home for the people, performances, and creative work of Albay. This system is being built to support Sining Banwa's events and audience.</p>
        <div class="d-flex flex-column flex-sm-row gap-3">
            <?php if (current_user() !== null): ?>
                <a class="btn btn-light btn-lg" href="<?= e(app_url('/dashboard.php')) ?>">Go to your dashboard</a>
            <?php else: ?>
                <a class="btn btn-light btn-lg" href="<?= e(app_url('/register.php')) ?>">Create a customer account</a>
                <a class="btn btn-outline-light btn-lg" href="<?= e(app_url('/login.php')) ?>">Log in</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="container content-section">
    <div class="row g-4">
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">Built for the community</p>
                <h2>Culture and connection</h2>
                <p class="mb-0">A digital starting point for Sining Banwa's cultural programs and audiences.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">A simple foundation</p>
                <h2>One organized system</h2>
                <p class="mb-0">The project will grow in phases, with each workflow added and tested deliberately.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">Current phase</p>
                <h2>Accounts and access</h2>
                <p class="mb-0">Customer sign-up and role-based account access are available in this foundation.</p>
            </article>
        </div>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
