<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';

$eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    http_response_code(404);
    exit('Event not found.');
}

$user = current_user();
$isEventManager = $user !== null && in_array($user['role'], ['admin', 'staff'], true);
$sql = 'SELECT id, title, poster_filename, status FROM events WHERE id = :id';
if (!$isEventManager) {
    $sql .= " AND status = 'published'";
}
$sql .= ' LIMIT 1';
$statement = db()->prepare($sql);
$statement->execute(['id' => $eventId]);
$event = $statement->fetch();
if ($event === false || $event['poster_filename'] === null) {
    http_response_code(404);
    exit('Poster not found.');
}

$returnPath = safe_poster_return(isset($_GET['return']) ? (string) $_GET['return'] : null);
$pageTitle = 'Poster';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <div class="poster-page-toolbar">
        <h1 class="h3 mb-0"><?= e((string) $event['title']) ?></h1>
        <a class="poster-close-button" href="<?= e(app_url($returnPath)) ?>" aria-label="Close poster and go back">×</a>
    </div>
    <img class="poster-preview-image mt-3" src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" alt="Poster for <?= e((string) $event['title']) ?>">
    <div class="mt-3">
        <a class="btn btn-secondary" href="<?= e(app_url($returnPath)) ?>">Back</a>
    </div>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>
