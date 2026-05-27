<?php

declare(strict_types=1);

final class Security
{
    private const RATE_LIMIT_FILE = APP_ROOT . '/storage/security_rate_limits.json';

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (TRUST_PROXY_HEADERS) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if ($forwardedFor !== '') {
                $parts = explode(',', $forwardedFor);
                $candidate = trim($parts[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    $ip = $candidate;
                }
            }
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public static function logEvent(string $type, string $message, array $context = []): void
    {
        $line = sprintf(
            "%s\t%s\t%s\t%s\n",
            date('c'),
            $type,
            $message,
            json_encode($context, JSON_UNESCAPED_SLASHES)
        );

        file_put_contents(APP_ROOT . '/storage/logs/security.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function throttle(string $key, int $limit, int $windowSeconds): array
    {
        $data = self::readRateLimitData();
        $now = time();

        $bucket = $data[$key] ?? ['start' => $now, 'count' => 0];
        if (($now - (int) $bucket['start']) >= $windowSeconds) {
            $bucket = ['start' => $now, 'count' => 0];
        }

        $bucket['count'] = (int) $bucket['count'] + 1;
        $data[$key] = $bucket;
        self::writeRateLimitData($data);

        $remaining = max(0, $limit - $bucket['count']);
        $allowed = $bucket['count'] <= $limit;

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'retry_after' => max(0, $windowSeconds - ($now - (int) $bucket['start'])),
        ];
    }

    private static function readRateLimitData(): array
    {
        if (!is_file(self::RATE_LIMIT_FILE)) {
            return [];
        }

        $raw = file_get_contents(self::RATE_LIMIT_FILE);
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function writeRateLimitData(array $data): void
    {
        file_put_contents(self::RATE_LIMIT_FILE, json_encode($data, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
}
