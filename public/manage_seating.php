<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/seating.php';
$user = require_role(['admin', 'staff']);

$eventId = filter_var($_GET['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    http_response_code(404);
    exit('Event not found.');
}
$eventStatement = db()->prepare('SELECT id, title FROM events WHERE id = :id LIMIT 1');
$eventStatement->execute(['id' => $eventId]);
$event = $eventStatement->fetch();
if ($event === false) {
    http_response_code(404);
    exit('Event not found.');
}
$sections = load_event_seating((int) $eventId);

$pageTitle = 'Seat layout';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Seat layout and pricing</p>
            <h1 class="display-6 mb-1"><?= e((string) $event['title']) ?></h1>
            <p class="text-muted mb-0">Arrange seats by dragging them. Seat positions, section prices, and overrides are saved for this event.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(app_url('/manage_event.php?id=' . (int) $event['id'])) ?>">Back to event</a>
    </div>
    <div id="seating-designer"
         data-save-url="<?= e(app_url('/save_event_seating.php')) ?>"
         data-csrf="<?= e(csrf_token()) ?>"
         data-initial="<?= e((string) json_encode($sections, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>">
        <div class="alert alert-info" role="status">Create sections, rows, and seats. The section price is the default; set a seat override only when that seat has a different price.</div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button class="btn btn-outline-primary" type="button" data-add-section>Add section</button>
            <button class="btn btn-primary" type="button" data-save-layout>Save seat layout</button>
            <span class="align-self-center text-muted small" data-save-status aria-live="polite"></span>
        </div>
        <div data-seating-sections></div>
    </div>
</section>
<script src="<?= e(app_url('/assets/js/seating-designer.js')) ?>"></script>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
