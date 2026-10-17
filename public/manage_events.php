<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';
$user = require_role(['admin', 'staff']);

$statement = db()->query(
    'SELECT id, title, venue, starts_at, status, poster_filename FROM events '
    . 'ORDER BY starts_at ASC, id ASC'
);
$events = $statement->fetchAll();
$pageTitle = 'Manage events';
require PROJECT_ROOT . '/app/views/header.php';
?>
<section class="container content-section">
    <?php require PROJECT_ROOT . '/app/views/flash.php'; ?>
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <p class="eyebrow mb-2">Event management</p>
            <h1 class="display-6 mb-0">Manage events</h1>
        </div>
        <a class="btn btn-primary" href="<?= e(app_url('/manage_event.php')) ?>">Create event</a>
    </div>
    <?php if ($events === []): ?>
        <div class="panel">
            <h2 class="h5">No events yet</h2>
            <p class="mb-0">Create a draft event to begin. Only published events appear on the public event pages.</p>
        </div>
    <?php else: ?>
        <div class="panel table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Event</th>
                        <th scope="col">Date and time</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $event['title']) ?></strong>
                                <div class="text-muted small"><?= e((string) $event['venue']) ?></div>
                                <?php if ($event['poster_filename'] !== null): ?>
                                    <a class="small" href="<?= e(app_url('/view_event_poster.php?id=' . (int) $event['id'] . '&return=' . rawurlencode('/manage_events.php'))) ?>" data-poster-open data-poster-src="<?= e(app_url('/event_poster.php?id=' . (int) $event['id'])) ?>" data-poster-title="<?= e((string) $event['title']) ?>">Preview poster</a>
                                <?php endif; ?>
                            </td>
                            <td><?= e(format_event_date((string) $event['starts_at'])) ?></td>
                            <td><span class="badge text-bg-secondary"><?= e(event_status_label((string) $event['status'])) ?></span></td>
                            <td class="text-end">
                                <div class="d-flex flex-column flex-lg-row justify-content-end gap-2">
                                    <a class="btn btn-outline-primary btn-sm" href="<?= e(app_url('/manage_event.php?id=' . (int) $event['id'])) ?>">Edit</a>
                                    <form method="post" action="<?= e(app_url('/update_event_status.php')) ?>" class="d-flex gap-2">
                                        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                        <label class="visually-hidden" for="status-<?= (int) $event['id'] ?>">Status for <?= e((string) $event['title']) ?></label>
                                        <select class="form-select form-select-sm" id="status-<?= (int) $event['id'] ?>" name="status">
                                            <?php foreach (EVENT_STATUSES as $status): ?>
                                                <option value="<?= e($status) ?>"<?= $event['status'] === $status ? ' selected' : '' ?>><?= e(event_status_label($status)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Save</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require PROJECT_ROOT . '/app/views/footer.php'; ?>