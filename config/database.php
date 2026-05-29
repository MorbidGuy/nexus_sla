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

// Mapeia todas as variações possíveis de nomes para evitar o erro de conexão
return [
    'host'     => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? getenv('MYSQLHOST') ?? $env['DB_HOST'] ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? getenv('MYSQLPORT') ?? $env['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?? getenv('MYSQLDATABASE') ?? $env['DB_DATABASE'] ?? $env['DB_NAME'] ?? 'railway',
    'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? getenv('MYSQLUSER') ?? $env['DB_USERNAME'] ?? $env['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? getenv('MYSQLPASSWORD') ?? $env['DB_PASSWORD'] ?? $env['DB_PASS'] ?? '',
    'charset'  => 'utf8mb4',
];