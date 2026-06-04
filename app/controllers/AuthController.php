<?php

declare(strict_types=1);

final class AuthController extends BaseController
{
    private const MANAGER_CONFIRMATION_SALT = 'nexus-sla-login-bootstrap-v1';
    private const MANAGER_CONFIRMATION_HASH = '143866148bb9ecdb08ca8e824429d9a785bccc15d100458ce895f7963037cd33';
    private const MANAGER_CONFIRMATION_ITERATIONS = 310000;

    public function showLogin(): void
    {
        $this->guest();
        $this->view('auth/login', [
            'title' => 'Entrar',
            'csrfField' => $this->csrfField(),
        ]);
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

    public function createManagerUser(): void
    {
        $this->guest();
        $this->verifyCsrf();

        $clientIp = Security::clientIp();
        $limit = Security::throttle('manager_signup_' . $clientIp, 5, 600);
        if (!$limit['allowed']) {
            header('Retry-After: ' . (string) $limit['retry_after']);
            Security::logEvent('manager_signup_throttle', 'Too many manager signup attempts', ['ip' => $clientIp]);
            http_response_code(429);
            exit('Muitas tentativas. Tente novamente em instantes.');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $confirmationPassword = (string) ($_POST['manager_confirmation_password'] ?? '');

        if ($name === '' || !$email) {
            Security::logEvent('manager_signup_failed', 'Invalid manager signup identity', ['ip' => $clientIp, 'email' => $email ?: null]);
            $_SESSION['flash'] = 'Informe nome e e-mail validos para criar o gestor.';
            $this->redirect('login');
        }

        if (!$this->isStrongPassword($password) || $password !== $passwordConfirmation) {
            Security::logEvent('manager_signup_failed', 'Invalid manager signup password', ['ip' => $clientIp, 'email' => $email]);
            $_SESSION['flash'] = 'A senha do gestor deve ter 12+ caracteres e a confirmacao precisa ser igual.';
            $this->redirect('login');
        }

        if (!$this->isManagerConfirmationPassword($confirmationPassword)) {
            Security::logEvent('manager_signup_failed', 'Invalid manager confirmation password', ['ip' => $clientIp, 'email' => $email]);
            $_SESSION['flash'] = 'Senha de confirmacao invalida.';
            $this->redirect('login');
        }

        $hashAlgo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $passwordHash = password_hash($password, $hashAlgo);

        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO users (name, email, password, role, active)
             VALUES (:name, :email, :password, :role, 1)
             ON DUPLICATE KEY UPDATE
               name = VALUES(name),
               password = VALUES(password),
               role = VALUES(role),
               active = 1'
        );
        $stmt->execute([
            'name' => $name,
            'email' => (string) $email,
            'password' => $passwordHash,
            'role' => 'gestor',
        ]);

        Security::logEvent('manager_signup_success', 'Manager user created or updated', ['ip' => $clientIp, 'email' => $email]);
        $_SESSION['flash'] = 'Usuario gestor criado ou atualizado. Agora acesse com o e-mail e senha cadastrados.';
        $this->redirect('login');
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

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 12
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/\d/', $password) === 1
            && preg_match('/[^a-zA-Z\d]/', $password) === 1;
    }

    private function isManagerConfirmationPassword(string $password): bool
    {
        $hash = hash_pbkdf2(
            'sha256',
            $password,
            self::MANAGER_CONFIRMATION_SALT,
            self::MANAGER_CONFIRMATION_ITERATIONS,
            64
        );

        return hash_equals(self::MANAGER_CONFIRMATION_HASH, $hash);
    }
}
