<?php
require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';

$featuredEvents = list_published_events(3);
$pageTitle = 'Home';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="hero">
    <div class="container">
        <p class="eyebrow">Arts, culture, community</p>
        <h1 class="display-3 mb-4">Sining Banwa</h1>
        <p class="hero-copy fs-5 mb-4">Performances and cultural activities from Albay</p>
        <p class="hero-copy mb-4">Browse published events, view seating and prices, and create a customer account. Online seat reservation is not open yet.</p>
        <div class="d-flex flex-column flex-sm-row gap-3">
            <a class="btn btn-light btn-lg" href="<?= e(app_url('/events.php')) ?>">Browse events</a>
            <?php if (current_user() !== null): ?>
                <a class="btn btn-outline-light btn-lg" href="<?= e(app_url('/dashboard.php')) ?>">Go to your dashboard</a>
            <?php else: ?>
                <a class="btn btn-outline-light btn-lg" href="<?= e(app_url('/register.php')) ?>">Create a customer account</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="container content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Upcoming</p>
            <h2 class="display-6 mb-0">Published events</h2>
        </div>
        <a class="btn btn-outline-primary" href="<?= e(app_url('/events.php')) ?>">View all events</a>
    </div>
    <?php if ($featuredEvents === []): ?>
        <div class="panel mb-5">
            <h3 class="h5">No published events yet</h3>
            <p class="mb-0">Please check back soon for upcoming performances and activities.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-5">
            <?php foreach ($featuredEvents as $event): ?>
                <div class="col-md-6 col-xl-4">
                    <?php require PROJECT_ROOT . '/app/views/event_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">For the audience</p>
                <h2>Culture and connection</h2>
                <p class="mb-0">Find Sining Banwa performances, workshops, and community activities in one place.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">Your account</p>
                <h2>A customer profile</h2>
                <p class="mb-0">Register to manage your name, phone number, and password. Staff accounts are created separately.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="feature-card">
                <p class="eyebrow">Before you book</p>
                <h2>Seats and prices</h2>
                <p class="mb-0">Published events can show their seat layout and prices. Selecting a seat does not reserve it yet.</p>
            </article>
        </div>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
