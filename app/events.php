<?php
declare(strict_types=1);

const EVENT_STATUSES = ['draft', 'published', 'completed', 'cancelled'];
const EVENT_POSTER_MAX_BYTES = 5242880;

function event_status_label(string $status): string
{
    return ucfirst($status);
}

function format_event_date(string $dateTime): string
{
    return (new DateTimeImmutable($dateTime, new DateTimeZone('Asia/Manila')))->format('M j, Y g:i A');
}

function validate_event_fields(array $input): array
{
    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $venue = trim((string) ($input['venue'] ?? ''));
    $startValue = trim((string) ($input['starts_at'] ?? ''));
    $status = (string) ($input['status'] ?? 'draft');
    $errors = [];

    if ($title === '' || mb_strlen($title) > 160) {
        $errors['title'] = 'Enter an event title no longer than 160 characters.';
    }
    if (mb_strlen($description) > 10000) {
        $errors['description'] = 'The description must be 10,000 characters or fewer.';
    }
    if ($venue === '' || mb_strlen($venue) > 190) {
        $errors['venue'] = 'Enter a venue no longer than 190 characters.';
    }

    $dateTime = DateTimeImmutable::createFromFormat(
        '!Y-m-d\TH:i',
        $startValue,
        new DateTimeZone('Asia/Manila')
    );
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        $dateTime === false
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $dateTime->format('Y-m-d\TH:i') !== $startValue
    ) {
        $errors['starts_at'] = 'Enter a valid event date and time.';
    }
    if (!in_array($status, EVENT_STATUSES, true)) {
        $errors['status'] = 'Choose a valid event status.';
    }

    return [
        [
            'title' => $title,
            'description' => $description,
            'venue' => $venue,
            'starts_at' => $dateTime === false ? '' : $dateTime->format('Y-m-d H:i:s'),
            'status' => $status,
        ],
        $errors,
    ];
}

function store_event_poster(array $upload): string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Choose a valid JPG, PNG, or WebP image no larger than 5 MiB.');
    }

    $temporaryPath = (string) ($upload['tmp_name'] ?? '');
    $fileSize = is_uploaded_file($temporaryPath) ? filesize($temporaryPath) : false;
    if ($fileSize === false || $fileSize < 1 || $fileSize > EVENT_POSTER_MAX_BYTES) {
        throw new InvalidArgumentException('The poster must be an uploaded image no larger than 5 MiB.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $extensionByMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $imageInfo = getimagesize($temporaryPath);
    if (
        !is_string($mimeType)
        || !isset($extensionByMime[$mimeType])
        || $imageInfo === false
        || ($imageInfo['mime'] ?? '') !== $mimeType
    ) {
        throw new InvalidArgumentException('The poster must be a valid JPG, PNG, or WebP image.');
    }

    $directory = PROJECT_ROOT . '/uploads/events';
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the private event poster directory.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('The private event poster directory is not writable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensionByMime[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
        throw new RuntimeException('Could not store the uploaded event poster.');
    }

    return $filename;
}

function delete_event_poster(?string $filename): void
{
    if ($filename === null || preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $filename) !== 1) {
        return;
    }

    $path = PROJECT_ROOT . '/uploads/events/' . $filename;
    if (is_file($path) && !unlink($path)) {
        log_application_error(new RuntimeException('Could not remove replaced event poster: ' . $filename));
    }
}