<section class="dashboard-welcome">
    <p class="eyebrow">Administrator dashboard</p>
    <h1 class="display-6">Welcome, <?= e($user['name']) ?></h1>
    <p class="lead">Create and manage Sining Banwa events, including their publication status and public details.</p>
</section>
<section class="panel">
    <h2 class="h5">Event management</h2>
    <p class="mb-0">Create, edit, publish, complete, or cancel events. Other management tools will be added in later phases. <a href="<?= e(app_url('/manage_events.php')) ?>">Open event management</a>.</p>
</section>
