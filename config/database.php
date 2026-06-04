<?php

declare(strict_types=1);

if (!function_exists('nexus_env')) {
    function nexus_env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}

$databaseUrl = nexus_env('DATABASE_URL') ?: nexus_env('MYSQL_URL');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts === false || empty($parts['scheme'])) {
        throw new RuntimeException('DATABASE_URL invalida.');
    }

    parse_str($parts['query'] ?? '', $query);

    return [
        'driver' => str_starts_with($parts['scheme'], 'postgres') ? 'pgsql' : 'mysql',
        'host' => $parts['host'] ?? '',
        'port' => isset($parts['port']) ? (string) $parts['port'] : (str_starts_with($parts['scheme'], 'postgres') ? '5432' : '3306'),
        'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : '',
        'username' => isset($parts['user']) ? urldecode($parts['user']) : '',
        'password' => isset($parts['pass']) ? urldecode($parts['pass']) : '',
        'charset' => nexus_env('DB_CHARSET', 'utf8mb4'),
        'ssl_mode' => (string) ($query['ssl-mode'] ?? $query['sslmode'] ?? nexus_env('DB_SSL_MODE', '')),
        'ssl_ca' => nexus_env('DB_SSL_CA'),
        'connect_retries' => (int) nexus_env('DB_CONNECT_RETRIES', '5'),
        'connect_retry_delay_ms' => (int) nexus_env('DB_CONNECT_RETRY_DELAY_MS', '750'),
    ];
}

return [
    'driver' => nexus_env('DB_CONNECTION', 'mysql'),
    'host' => nexus_env('DB_HOST') ?: nexus_env('MYSQLHOST'),
    'port' => nexus_env('DB_PORT') ?: nexus_env('MYSQLPORT', '3306'),
    'database' => nexus_env('DB_NAME') ?: nexus_env('DB_DATABASE') ?: nexus_env('MYSQLDATABASE'),
    'username' => nexus_env('DB_USER') ?: nexus_env('DB_USERNAME') ?: nexus_env('MYSQLUSER'),
    'password' => nexus_env('DB_PASS') ?: nexus_env('DB_PASSWORD') ?: nexus_env('MYSQLPASSWORD'),
    'charset' => nexus_env('DB_CHARSET', 'utf8mb4'),
    'ssl_mode' => nexus_env('DB_SSL_MODE'),
    'ssl_ca' => nexus_env('DB_SSL_CA'),
    'connect_retries' => (int) nexus_env('DB_CONNECT_RETRIES', '5'),
    'connect_retry_delay_ms' => (int) nexus_env('DB_CONNECT_RETRY_DELAY_MS', '750'),
];
