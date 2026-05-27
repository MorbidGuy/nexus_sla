<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$response = [
    'status' => 'OK',
    'app' => APP_NAME,
    'timestamp' => time(),
    'uptime' => getServerUptime(),
];

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

function getServerUptime(): int
{
    $startFile = APP_ROOT . '/storage/server_started_at.txt';
    if (file_exists($startFile)) {
        $startTime = (int) file_get_contents($startFile);
        return time() - $startTime;
    }

    return 0;
}
