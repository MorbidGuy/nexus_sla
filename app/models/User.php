<?php

declare(strict_types=1);

final class User
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email AND active = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function allActive(): array
    {
        return Database::connection()
            ->query('SELECT id, name, email FROM users WHERE active = 1 ORDER BY name')
            ->fetchAll();
    }

    public function dashboardTargets(): array
    {
        return Database::connection()
            ->query("SELECT id, name, email, role FROM users WHERE active = 1 AND role IN ('usuario', 'demand_operator', 'gestor', 'manager') ORDER BY role, name")
            ->fetchAll();
    }

    public function demandReceivers(): array
    {
        return Database::connection()
            ->query("SELECT id, name, email FROM users WHERE active = 1 AND role IN ('usuario', 'demand_operator') ORDER BY name")
            ->fetchAll();
    }

    public function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, email, role, active, created_at FROM users ORDER BY name')
            ->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name, email, role, active FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password, role, active) VALUES (:name, :email, :password, :role, :active)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'],
            'active' => $data['active'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'active' => $data['active'],
        ];

        $passwordSql = '';
        if (!empty($data['password'])) {
            $passwordSql = ', password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $stmt = Database::connection()->prepare(
            "UPDATE users SET name = :name, email = :email, role = :role, active = :active {$passwordSql} WHERE id = :id"
        );
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function statsByRole(): array
    {
        return Database::connection()
            ->query('SELECT role, COUNT(*) total FROM users GROUP BY role ORDER BY role')
            ->fetchAll();
    }
}
