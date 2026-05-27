<?php

declare(strict_types=1);

final class Demand
{
    public function all(): array
    {
        $sql = $this->baseQuery() . ' ORDER BY d.created_at DESC';
        return Database::connection()->query($sql)->fetchAll();
    }

    public function getLatestId(): int
    {
        $sql = 'SELECT MAX(id) as last_id FROM demands';
        $row = Database::connection()->query($sql)->fetch();
        return (int) ($row['last_id'] ?? 0);
    }

    public function latestAssignedId(int $userId): int
    {
        $stmt = Database::connection()->prepare('SELECT MAX(id) as last_id FROM demands WHERE responsible_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return (int) ($row['last_id'] ?? 0);
    }

    public function recent(int $limit = 6): array
    {
        $sql = $this->baseQuery() . ' ORDER BY d.created_at DESC LIMIT :limit';
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function assignedTo(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            $this->baseQuery() . ' WHERE d.responsible_id = :user_id ORDER BY d.due_at ASC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function statsForUser(int $userId): array
    {
        $sql = "SELECT
            COUNT(*) total,
            SUM(CASE WHEN d.due_at < NOW() AND s.slug != 'finalizado' THEN 1 ELSE 0 END) overdue,
            SUM(CASE WHEN s.slug = 'em-andamento' THEN 1 ELSE 0 END) in_progress,
            SUM(CASE WHEN s.slug = 'finalizado' THEN 1 ELSE 0 END) finished,
            SUM(CASE WHEN p.slug IN ('alto', 'super-alto') THEN 1 ELSE 0 END) critical
        FROM demands d
        INNER JOIN priorities p ON p.id = d.priority_id
        INNER JOIN statuses s ON s.id = d.status_id
        WHERE d.responsible_id = :user_id";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetch() ?: [
            'total' => 0,
            'overdue' => 0,
            'in_progress' => 0,
            'finished' => 0,
            'critical' => 0,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare($this->baseQuery() . ' WHERE d.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $demand = $stmt->fetch();

        return $demand ?: null;
    }

    public function stats(): array
    {
        $sql = "SELECT
            COUNT(*) total,
            SUM(CASE WHEN d.due_at < NOW() AND s.slug != 'finalizado' THEN 1 ELSE 0 END) overdue,
            SUM(CASE WHEN s.slug = 'em-andamento' THEN 1 ELSE 0 END) in_progress,
            SUM(CASE WHEN s.slug = 'finalizado' THEN 1 ELSE 0 END) finished,
            SUM(CASE WHEN p.slug IN ('alto', 'super-alto') THEN 1 ELSE 0 END) critical
        FROM demands d
        INNER JOIN priorities p ON p.id = d.priority_id
        INNER JOIN statuses s ON s.id = d.status_id";

        return Database::connection()->query($sql)->fetch() ?: [
            'total' => 0,
            'overdue' => 0,
            'in_progress' => 0,
            'finished' => 0,
            'critical' => 0,
        ];
    }

    public function priorities(): array
    {
        return Database::connection()->query('SELECT * FROM priorities ORDER BY sla_hours DESC')->fetchAll();
    }

    public function statuses(bool $kanbanOnly = false): array
    {
        $sql = 'SELECT * FROM statuses';
        if ($kanbanOnly) {
            $sql .= " WHERE slug IN ('aberto', 'em-andamento', 'finalizado')";
        }
        $sql .= ' ORDER BY position';

        return Database::connection()->query($sql)->fetchAll();
    }

    public function create(array $data): void
    {
        $priority = $this->priority((int) $data['priority_id']);
        $openedAt = new DateTimeImmutable();
        $dueAt = $openedAt->modify('+' . (int) $priority['sla_hours'] . ' hours');
        $responsibleId = (int) ($data['responsible_id'] ?? 0);

        if ($responsibleId === 0) {
            $responsibleId = $this->randomResponsibleId();
        }

        $sql = 'INSERT INTO demands
            (title, description, client_sector, responsible_id, priority_id, status_id, opened_at, due_at, notes)
            VALUES
            (:title, :description, :client_sector, :responsible_id, :priority_id, :status_id, :opened_at, :due_at, :notes)';

        Database::connection()->prepare($sql)->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'client_sector' => $data['client_sector'],
            'responsible_id' => $responsibleId ?: null,
            'priority_id' => $data['priority_id'],
            'status_id' => $data['status_id'],
            'opened_at' => $openedAt->format('Y-m-d H:i:s'),
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
            'notes' => $data['notes'] ?: null,
        ]);
    }

    public function update(int $id, array $data): void
    {
        $current = $this->find($id);
        $priorityChanged = (int) $current['priority_id'] !== (int) $data['priority_id'];
        $dueAt = $current['due_at'];

        if ($priorityChanged) {
            $priority = $this->priority((int) $data['priority_id']);
            $dueAt = (new DateTimeImmutable($current['opened_at']))
                ->modify('+' . (int) $priority['sla_hours'] . ' hours')
                ->format('Y-m-d H:i:s');
        }

        $status = $this->status((int) $data['status_id']);
        $finishedAt = $status['slug'] === 'finalizado' ? date('Y-m-d H:i:s') : null;

        $sql = 'UPDATE demands SET
            title = :title,
            description = :description,
            client_sector = :client_sector,
            responsible_id = :responsible_id,
            priority_id = :priority_id,
            status_id = :status_id,
            due_at = :due_at,
            finished_at = :finished_at,
            notes = :notes
            WHERE id = :id';

        Database::connection()->prepare($sql)->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'],
            'client_sector' => $data['client_sector'],
            'responsible_id' => $data['responsible_id'] ?: null,
            'priority_id' => $data['priority_id'],
            'status_id' => $data['status_id'],
            'due_at' => $dueAt,
            'finished_at' => $finishedAt,
            'notes' => $data['notes'] ?: null,
        ]);
    }

    public function updateStatus(int $id, int $statusId): void
    {
        if (!$this->statusExists($statusId)) {
            throw new InvalidArgumentException('Status invalido.');
        }

        $status = $this->status($statusId);
        $finishedAt = $status['slug'] === 'finalizado' ? date('Y-m-d H:i:s') : null;

        $stmt = Database::connection()->prepare(
            'UPDATE demands SET status_id = :status_id, finished_at = :finished_at WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status_id' => $statusId, 'finished_at' => $finishedAt]);
    }

    public function reopen(int $id, string $notes): void
    {
        $status = $this->statusBySlug('em-andamento');
        $stmt = Database::connection()->prepare(
            'UPDATE demands
             SET status_id = :status_id, finished_at = NULL, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status_id' => $status['id'],
            'notes' => $notes,
        ]);
    }

    public function updateAssignedStatus(int $id, int $userId, string $statusSlug, string $notes = ''): void
    {
        $status = $this->statusBySlug($statusSlug);
        $finishedAt = $status['slug'] === 'finalizado' ? date('Y-m-d H:i:s') : null;

        $stmt = Database::connection()->prepare(
            'UPDATE demands
             SET status_id = :status_id, finished_at = :finished_at, notes = :notes
             WHERE id = :id AND responsible_id = :user_id'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'status_id' => $status['id'],
            'finished_at' => $finishedAt,
            'notes' => $notes ?: null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM demands WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function priorityExists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = Database::connection()->prepare('SELECT 1 FROM priorities WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function statusExists(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = Database::connection()->prepare('SELECT 1 FROM statuses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    private function priority(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM priorities WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    private function status(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM statuses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    private function statusBySlug(string $slug): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM statuses WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch();
    }

    private function randomResponsibleId(): int
    {
        $stmt = Database::connection()->query(
            "SELECT id FROM users WHERE active = 1 AND role IN ('usuario', 'demand_operator') ORDER BY RAND() LIMIT 1"
        );
        $user = $stmt->fetch();

        return (int) ($user['id'] ?? 0);
    }

    private function baseQuery(): string
    {
        return "SELECT d.*, p.name priority_name, p.slug priority_slug, p.color priority_color, p.sla_hours,
            s.name status_name, s.slug status_slug, u.name responsible_name,
            CASE WHEN d.due_at < NOW() AND s.slug != 'finalizado' THEN 1 ELSE 0 END is_overdue
            FROM demands d
            INNER JOIN priorities p ON p.id = d.priority_id
            INNER JOIN statuses s ON s.id = d.status_id
            LEFT JOIN users u ON u.id = d.responsible_id";
    }
}
