<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__)); // Ajustado: public -> raiz
}

if (!defined('APP_NAME')) {
    require APP_ROOT . '/config/config.php';
}
require_once APP_ROOT . '/app/models/Database.php';
require_once APP_ROOT . '/app/models/Security.php';

if (!APP_SETUP_ENABLED) {
    http_response_code(403);
    exit('Setup desabilitado em producao. Defina APP_SETUP_ENABLED=1 apenas temporariamente.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo nao permitido.');
}

$ip = Security::clientIp();
$setupLimit = Security::throttle('setup_' . $ip, 30, 300);
if (!$setupLimit['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . (string) $setupLimit['retry_after']);
    exit('Muitas tentativas. Aguarde.');
}

$errors = [];
$success = '';

try {
    $db = Database::connection();
    $tableCheck = $db->query("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
    ");
    $usersTableExists = (int) ($tableCheck->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) === 1;
    $count = 0;

    if ($usersTableExists) {
        $result = $db->query('SELECT COUNT(*) AS total FROM users');
        $count = (int) ($result->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    if ($count > 0) {
        http_response_code(403);
        exit('Setup bloqueado: sistema ja inicializado.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['admin', 'gestor'], true) ? $_POST['role'] : 'admin';

        if ($name === '') {
            $errors[] = 'Nome obrigatorio.';
        }
        if (!$email) {
            $errors[] = 'E-mail invalido.';
        }
        if (
            strlen($password) < 12
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/\d/', $password)
            || !preg_match('/[^a-zA-Z\d]/', $password)
        ) {
            $errors[] = 'Senha fraca. Use 12+ caracteres com maiuscula, minuscula, numero e simbolo.';
        }

        if ($errors === []) {
            $sqlFile = APP_ROOT . '/database/schema.sql';
            if (!file_exists($sqlFile)) {
                throw new RuntimeException("Arquivo de estrutura SQL nao encontrado em: " . $sqlFile);
            }

            $sql = file_get_contents($sqlFile);
            foreach (explode(';', $sql) as $query) {
                $query = trim($query);
                if ($query === '') continue;
                
                $upperQuery = strtoupper($query);
                if (!str_starts_with($upperQuery, 'CREATE DATABASE') && !str_starts_with($upperQuery, 'USE ')) {
                    $db->exec($query);
                }
            }

            $stmt = $db->prepare('INSERT INTO users (name, email, password, role, active) VALUES (:name, :email, :password, :role, 1)');
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);

            Security::logEvent('setup_success', 'Initial user created', ['ip' => $ip, 'email' => $email]);
            $success = 'Usuario inicial criado. Desative APP_SETUP_ENABLED nas variaveis do Railway.';
        } else {
            Security::logEvent('setup_failed', 'Invalid setup payload', ['ip' => $ip]);
        }
    }
} catch (Throwable $e) {
    Security::logEvent('setup_error', 'Setup execution error', ['ip' => $ip, 'message' => $e->getMessage()]);
    if (APP_DEBUG) {
        $errors[] = 'Erro interno: ' . $e->getMessage();
    } else {
        $errors[] = 'Erro interno ao processar setup.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Seguro - Nexus SLA</title>
</head>
<body>
    <h1>Setup inicial seguro</h1>
    <?php foreach ($errors as $error): ?>
        <p style="color: #b91c1c;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>
    <?php if ($success !== ''): ?>
        <p style="color: #166534;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Nome</label><br>
        <input type="text" name="name" required><br>
        <label>E-mail</label><br>
        <input type="email" name="email" required><br>
        <label>Senha forte</label><br>
        <input type="password" name="password" required minlength="12"><br>
        <label>Perfil</label><br>
        <select name="role">
            <option value="admin">Administrador</option>
            <option value="gestor">Gestor</option>
        </select><br><br>
        <button type="submit">Criar conta inicial</button>
    </form>
</body>
</html>
