<?php

declare(strict_types=1);

final class DemandController extends BaseController
{
    private Demand $demands;

    public function __construct()
    {
        $this->demands = new Demand();
    }

    public function index(): void
    {
        $this->authorize($this->can('work_demands') ? 'work_demands' : 'view_demands');
        $demands = $this->can('work_demands') && !$this->can('manage_demands')
            ? $this->demands->assignedTo((int) $_SESSION['user']['id'])
            : $this->demands->all();

        $this->view('demands/index', [
            'title' => 'Demandas',
            'demands' => $demands,
            'canCreateDemand' => $this->can('create_demands'),
            'canManageDemand' => $this->can('manage_demands'),
            'canWorkDemand' => $this->can('work_demands'),
            'csrfField' => $this->csrfField(),
        ]);
    }

    public function create(): void
    {
        $this->authorize('create_demands');
        $this->form('Nova demanda');
    }

    public function store(): void
    {
        $this->authorize('create_demands');
        $this->verifyCsrf();
        $this->demands->create($this->validated());
        $_SESSION['flash'] = 'Demanda criada com SLA calculado automaticamente.';
        $this->redirect('demands');
    }

    public function edit(): void
    {
        $this->authorize('manage_demands');
        $demand = $this->demands->find((int) ($_GET['id'] ?? 0));

        if (!$demand) {
            $this->redirect('demands');
        }

        $this->form('Editar demanda', $demand);
    }

    public function update(): void
    {
        $this->authorize('manage_demands');
        $this->verifyCsrf();
        $this->demands->update((int) ($_POST['id'] ?? 0), $this->validated());
        $_SESSION['flash'] = 'Demanda atualizada.';
        $this->redirect('demands');
    }

    public function delete(): void
    {
        $this->authorize('manage_demands');
        $this->verifyCsrf();
        $this->demands->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['flash'] = 'Demanda removida.';
        $this->redirect('demands');
    }

    public function reopen(): void
    {
        $this->authorize('manage_demands');
        $this->verifyCsrf();
        $notes = $this->clean($_POST['notes'] ?? 'Reaberta pelo gestor: demanda nao foi concluida.');

        $this->demands->reopen((int) ($_POST['id'] ?? 0), $notes);
        $_SESSION['flash'] = 'Demanda reaberta como nao concluida.';
        $this->redirect('demands');
    }

    public function status(): void
    {
        $this->authorize('work_demands');
        $this->verifyCsrf();
        $allowed = ['em-andamento', 'finalizado'];
        $status = $_POST['status'] ?? '';

        if (!in_array($status, $allowed, true)) {
            $this->redirect('demands');
        }

        $this->demands->updateAssignedStatus(
            (int) ($_POST['id'] ?? 0),
            (int) ($_SESSION['user']['id'] ?? 0),
            $status,
            $this->clean($_POST['notes'] ?? '')
        );

        $_SESSION['flash'] = 'Demanda atualizada.';
        $this->redirect('demands');
    }

    public function details(): void
    {
        $this->auth();
        $id = (int) ($_GET['id'] ?? 0);
        $demand = $this->demands->find($id);

        if (!$demand || !$this->canViewDemand($demand)) {
            $this->json(['error' => 'Demanda nao encontrada.']);
        }

        $this->json([
            'title' => $demand['title'],
            'description' => nl2br(htmlspecialchars($demand['description'])),
            'notes' => nl2br(htmlspecialchars($demand['notes'] ?? 'Sem observacoes.')),
            'client_sector' => $demand['client_sector'],
            'responsible' => $demand['responsible_name'] ?? 'Nao atribuido',
        ]);
    }

    public function latest(): void
    {
        $this->authorize($this->can('work_demands') ? 'work_demands' : 'view_demands');

        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $latestId = $this->can('work_demands') && !$this->can('manage_demands')
            ? $this->demands->latestAssignedId($userId)
            : $this->demands->getLatestId();

        $this->json(['id' => $latestId]);
    }

    private function form(string $title, ?array $demand = null): void
    {
        $this->view('demands/form', [
            'title' => $title,
            'demand' => $demand,
            'priorities' => $this->demands->priorities(),
            'statuses' => $this->demands->statuses(),
            'users' => (new User())->demandReceivers(),
            'csrfField' => $this->csrfField(),
        ]);
    }

    private function validated(): array
    {
        $data = [
            'title' => $this->clean($_POST['title'] ?? ''),
            'description' => $this->clean($_POST['description'] ?? ''),
            'client_sector' => $this->clean($_POST['client_sector'] ?? ''),
            'responsible_id' => (int) ($_POST['responsible_id'] ?? 0),
            'priority_id' => (int) ($_POST['priority_id'] ?? 0),
            'status_id' => (int) ($_POST['status_id'] ?? 0),
            'notes' => $this->clean($_POST['notes'] ?? ''),
        ];

        if ($data['title'] === '' || $data['description'] === '' || $data['client_sector'] === '') {
            $_SESSION['flash'] = 'Preencha titulo, descricao e cliente/setor.';
            $this->redirect('demands');
        }

        if (!$this->demands->priorityExists($data['priority_id']) || !$this->demands->statusExists($data['status_id'])) {
            $_SESSION['flash'] = 'Prioridade ou status invalido.';
            $this->redirect('demands');
        }

        return $data;
    }

    private function canViewDemand(array $demand): bool
    {
        if ($this->can('manage_demands') || ($this->can('view_demands') && !$this->can('work_demands'))) {
            return true;
        }

        return $this->can('work_demands')
            && (int) ($demand['responsible_id'] ?? 0) === (int) ($_SESSION['user']['id'] ?? 0);
    }
}
