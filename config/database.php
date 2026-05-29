<?php

declare(strict_types=1);

$envPath = dirname(__DIR__) . '/.env';
$env = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) continue;
        
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");
        $env[trim($name)] = trim($value);
        $_ENV[$name] = $value;
        putenv($name . '=' . $value);
    }
}

// Suporte automático para variáveis do Railway ou variáveis padrão do .env
return [
    'host'     => getenv('MYSQLHOST') ?: ($env['DB_HOST'] ?? '127.0.0.1'),
    'port'     => getenv('MYSQLPORT') ?: ($env['DB_PORT'] ?? '3306'),
    'database' => getenv('MYSQLDATABASE') ?: ($env['DB_NAME'] ?? 'nexus_sla'),
    'username' => getenv('MYSQLUSER') ?: ($env['DB_USER'] ?? 'root'),
    'password' => getenv('MYSQLPASSWORD') ?: ($env['DB_PASS'] ?? ''),
    'charset'  => 'utf8mb4',
];
