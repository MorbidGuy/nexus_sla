<?php

declare(strict_types=1);

define('APP_NAME', 'Nexus SLA');
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_PATH', APP_ROOT . '/public');

$isHttps = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on')
);
$protocol = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
define('APP_URL', $protocol . '://' . $host);
define('APP_ENV', $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL));
define('SESSION_TIMEOUT_SECONDS', (int) ($_ENV['SESSION_TIMEOUT_SECONDS'] ?? getenv('SESSION_TIMEOUT_SECONDS') ?: 18000));
define('TRUST_PROXY_HEADERS', filter_var($_ENV['TRUST_PROXY_HEADERS'] ?? getenv('TRUST_PROXY_HEADERS') ?: '1', FILTER_VALIDATE_BOOL));
define('APP_SETUP_ENABLED', filter_var($_ENV['APP_SETUP_ENABLED'] ?? getenv('APP_SETUP_ENABLED') ?: '0', FILTER_VALIDATE_BOOL));
define('CSRF_CHECK_ORIGIN', filter_var($_ENV['CSRF_CHECK_ORIGIN'] ?? getenv('CSRF_CHECK_ORIGIN') ?: '1', FILTER_VALIDATE_BOOL));
define('IS_HTTPS', $isHttps);

// Evita erro de Read-only file system no Vercel/Produção
if (APP_ENV !== 'production' && !is_dir(APP_ROOT . '/storage/logs')) {
    mkdir(APP_ROOT . '/storage/logs', 0755, true);
}

date_default_timezone_set('America/Sao_Paulo');

$GLOBALS['db_config'] = require APP_ROOT . '/config/database.php';
