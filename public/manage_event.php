<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';
$user = require_role(['admin', 'staff']);

$eventIdValue = $_GET['id'] ?? null;
$eventId = $eventIdValue === null ? null : filter_var($eventIdValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventIdValue !== null && $eventId === false) {
    http_response_code(404);
    exit('Event not found.');
}

$event = null;
if ($eventId !== null) {
    $statement = db()->prepare('SELECT * FROM events WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $eventId]);
    $event = $statement->fetch();
    if ($event === false) {
        http_response_code(404);
        exit('Event not found.');
    }
}

$defaultStart = (new DateTimeImmutable('+1 day', new DateTimeZone('Asia/Manila')))->format('Y-m-d\\TH:i');
$values = [
    'title' => (string) ($event['title'] ?? ''),
    'description' => (string) ($event['description'] ?? ''),
    'venue' => (string) ($event['venue'] ?? ''),
    'starts_at' => $event === null
        ? $defaultStart
        : (new DateTimeImmutable((string) $event['starts_at'], new DateTimeZone('Asia/Manila')))->format('Y-m-d\\TH:i'),
    'status' => (string) ($event['status'] ?? 'draft'),
];
$errors = [];
$posterError = null;
$generalError = null;
$createdEventId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['_csrf'] ?? null)) {
        $generalError = 'Your form session expired. Please try again.';
    } else {
        $rawValues = [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'venue' => trim((string) ($_POST['venue'] ?? '')),
            'starts_at' => trim((string) ($_POST['starts_at'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'draft'),
        ];
        [$normalizedValues, $errors] = validate_event_fields($rawValues);
        $values = $rawValues;
        $newPosterFilename = null;
        $posterUpload = $_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE];
        if (($posterUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $errors === []) {
            try {
                $newPosterFilename = store_event_poster($posterUpload);
            } catch (InvalidArgumentException $error) {
                $posterError = $error->getMessage();
            } catch (RuntimeException $error) {
                log_application_error($error);
                $posterError = 'The poster could not be saved. Check the private uploads folder and try again.';
            }
        }

        if ($errors === [] && $posterError === null) {
            $posterFilename = $newPosterFilename ?? ($event['poster_filename'] ?? null);
            try {
                if ($event === null) {
                    $statement = db()->prepare(
                        'INSERT INTO events (title, description, venue, starts_at, poster_filename, status, created_by) '
                        . 'VALUES (:title, :description, :venue, :starts_at, :poster_filename, :status, :created_by)'
                    );
                    $statement->execute([
                        'title' => $normalizedValues['title'],
                        'description' => $normalizedValues['description'] === '' ? null : $normalizedValues['description'],
                        'venue' => $normalizedValues['venue'],
                        'starts_at' => $normalizedValues['starts_at'],
                        'poster_filename' => $posterFilename,
                        'status' => $normalizedValues['status'],
                        'created_by' => $user['id'],
                    ]);
                    $createdEventId = (int) db()->lastInsertId();
                    set_flash('success', 'Event created.');
                } else {
                    $statement = db()->prepare(
                        'UPDATE events SET title = :title, description = :description, venue = :venue, '
                        . 'starts_at = :starts_at, poster_filename = :poster_filename, status = :status WHERE id = :id'
                    );
                    $statement->execute([
                        'title' => $normalizedValues['title'],
                        'description' => $normalizedValues['description'] === '' ? null : $normalizedValues['description'],
                        'venue' => $normalizedValues['venue'],
                        'starts_at' => $normalizedValues['starts_at'],
                        'poster_filename' => $posterFilename,
                        'status' => $normalizedValues['status'],
                        'id' => $eventId,
                    ]);
                    set_flash('success', 'Event updated.');
                }
            } catch (Throwable $error) {
                if ($newPosterFilename !== null) {
                    delete_event_poster($newPosterFilename);
                }
                throw $error;
            }

            if ($newPosterFilename !== null && $event !== null) {
                delete_event_poster((string) $event['poster_filename']);
            }
            if ($createdEventId !== null) {
                set_flash('success', 'Event created. You can now design its seat layout and set prices.');
                redirect('/manage_event.php?id=' . $createdEventId);
            }
            redirect('/manage_events.php');
        }
    }
}

$pageTitle = $event === null ? 'Create event' : 'Edit event';
$formAction = app_url('/manage_event.php' . ($eventId === null ? '' : '?id=' . $eventId));
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <div class="mb-4">
        <p class="eyebrow mb-2">Event management</p>
        <h1 class="display-6"><?= e($pageTitle) ?></h1>
    </div>
    <?php if ($event !== null): ?>
        <div class="panel d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Seat layout and pricing</h2>
                <p class="text-muted mb-0">Open the box layout, click boxes to place seats, and set section or individual seat prices.</p>
            </div>
            <a class="btn btn-outline-primary" href="<?= e(app_url('/manage_seating.php?event_id=' . (int) $event['id'])) ?>">Design seats</a>
        </div>
    <?php endif; ?>
    <?php if ($generalError !== null): ?>
        <div class="alert alert-danger" role="alert"><?= e($generalError) ?></div>
    <?php endif; ?>
    <form class="panel event-form" method="post" action="<?= e($formAction) ?>" enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="mb-3">
            <label class="form-label" for="title">Event title</label>
            <input class="form-control<?= isset($errors['title']) ? ' is-invalid' : '' ?>" id="title" name="title" type="text" maxlength="160" value="<?= e($values['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Description</label>
            <textarea class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>" id="description" name="description" rows="6" maxlength="10000"><?= e($values['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
        </div>
        <div class="row g-3">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="venue">Venue</label>
                <input class="form-control<?= isset($errors['venue']) ? ' is-invalid' : '' ?>" id="venue" name="venue" type="text" maxlength="190" value="<?= e($values['venue']) ?>" required>
                <?php if (isset($errors['venue'])): ?><div class="invalid-feedback"><?= e($errors['venue']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="starts_at">Date and time (Philippine time)</label>
                <input class="form-control<?= isset($errors['starts_at']) ? ' is-invalid' : '' ?>" id="starts_at" name="starts_at" type="datetime-local" value="<?= e($values['starts_at']) ?>" required>
                <?php if (isset($errors['starts_at'])): ?><div class="invalid-feedback"><?= e($errors['starts_at']) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="status">Status</label>
                <select class="form-select<?= isset($errors['status']) ? ' is-invalid' : '' ?>" id="status" name="status">
                    <?php foreach (EVENT_STATUSES as $status): ?>
                        <option value="<?= e($status) ?>"<?= $values['status'] === $status ? ' selected' : '' ?>><?= e(event_status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><div class="invalid-feedback"><?= e($errors['status']) ?></div><?php endif; ?>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="poster">Poster (optional; JPG, PNG, or WebP; max 5 MiB)</label>
                <input class="form-control<?= $posterError !== null ? ' is-invalid' : '' ?>" id="poster" name="poster" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if ($posterError !== null): ?><div class="invalid-feedback"><?= e($posterError) ?></div><?php endif; ?>
                <?php if (!empty($event['poster_filename'])): ?>
                    <a class="btn btn-link btn-sm px-0 d-block mt-2" href="<?= e(app_url('/view_event_poster.php?id=' . (int) $event['id'] . '&return=' . rawurlencode('/manage_event.php?id=' . (int) $event['id']))) ?>" data-poster-open data-poster-src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" data-poster-title="<?= e((string) $event['title']) ?>">View current poster</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
            <button class="btn btn-primary" type="submit">Save event</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_url('/manage_events.php')) ?>">Cancel</a>
        </div>
    </form>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>