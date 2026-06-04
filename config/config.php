<?php

declare(strict_types=1);

define('APP_NAME', 'Nexus SLA');
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', APP_ROOT . '/public');

if (!function_exists('nexus_load_env')) {
    function nexus_load_env(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim(trim($value), "\"'");

            if ($name === '' || getenv($name) !== false) {
                continue;
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

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

nexus_load_env(APP_ROOT . '/.env');

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
);
$protocol = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? ('localhost:' . nexus_env('PORT', '8000'));
define('APP_URL', nexus_env('APP_URL', $protocol . '://' . $host));
define('APP_ENV', nexus_env('APP_ENV', 'production'));
define('APP_DEBUG', filter_var(nexus_env('APP_DEBUG', '0'), FILTER_VALIDATE_BOOL));
define('SESSION_TIMEOUT_SECONDS', (int) nexus_env('SESSION_TIMEOUT_SECONDS', '18000'));
define('TRUST_PROXY_HEADERS', filter_var(nexus_env('TRUST_PROXY_HEADERS', '1'), FILTER_VALIDATE_BOOL));
define('APP_SETUP_ENABLED', filter_var(nexus_env('APP_SETUP_ENABLED', '0'), FILTER_VALIDATE_BOOL));
define('CSRF_CHECK_ORIGIN', filter_var(nexus_env('CSRF_CHECK_ORIGIN', '1'), FILTER_VALIDATE_BOOL));
define('IS_HTTPS', $isHttps);

if (!is_dir(APP_ROOT . '/storage/logs')) {
    mkdir(APP_ROOT . '/storage/logs', 0755, true);
}

date_default_timezone_set('America/Sao_Paulo');

$GLOBALS['db_config'] = require APP_ROOT . '/config/database.php';
