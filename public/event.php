<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';
require_once PROJECT_ROOT . '/app/seating.php';

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
$sections = load_event_seating((int) $eventId);

$pageTitle = (string) $event['title'];
require PROJECT_ROOT . '/app/views/header.php';
?>
<article class="container content-section event-detail">
    <p class="eyebrow mb-2"><?= e(format_event_date((string) $event['starts_at'])) ?></p>
    <h1 class="display-6 mb-3"><?= e((string) $event['title']) ?></h1>
    <p class="lead text-muted"><?= e((string) $event['venue']) ?></p>
    <?php if ($event['poster_filename'] !== null): ?>
        <a class="poster-launch mb-4" href="<?= e(app_url('/view_event_poster.php?id=' . (int) $event['id'] . '&return=' . rawurlencode('/event.php?id=' . (int) $event['id']))) ?>" data-poster-open data-poster-src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" data-poster-title="<?= e((string) $event['title']) ?>">
            <img class="event-poster-detail" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>. Open to view, then use Back or X to return.">
        </a>
    <?php endif; ?>
    <div class="event-description">
        <?php if (trim((string) $event['description']) === ''): ?>
            <p>Event details will be announced soon.</p>
        <?php else: ?>
            <p><?= nl2br(e((string) $event['description'])) ?></p>
        <?php endif; ?>
    </div>
    <aside class="panel mt-4">
        <h2 class="h5">Customer account</h2>
        <?php if (current_user() === null): ?>
            <p>Create a free customer account to manage your details. Online seat reservation is not open yet.</p>
            <a class="btn btn-primary" href="<?= e(app_url('/register.php')) ?>">Create account</a>
            <a class="btn btn-outline-primary" href="<?= e(app_url('/login.php')) ?>">Log in</a>
        <?php else: ?>
            <p class="mb-3">You are signed in as <?= e(current_user()['name']) ?>. Online seat reservation is not open yet.</p>
            <a class="btn btn-outline-primary" href="<?= e(app_url('/profile.php')) ?>">Manage profile</a>
        <?php endif; ?>
    </aside>
    <?php if ($sections !== []): ?>
        <section class="event-seating mt-5" aria-labelledby="event-seating-title">
            <h2 class="h3 mb-2" id="event-seating-title">Seating and prices</h2>
            <p class="text-muted">Seat prices and payment colors are shown below. Selecting a seat does not reserve it.</p>
            <?php require PROJECT_ROOT . '/app/views/seat_status_legend.php'; ?>
            <?php foreach ($sections as $section): ?>
                <section class="panel mb-4">
                    <h3 class="h5"><?= e($section['name']) ?></h3>
                    <p class="text-muted small">Default price: ₱<?= e(number_format((float) $section['default_price'], 2)) ?></p>
                    <?php require PROJECT_ROOT . '/app/views/seat_box_grid.php'; ?>
                </section>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
    <a class="btn btn-outline-secondary mt-3" href="<?= e(app_url('/events.php')) ?>">Back to events</a>
</article>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>