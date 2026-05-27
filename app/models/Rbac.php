<?php

declare(strict_types=1);

final class Rbac
{
    private const CACHE_TTL_SECONDS = 300;

    private static array $cache = [];

    public function permissionsForUser(int $userId, string $legacyRole): array
    {
        $cacheKey = $userId . ':' . $legacyRole;
        $cached = self::$cache[$cacheKey] ?? null;

        if (is_array($cached) && ($cached['expires_at'] ?? 0) > time()) {
            return $cached['permissions'];
        }

        $permissions = $this->loadPermissionsFromDatabase($userId);

        if ($permissions === []) {
            $permissions = $this->legacyPermissions($legacyRole);
        }

        self::$cache[$cacheKey] = [
            'expires_at' => time() + self::CACHE_TTL_SECONDS,
            'permissions' => $permissions,
        ];

        return $permissions;
    }

    public function userHasPermission(int $userId, string $legacyRole, string $permission): bool
    {
        return in_array($permission, $this->permissionsForUser($userId, $legacyRole), true);
    }

    public function logAccess(string $permission, bool $allowed, array $context = []): void
    {
        Security::logEvent($allowed ? 'access_allowed' : 'access_denied', 'RBAC permission check', [
            'permission' => $permission,
            'allowed' => $allowed,
            'user_id' => $_SESSION['user']['id'] ?? null,
            'role' => $_SESSION['user']['role'] ?? null,
            'route' => $_GET['route'] ?? 'dashboard',
            'ip' => Security::clientIp(),
        ] + $context);
    }

    private function loadPermissionsFromDatabase(int $userId): array
    {
        try {
            $pdo = Database::connection();

            if (!$this->tablesExist($pdo)) {
                return [];
            }

            $sql = "
                WITH RECURSIVE role_tree AS (
                    SELECT r.id, r.parent_id
                    FROM roles r
                    INNER JOIN user_roles ur ON ur.role_id = r.id
                    WHERE ur.user_id = :user_id
                    UNION ALL
                    SELECT parent.id, parent.parent_id
                    FROM roles parent
                    INNER JOIN role_tree child ON child.parent_id = parent.id
                )
                SELECT DISTINCT p.slug
                FROM role_tree rt
                INNER JOIN role_permissions rp ON rp.role_id = rt.id
                INNER JOIN permissions p ON p.id = rp.permission_id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);

            return array_values(array_filter(array_map(
                static fn (array $row): string => (string) $row['slug'],
                $stmt->fetchAll()
            )));
        } catch (Throwable $e) {
            error_log('[RBAC] ' . $e->getMessage());
            return [];
        }
    }

    private function tablesExist(PDO $pdo): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME IN ('roles', 'permissions', 'role_permissions', 'user_roles')
        ");
        $stmt->execute();

        return (int) ($stmt->fetch()['total'] ?? 0) === 4;
    }

    private function legacyPermissions(string $role): array
    {
        $adminPerms = [
            'dashboard.view',
            'database.view',
            'users.manage',
            'demands.view',
            'demands.create',
            'demands.manage',
            'demands.work',
            'kanban.move',
            'audit.view',
        ];
        $operatorPerms = ['dashboard.view', 'demands.view', 'demands.create', 'demands.work', 'kanban.move'];

        $permissions = [
            'administrator' => $adminPerms,
            'admin' => $adminPerms,
            'gestor' => $adminPerms,
            'manager' => $adminPerms,
            'rh' => ['dashboard.view', 'hr.view'],
            'user_manager' => ['dashboard.view', 'users.manage', 'hr.view'],
            'usuario' => $operatorPerms,
            'demand_operator' => $operatorPerms,
            'operador' => $operatorPerms,
            'colaborador' => ['dashboard.view', 'demands.view'],
            'auditor' => ['dashboard.view', 'demands.view', 'audit.view'],
            'visitante' => ['dashboard.view'],
        ];

        return $permissions[$role] ?? [];
    }
}
