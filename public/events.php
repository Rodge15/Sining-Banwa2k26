<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';

$statement = db()->query(
    "SELECT id, title, description, venue, starts_at, poster_filename FROM events "
    . "WHERE status = 'published' ORDER BY starts_at ASC, id ASC"
);
$events = $statement->fetchAll();
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
                    <article class="feature-card event-card">
                        <?php if ($event['poster_filename'] !== null): ?>
                            <img class="event-poster-preview mb-3" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>">
                        <?php endif; ?>
                        <p class="eyebrow mb-2"><?= e(format_event_date((string) $event['starts_at'])) ?></p>
                        <h2 class="h4"><?= e((string) $event['title']) ?></h2>
                        <p class="text-muted mb-2"><?= e((string) $event['venue']) ?></p>
                        <?php if (trim((string) $event['description']) !== ''): ?>
                            <p><?= e(mb_strlen((string) $event['description']) > 180 ? mb_substr((string) $event['description'], 0, 177) . '...' : (string) $event['description']) ?></p>
                        <?php endif; ?>
                        <a class="btn btn-outline-primary mt-auto" href="<?= e(app_url('/event.php?id=' . (int) $event['id'])) ?>">Event details</a>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>