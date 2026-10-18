<?php
declare(strict_types=1);

function storage_driver(): string
{
    $driver = strtolower(trim((string) env_value('STORAGE_DRIVER', 'local')));
    if (!in_array($driver, ['local', 'supabase'], true)) {
        throw new RuntimeException('Storage driver configuration is invalid.');
    }

    return $driver;
}

function store_poster_bytes(string $filename, string $bytes, string $mimeType): void
{
    if (storage_driver() !== 'supabase') {
        throw new RuntimeException('Remote poster storage is not enabled.');
    }

    supabase_storage_request('POST', $filename, $bytes, $mimeType);
}

function delete_poster_object(string $filename): void
{
    if (storage_driver() !== 'supabase') {
        return;
    }

    supabase_storage_request('DELETE', $filename, null, null, true);
}

function read_poster_bytes(string $filename): ?string
{
    if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $filename) !== 1) {
        return null;
    }
    if (storage_driver() === 'supabase') {
        return supabase_storage_request('GET', $filename);
    }

    $directory = realpath(PROJECT_ROOT . '/uploads/events');
    $path = realpath(PROJECT_ROOT . '/uploads/events/' . $filename);
    if (
        $directory === false
        || $path === false
        || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR)
        || !is_file($path)
        || !is_readable($path)
    ) {
        return null;
    }

    $bytes = file_get_contents($path);
    return $bytes === false ? null : $bytes;
}

function supabase_storage_request(
    string $method,
    string $filename,
    ?string $body = null,
    ?string $contentType = null,
    bool $missingIsSuccess = false
): ?string {
    if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $filename) !== 1) {
        throw new RuntimeException('The poster filename is invalid.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('The curl extension is required for Supabase storage.');
    }

    $baseUrl = rtrim((string) env_value('SUPABASE_URL', ''), '/');
    $key = (string) env_value('SUPABASE_SERVICE_ROLE_KEY', '');
    $bucket = (string) env_value('SUPABASE_STORAGE_BUCKET', 'event-posters');
    if (
        preg_match('#\Ahttps://[a-z0-9-]+\.supabase\.co\z#i', $baseUrl) !== 1
        || $key === ''
        || preg_match('/\A[a-z0-9][a-z0-9-]{1,62}\z/', $bucket) !== 1
    ) {
        throw new RuntimeException('Supabase storage configuration is incomplete.');
    }

    $url = $baseUrl . '/storage/v1/object/' . rawurlencode($bucket) . '/' . rawurlencode($filename);
    $headers = [
        'Authorization: Bearer ' . $key,
        'apikey: ' . $key,
    ];
    if ($contentType !== null) {
        $headers[] = 'Content-Type: ' . $contentType;
        $headers[] = 'x-upsert: false';
    }

    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Could not start the storage request.');
    }
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $failed = $response === false;
    curl_close($curl);
    if ($failed) {
        throw new RuntimeException('The poster storage request failed.');
    }
    if ($method === 'GET' && $status === 404) {
        return null;
    }
    if ($status >= 400) {
        if ($missingIsSuccess && $status === 404) {
            return null;
        }
        throw new RuntimeException('Poster storage returned HTTP ' . $status . '.');
    }

    return is_string($response) ? $response : null;
}
