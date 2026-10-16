<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/auth.php';
require_once PROJECT_ROOT . '/app/seating.php';
require_role(['admin', 'staff']);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Use POST to save the seat layout.']);
    exit;
}
if (!verify_csrf_token($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session expired. Reload the page and try again.']);
    exit;
}
$contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
if ($contentType !== 'application/json') {
    http_response_code(415);
    echo json_encode(['error' => 'Send the seat layout as JSON.']);
    exit;
}
$contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? 0, FILTER_VALIDATE_INT);
if ($contentLength === false || $contentLength > 2097152) {
    http_response_code(413);
    echo json_encode(['error' => 'The seat layout is too large to save in one request.']);
    exit;
}
$requestBody = file_get_contents('php://input');
if ($requestBody === false || strlen($requestBody) > 2097152) {
    http_response_code(413);
    echo json_encode(['error' => 'The seat layout is too large to save in one request.']);
    exit;
}
$request = json_decode($requestBody, true);
$eventId = is_array($request) ? filter_var($request['event_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
if (!is_array($request) || $eventId === false || !isset($request['sections']) || !is_array($request['sections'])) {
    http_response_code(400);
    echo json_encode(['error' => 'The seat layout request is invalid.']);
    exit;
}

try {
    save_event_seating((int) $eventId, $request['sections']);
    echo json_encode(['saved' => true, 'message' => 'Seat layout and prices saved.']);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['error' => $error->getMessage()]);
} catch (RuntimeException $error) {
    if ($error->getMessage() === 'Event not found.') {
        http_response_code(404);
        echo json_encode(['error' => $error->getMessage()]);
        exit;
    }
    throw $error;
}
