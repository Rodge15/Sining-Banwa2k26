<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';

$events = list_published_events();
$pageTitle = 'Events';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <p class="eyebrow mb-2">Sining Banwa</p>
    <h1 class="display-6 mb-4">Published events</h1>
    <?php if ($events === []): ?>
        <div class="panel">
            <h2 class="h5">No published events yet</h2>
            <p class="mb-0">Please check back soon for upcoming performances and activities.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($events as $event): ?>
                <div class="col-md-6 col-xl-4">
                    <?php require PROJECT_ROOT . '/app/views/event_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>