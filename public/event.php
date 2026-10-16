<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';

$eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    http_response_code(404);
    exit('Event not found.');
}
$statement = db()->prepare(
    "SELECT id, title, description, venue, starts_at, poster_filename FROM events "
    . "WHERE id = :id AND status = 'published' LIMIT 1"
);
$statement->execute(['id' => $eventId]);
$event = $statement->fetch();
if ($event === false) {
    http_response_code(404);
    exit('Event not found.');
}

$pageTitle = (string) $event['title'];
require PROJECT_ROOT . '/app/views/header.php';
?>
<article class="container content-section event-detail">
    <p class="eyebrow mb-2"><?= e(format_event_date((string) $event['starts_at'])) ?></p>
    <h1 class="display-6 mb-3"><?= e((string) $event['title']) ?></h1>
    <p class="lead text-muted"><?= e((string) $event['venue']) ?></p>
    <?php if ($event['poster_filename'] !== null): ?>
        <img class="event-poster-detail mb-4" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>">
    <?php endif; ?>
    <div class="event-description">
        <?php if (trim((string) $event['description']) === ''): ?>
            <p>Event details will be announced soon.</p>
        <?php else: ?>
            <p><?= nl2br(e((string) $event['description'])) ?></p>
        <?php endif; ?>
    </div>
    <a class="btn btn-outline-secondary mt-3" href="<?= e(app_url('/events.php')) ?>">Back to events</a>
</article>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>