<?php
declare(strict_types=1);

function request_is_https(): bool
{
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (env_value('VERCEL') === '1' || session_driver() === 'cookie') {
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    return false;
}

function session_driver(): string
{
    $driver = strtolower(trim((string) env_value('SESSION_DRIVER', 'file')));
    if (!in_array($driver, ['file', 'cookie'], true)) {
        throw new RuntimeException('Session driver configuration is invalid.');
    }

    return $driver;
}

final class CookieSessionHandler implements SessionHandlerInterface
{
    private const COOKIE = 'sining_banwa_state';
    private const MAX_AGE = 43200;

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $cookie = $_COOKIE[self::COOKIE] ?? '';
        if (!is_string($cookie) || $cookie === '') {
            return '';
        }

        $data = self::unseal($cookie);
        return $data ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $sealed = self::seal($data);
        if ($sealed === null || strlen($sealed) > 3800) {
            error_log('Could not store the signed session cookie.');
            return false;
        }

        return setcookie(self::COOKIE, $sealed, self::cookieOptions(time() + self::MAX_AGE));
    }

    public function destroy(string $id): bool
    {
        unset($_COOKIE[self::COOKIE]);
        return setcookie(self::COOKIE, '', self::cookieOptions(time() - 42000));
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private static function cookieOptions(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => '/',
            'secure' => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    private static function seal(string $data): ?string
    {
        try {
            $payload = json_encode([
                'e' => time() + self::MAX_AGE,
                'd' => $data,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $signature = hash_hmac('sha256', $payload, self::secret(), true);
        return self::base64urlEncode($payload) . '.' . self::base64urlEncode($signature);
    }

    private static function unseal(string $cookie): ?string
    {
        $parts = explode('.', $cookie, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $payload = self::base64urlDecode($parts[0]);
        $signature = self::base64urlDecode($parts[1]);
        if ($payload === null || $signature === null) {
            return null;
        }

        $expected = hash_hmac('sha256', $payload, self::secret(), true);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        try {
            $decoded = json_decode($payload, true, 3, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (!is_array($decoded) || !is_int($decoded['e'] ?? null) || !is_string($decoded['d'] ?? null)) {
            return null;
        }
        if ($decoded['e'] < time()) {
            return null;
        }

        return $decoded['d'];
    }

    private static function secret(): string
    {
        $secret = env_value('APP_SECRET');
        if ($secret === null || strlen($secret) < 32) {
            throw new RuntimeException('APP_SECRET must be at least 32 characters when cookie sessions are enabled.');
        }

        return $secret;
    }

    private static function base64urlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64urlDecode(string $value): ?string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/D', $value) !== 1) {
            return null;
        }
        $remainder = strlen($value) % 4;
        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
