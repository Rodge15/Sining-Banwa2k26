<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/events.php';
require_role(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['_csrf'] ?? null)) {
    http_response_code(400);
    exit('Invalid request.');
}
$eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$status = (string) ($_POST['status'] ?? '');
if ($eventId === false || !in_array($status, EVENT_STATUSES, true)) {
    http_response_code(400);
    exit('Invalid event update.');
}

$statement = db()->prepare('UPDATE events SET status = :status WHERE id = :id');
$statement->execute(['status' => $status, 'id' => $eventId]);
if ($statement->rowCount() === 0) {
    $check = db()->prepare('SELECT id FROM events WHERE id = :id LIMIT 1');
    $check->execute(['id' => $eventId]);
    if ($check->fetch() === false) {
        http_response_code(404);
        exit('Event not found.');
    }
}
set_flash('success', 'Event status updated.');
redirect('/manage_events.php');