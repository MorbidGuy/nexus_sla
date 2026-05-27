<?php

declare(strict_types=1);

final class KanbanController extends BaseController
{
    public function index(): void
    {
        $this->authorize($this->can('work_demands') ? 'work_demands' : 'view_demands');
        $demand = new Demand();
        $demands = $this->can('work_demands') && !$this->can('manage_demands')
            ? $demand->assignedTo((int) ($_SESSION['user']['id'] ?? 0))
            : $demand->all();

        $this->view('demands/kanban', [
            'title' => 'Kanban',
            'demands' => $demands,
            'statuses' => $demand->statuses(true),
            'canMoveCards' => $this->can('move_kanban'),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    public function updateStatus(): void
    {
        $this->authorize('move_kanban');
        $this->verifyCsrf();

        (new Demand())->updateStatus((int) ($_POST['id'] ?? 0), (int) ($_POST['status_id'] ?? 0));
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
