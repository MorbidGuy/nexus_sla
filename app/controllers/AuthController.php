<?php

declare(strict_types=1);

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $this->guest();
        $this->view('auth/login', ['title' => 'Entrar', 'csrfField' => $this->csrfField()]);
    }

    public function login(): void
    {
        $this->guest();
        $this->verifyCsrf();

        $clientIp = Security::clientIp();
        $ipLimit = Security::throttle('login_ip_' . $clientIp, 20, 300);
        if (!$ipLimit['allowed']) {
            header('Retry-After: ' . (string) $ipLimit['retry_after']);
            Security::logEvent('auth_throttle', 'Too many login attempts by IP', ['ip' => $clientIp]);
            http_response_code(429);
            exit('Muitas tentativas. Tente novamente em instantes.');
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || $password === '') {
            $_SESSION['flash'] = 'Informe e-mail e senha validos.';
            $this->redirect('login');
        }

        $emailKey = strtolower((string) $email);
        $emailLimit = Security::throttle('login_email_' . $emailKey, 10, 300);
        if (!$emailLimit['allowed']) {
            header('Retry-After: ' . (string) $emailLimit['retry_after']);
            Security::logEvent('auth_throttle', 'Too many login attempts by e-mail', [
                'ip' => $clientIp,
                'email' => $emailKey,
            ]);
            http_response_code(429);
            exit('Muitas tentativas. Tente novamente em instantes.');
        }

        $user = (new User())->findByEmail((string) $email);

        if (!$user || !password_verify($password, $user['password'])) {
            usleep(250000);
            Security::logEvent('auth_failed', 'Invalid credentials', ['ip' => $clientIp, 'email' => $emailKey]);
            $_SESSION['flash'] = 'Credenciais invalidas.';
            $this->redirect('login');
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['login_at'] = time();
        $_SESSION['last_activity'] = time();

        Security::logEvent('auth_success', 'User logged in', ['ip' => $clientIp, 'user_id' => $user['id']]);
        $this->redirect('dashboard');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Security::logEvent('auth_logout', 'User logged out', [
            'ip' => Security::clientIp(),
            'user_id' => $_SESSION['user']['id'] ?? null,
        ]);

        session_destroy();
        $this->redirect('login');
    }
}
