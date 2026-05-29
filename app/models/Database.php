<?php

declare(strict_types=1);

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        // Verifica se a conexão já existe e se ainda está viva
        if (self::$connection !== null) {
            try {
                // Um comando simples para testar se o servidor responde
                self::$connection->query('SELECT 1');
            } catch (PDOException $e) {
                self::$connection = null; // Conexão morreu (2006), força uma nova
            }
        }

        if (self::$connection === null) {
            $config = require APP_ROOT . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $exception) {
                error_log('[PDO] ' . $exception->getMessage());

                throw new RuntimeException(
                    APP_DEBUG
                        ? 'Falha ao conectar no banco: ' . $exception->getMessage()
                        : 'Falha ao conectar no banco de dados.'
                );
            }
        }

        return self::$connection;
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
