<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $config = require APP_ROOT . '/config/database.php';
            self::validateConfig($config);

            $dsn = self::dsn($config);
            $options = self::options($config);
            $attempts = max(1, (int) ($config['connect_retries'] ?? 1));
            $delayMs = max(0, (int) ($config['connect_retry_delay_ms'] ?? 0));
            $lastException = null;

            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    self::$connection = new PDO(
                        $dsn,
                        $config['username'],
                        $config['password'],
                        $options
                    );
                    break;
                } catch (PDOException $exception) {
                    $lastException = $exception;
                    error_log(sprintf('[PDO] attempt %d/%d: %s', $attempt, $attempts, $exception->getMessage()));

                    if ($attempt < $attempts && $delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                }
            }

            if (self::$connection === null) {
                throw new RuntimeException(
                    APP_DEBUG && $lastException
                        ? 'Falha ao conectar no banco: ' . $lastException->getMessage()
                        : 'Falha ao conectar no banco de dados. Verifique as variaveis de ambiente e tente novamente.'
                );
            }
        }

        return self::$connection;
    }

    private static function validateConfig(array $config): void
    {
        foreach (['host', 'database', 'username'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException("Variavel de ambiente do banco ausente: {$key}.");
            }
        }
    }

    private static function dsn(array $config): string
    {
        if (($config['driver'] ?? 'mysql') === 'pgsql') {
            $sslMode = (string) ($config['ssl_mode'] ?? '');

            return sprintf(
                'pgsql:host=%s;port=%s;dbname=%s%s',
                $config['host'],
                $config['port'] ?: '5432',
                $config['database'],
                $sslMode !== '' ? ';sslmode=' . $sslMode : ''
            );
        }

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'] ?: '3306',
            $config['database'],
            $config['charset'] ?: 'utf8mb4'
        );
    }

    private static function options(array $config): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ];

        if (($config['driver'] ?? 'mysql') !== 'mysql') {
            return $options;
        }

        $sslMode = strtolower((string) ($config['ssl_mode'] ?? ''));
        $sslCa = (string) ($config['ssl_ca'] ?? '');

        if ($sslCa !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        }

        if ($sslMode !== '' && $sslMode !== 'disabled' && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = !in_array($sslMode, ['required', 'require', 'preferred'], true);
        }

        return $options;
    }

    public static function storageStats(): array
    {
        try {
            $pdo = self::connection();

            $sql = "
                SELECT
                    COUNT(*) AS tables_count,
                    COALESCE(SUM(TABLE_ROWS), 0) AS rows_count,
                    COALESCE(SUM(DATA_LENGTH), 0) AS data_bytes,
                    COALESCE(SUM(INDEX_LENGTH), 0) AS index_bytes
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
            ";

            $stmt = $pdo->query($sql);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'tables_count' => 0,
                'rows_count' => 0,
                'data_bytes' => 0,
                'index_bytes' => 0,
            ];
        } catch (Throwable $e) {
            error_log('[storageStats] ' . $e->getMessage());

            return [
                'tables_count' => 0,
                'rows_count' => 0,
                'data_bytes' => 0,
                'index_bytes' => 0,
            ];
        }
    }

    public static function tableStats(): array
    {
        try {
            $pdo = self::connection();

            $sql = "
                SELECT
                    TABLE_NAME as table_name,
                    TABLE_ROWS as rows_count,
                    DATA_LENGTH as data_bytes,
                    INDEX_LENGTH as index_bytes
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                ORDER BY DATA_LENGTH DESC
            ";

            $stmt = $pdo->query($sql);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[tableStats] ' . $e->getMessage());

            return [];
        }
    }

    public static function diskStats(): array
    {
        try {
            $total = disk_total_space('/');
            $free = disk_free_space('/');

            return [
                'total_bytes' => $total ?: 0,
                'free_bytes' => $free ?: 0,
                'used_bytes' => ($total - $free),
            ];
        } catch (Throwable $e) {
            error_log('[diskStats] ' . $e->getMessage());

            return [
                'total_bytes' => 0,
                'free_bytes' => 0,
                'used_bytes' => 0,
            ];
        }
    }
}
