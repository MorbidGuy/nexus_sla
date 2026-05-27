<?php

declare(strict_types=1);

abstract class BaseController
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/app/views/layouts/header.php';
        require APP_ROOT . '/app/views/' . $view . '.php';
        require APP_ROOT . '/app/views/layouts/footer.php';
    }

    protected function json(mixed $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $route): void
    {
        header('Location: index.php?route=' . rawurlencode($route));
        exit;
    }

    protected function auth(): void
    {
        if (empty($_SESSION['user'])) {
            $this->redirect('login');
        }
    }

    protected function userRole(): string
    {
        return $_SESSION['user']['role'] ?? '';
    }

    protected function can(string $permission): bool
    {
        $aliases = [
            'dashboard' => 'dashboard.view',
            'view_database' => 'database.view',
            'manage_users' => 'users.manage',
            'view_demands' => 'demands.view',
            'create_demands' => 'demands.create',
            'manage_demands' => 'demands.manage',
            'work_demands' => 'demands.work',
            'move_kanban' => 'kanban.move',
        ];

        $normalizedPermission = $aliases[$permission] ?? $permission;
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            return false;
        }

        return (new Rbac())->userHasPermission($userId, $this->userRole(), $normalizedPermission);
    }

    protected function authorize(string $permission): void
    {
        $this->auth();

        if (!$this->can($permission)) {
            $_SESSION['flash'] = 'Você não tem permissão para acessar esta área.';
            $this->redirect('dashboard');
        }
    }

    protected function roleLabel(?string $role = null): string
    {
        return [
            'administrator' => 'Administrador técnico',
            'admin' => 'Administrador',
            'rh' => 'Acesso desativado',
            'user_manager' => 'Recursos Humanos',
            'usuario' => 'Usuário operacional',
            'demand_operator' => 'Usuário operacional',
            'gestor' => 'Gestor de demandas',
            'manager' => 'Gestor de demandas',
        ][$role ?? $this->userRole()] ?? 'Usuario';
    }

    protected function guest(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }
    }

    protected function clean(string $value): string
    {
        return trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    protected function csrfField(): string
    {
        $token = htmlspecialchars($this->csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    protected function verifyCsrf(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
        $forwardedHostHeader = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? '';
        $serverHost = $_SERVER['SERVER_NAME'] ?? '';
        $trustedHost = (string) parse_url(APP_URL, PHP_URL_HOST);
        $requestHost = strtolower((string) strtok($hostHeader, ':'));
        $forwardedHost = strtolower((string) strtok($forwardedHostHeader, ','));
        $forwardedHost = strtolower((string) strtok(trim($forwardedHost), ':'));

        $allowedHosts = array_values(array_filter(array_unique([
            $requestHost,
            TRUST_PROXY_HEADERS ? $forwardedHost : '',
            strtolower($serverHost),
            strtolower($trustedHost),
        ])));

        $isSameOrigin = true;
        if ($origin !== '') {
            $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
            $isSameOrigin = ($originHost !== '' && in_array($originHost, $allowedHosts, true));
        } elseif ($referer !== '') {
            $refererHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
            $isSameOrigin = ($refererHost !== '' && in_array($refererHost, $allowedHosts, true));
        }

        if (CSRF_CHECK_ORIGIN && !$isSameOrigin) {
            Security::logEvent('csrf_block', 'Cross-origin POST blocked', [
                'ip' => Security::clientIp(),
                'host' => $hostHeader,
                'forwarded_host' => $forwardedHostHeader,
                'allowed_hosts' => $allowedHosts,
                'origin' => $origin,
                'referer' => $referer,
                'route' => $_GET['route'] ?? '',
            ]);
            http_response_code(403);
            exit('Requisicao bloqueada.');
        }

        $token = (string) ($_POST['_token'] ?? '');
        $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            Security::logEvent('csrf_block', 'Invalid CSRF token', [
                'ip' => Security::clientIp(),
                'route' => $_GET['route'] ?? '',
            ]);
            http_response_code(419);
            $_SESSION['flash'] = 'Sessao invalida. Atualize a pagina e tente novamente.';
            $this->redirect('dashboard');
        }
    }
}
