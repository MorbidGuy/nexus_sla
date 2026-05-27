<?php

declare(strict_types=1);

final class UserController extends BaseController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        $this->authorize('manage_users');

        $this->view('users/index', [
            'title' => 'Usuarios',
            'users' => $this->users->all(),
        ]);
    }

    public function create(): void
    {
        $this->authorize('manage_users');
        $this->form('Novo usuario');
    }

    public function store(): void
    {
        $this->authorize('manage_users');
        $this->verifyCsrf();
        $data = $this->validated();

        if ($data['name'] === '' || $data['email'] === '' || $data['password'] === '') {
            $_SESSION['flash'] = 'Preencha nome, e-mail e senha.';
            $this->redirect('users/create');
        }
        if (!$this->isStrongPassword($data['password'])) {
            $_SESSION['flash'] = 'Senha fraca. Use 12+ caracteres com maiuscula, minuscula, numero e simbolo.';
            $this->redirect('users/create');
        }

        $this->users->create($data);
        Security::logEvent('user_create', 'User created by admin', [
            'actor_id' => $_SESSION['user']['id'] ?? null,
            'email' => $data['email'],
        ]);

        $_SESSION['flash'] = 'Usuario criado com sucesso.';
        $this->redirect('users');
    }

    public function edit(): void
    {
        $this->authorize('manage_users');
        $managedUser = $this->users->find((int) ($_GET['id'] ?? 0));

        if (!$managedUser) {
            $this->redirect('users');
        }

        $this->form('Editar usuario', $managedUser);
    }

    public function update(): void
    {
        $this->authorize('manage_users');
        $this->verifyCsrf();
        $data = $this->validated(false);

        if ($data['name'] === '' || $data['email'] === '') {
            $_SESSION['flash'] = 'Preencha nome e e-mail.';
            $this->redirect('users');
        }
        if ($data['password'] !== '' && !$this->isStrongPassword($data['password'])) {
            $_SESSION['flash'] = 'Senha fraca. Use 12+ caracteres com maiuscula, minuscula, numero e simbolo.';
            $this->redirect('users');
        }

        $targetId = (int) ($_POST['id'] ?? 0);
        $this->users->update($targetId, $data);
        Security::logEvent('user_update', 'User updated by admin', [
            'actor_id' => $_SESSION['user']['id'] ?? null,
            'target_id' => $targetId,
        ]);

        $_SESSION['flash'] = 'Usuario atualizado.';
        $this->redirect('users');
    }

    public function delete(): void
    {
        $this->authorize('manage_users');
        $this->verifyCsrf();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
            $_SESSION['flash'] = 'Voce nao pode excluir o proprio usuario logado.';
            $this->redirect('users');
        }

        $this->users->delete($id);
        Security::logEvent('user_delete', 'User deleted by admin', [
            'actor_id' => $_SESSION['user']['id'] ?? null,
            'target_id' => $id,
        ]);

        $_SESSION['flash'] = 'Usuario removido.';
        $this->redirect('users');
    }

    private function form(string $title, ?array $managedUser = null): void
    {
        $this->view('users/form', [
            'title' => $title,
            'managedUser' => $managedUser,
            'roles' => $this->roles(),
            'csrfField' => $this->csrfField(),
        ]);
    }

    private function validated(bool $includePassword = true): array
    {
        return [
            'name' => $this->clean($_POST['name'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
            'password' => $includePassword ? ($_POST['password'] ?? '') : trim($_POST['password'] ?? ''),
            'role' => array_key_exists($_POST['role'] ?? '', $this->roles()) ? $_POST['role'] : 'demand_operator',
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
    }

    private function roles(): array
    {
        return [
            'gestor' => 'Gestor geral',
            'usuario' => 'Usuario operacional',
        ];
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 12
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^a-zA-Z\d]/', $password) === 1;
    }
}
