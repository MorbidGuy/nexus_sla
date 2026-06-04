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
            $tables = self::mysqlTableMetadata($pdo);
            $rowsCount = 0;

            foreach ($tables as $table) {
                $rowsCount += self::exactTableRows($pdo, (string) $table['table_name']);
            }

            return [
                'tables_count' => count($tables),
                'rows_count' => $rowsCount,
                'data_bytes' => array_sum(array_map(static fn (array $table): int => (int) $table['data_bytes'], $tables)),
                'index_bytes' => array_sum(array_map(static fn (array $table): int => (int) $table['index_bytes'], $tables)),
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
            $tables = self::mysqlTableMetadata($pdo);

            foreach ($tables as &$table) {
                $table['rows_count'] = self::exactTableRows($pdo, (string) $table['table_name']);
                $table['table_rows'] = $table['rows_count'];
                $table['data_length'] = $table['data_bytes'];
                $table['index_length'] = $table['index_bytes'];
            }
            unset($table);

            return $tables;
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
            $total = $total ?: 0;
            $free = $free ?: 0;
            $used = max(0, $total - $free);
            $usedPercent = $total > 0 ? ($used / $total) * 100 : 0.0;

            return [
                'total_bytes' => $total,
                'free_bytes' => $free,
                'used_bytes' => $used,
                'used_percent' => $usedPercent,
                'is_warning' => $usedPercent >= 85.0,
            ];
        } catch (Throwable $e) {
            error_log('[diskStats] ' . $e->getMessage());

            return [
                'total_bytes' => 0,
                'free_bytes' => 0,
                'used_bytes' => 0,
                'used_percent' => 0.0,
                'is_warning' => false,
            ];
        }
    }

    private static function mysqlTableMetadata(PDO $pdo): array
    {
        $stmt = $pdo->query("
            SELECT
                TABLE_NAME AS table_name,
                COALESCE(DATA_LENGTH, 0) AS data_bytes,
                COALESCE(INDEX_LENGTH, 0) AS index_bytes
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY DATA_LENGTH DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function exactTableRows(PDO $pdo, string $tableName): int
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            return 0;
        }

        return (int) $pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
    }
}
