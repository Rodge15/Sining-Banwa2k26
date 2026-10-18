<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';

$eventId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($eventId === false) {
    http_response_code(404);
    exit;
}

$user = current_user();
$isEventManager = $user !== null && in_array($user['role'], ['admin', 'staff'], true);
$sql = 'SELECT poster_filename, status FROM events WHERE id = :id';
if (!$isEventManager) {
    $sql .= " AND status = 'published'";
}
$sql .= ' LIMIT 1';
$statement = db()->prepare($sql);
$statement->execute(['id' => $eventId]);
$event = $statement->fetch();
if ($event === false || $event['poster_filename'] === null) {
    http_response_code(404);
    exit;
}

$filename = (string) $event['poster_filename'];
$bytes = read_poster_bytes($filename);
if ($bytes === null) {
    http_response_code(404);
    exit;
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->buffer($bytes);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!is_string($mimeType) || !in_array($mimeType, $allowedTypes, true) || getimagesizefromstring($bytes) === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . (string) strlen($bytes));
header('Content-Disposition: inline; filename="event-poster.' . pathinfo($filename, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
echo $bytes;