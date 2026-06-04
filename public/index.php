<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

set_exception_handler(static function (Throwable $exception): void {
    error_log('[Unhandled Exception] ' . $exception->getMessage());
    http_response_code(500);
    echo APP_DEBUG
        ? 'Erro interno: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Erro interno do servidor.';
    exit;
});

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => IS_HTTPS,
    'use_strict_mode' => true,
    'cookie_lifetime' => 0,
]);

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header('X-Permitted-Cross-Domain-Policies: none');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (IS_HTTPS) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");

spl_autoload_register(function ($class) {
    $paths = [
        APP_ROOT . '/app/models/',
        APP_ROOT . '/app/controllers/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

$storagePath = APP_ROOT . '/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath, 0755, true);
}

$serverStartedFile = $storagePath . '/server_started_at.txt';
if (!is_file($serverStartedFile)) {
    file_put_contents($serverStartedFile, (string) time());
}
$serverStartedAt = (int) trim((string) file_get_contents($serverStartedFile));
$sessionTimeout = SESSION_TIMEOUT_SECONDS;
$now = time();

if (!empty($_SESSION['user'])) {
    $loginAt = (int) ($_SESSION['login_at'] ?? 0);
    $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

    if (($serverStartedAt && $loginAt < $serverStartedAt) || ($lastActivity && ($now - $lastActivity) > $sessionTimeout)) {
        session_unset();
        session_destroy();
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => IS_HTTPS,
            'use_strict_mode' => true,
        ]);
        $_SESSION['flash'] = 'Sua sessao expirou. Faca login novamente.';
    } else {
        $_SESSION['last_activity'] = $now;
    }
}

try {
    $db = Database::connection();
    $route = trim($_GET['route'] ?? 'dashboard', '/');
    $isManagerSignupRoute = in_array($route, ['login', 'login/manager-user'], true);

    if ($isManagerSignupRoute) {
        installApplicationSchema($db);
    }

    $result = $db->query('SELECT COUNT(*) as count FROM users');
    $userCount = (int) ($result->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    if ($userCount === 0 && !$isManagerSignupRoute) {
        require APP_ROOT . '/public/setup.php';
        exit;
    }
} catch (Exception $e) {
    error_log('[Database] ' . $e->getMessage());
    if (APP_SETUP_ENABLED) {
        require APP_ROOT . '/public/setup.php';
        exit;
    }

    http_response_code(503);
    echo APP_DEBUG
        ? 'Erro ao conectar ao banco de dados: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
        : 'Banco de dados indisponivel. Verifique as variaveis de ambiente e tente novamente.';
    exit;
}

$routes = require APP_ROOT . '/routes/web.php';
$route = $route ?? trim($_GET['route'] ?? 'dashboard', '/');
$method = $_SERVER['REQUEST_METHOD'];
$allowedMethods = ['GET', 'POST'];
if (!in_array($method, $allowedMethods, true)) {
    http_response_code(405);
    exit('Metodo nao permitido.');
}

$globalLimit = Security::throttle('global_' . Security::clientIp(), 240, 60);
if (!$globalLimit['allowed']) {
    header('Retry-After: ' . (string) $globalLimit['retry_after']);
    Security::logEvent('http_throttle', 'Global request throttled', ['ip' => Security::clientIp()]);
    http_response_code(429);
    exit('Muitas requisicoes. Aguarde um momento.');
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 2 * 1024 * 1024) {
    http_response_code(413);
    exit('Payload muito grande.');
}

$key = $method . ' ' . $route;

if (!isset($routes[$key])) {
    http_response_code(404);
    echo 'Pagina nao encontrada.';
    exit;
}

[$controllerName, $action] = $routes[$key];
$controller = new $controllerName();
$controller->$action();

function installApplicationSchema(PDO $db): void
{
    $sql = file_get_contents(APP_ROOT . '/database/schema.sql');
    foreach (explode(';', $sql ?: '') as $query) {
        if (trim($query) !== '') {
            $db->exec($query);
        }
    }
}
