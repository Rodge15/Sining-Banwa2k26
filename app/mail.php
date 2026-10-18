<?php
declare(strict_types=1);

function send_account_email(string $to, string $subject, string $body, string $fromEmail, string $fromName): bool
{
    $apiKey = trim((string) env_value('RESEND_API_KEY', ''));
    if ($apiKey !== '') {
        return send_resend_email($apiKey, $to, $subject, $body, $fromEmail, $fromName);
    }

    $headers = 'From: ' . sprintf('"%s" <%s>', addcslashes($fromName, "\\\""), $fromEmail)
        . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";

    return mail($to, $subject, $body, $headers);
}

function send_resend_email(
    string $apiKey,
    string $to,
    string $subject,
    string $body,
    string $fromEmail,
    string $fromName
): bool {
    if (!function_exists('curl_init')) {
        log_application_error(new RuntimeException('The curl extension is required to send email through Resend.'));
        return false;
    }

    $payload = json_encode([
        'from' => sprintf('%s <%s>', $fromName, $fromEmail),
        'to' => [$to],
        'subject' => $subject,
        'text' => $body,
    ], JSON_THROW_ON_ERROR);

    $curl = curl_init('https://api.resend.com/emails');
    if ($curl === false) {
        return false;
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($response === false || $status < 200 || $status >= 300) {
        log_application_error(new RuntimeException('Password reset email could not be sent through Resend. HTTP ' . $status . '.'));
        return false;
    }

    return true;
}
