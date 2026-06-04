<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');
$port = getenv('DB_PORT');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass, [
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    
    echo "<h1>Conexão OK!</h1>";
    
    $email = 'admin@admin.com';
    $senha_digitada = '123456';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "Usuário encontrado: " . $user['email'] . "<br>";
        if (password_verify($senha_digitada, $user['password'])) {
            echo "✅ SENHA CORRETA! O problema é no código do seu Login (Session ou Redirect).";
        } else {
            echo "❌ SENHA INCORRETA NO BANCO!";
        }
    } else {
        echo "❌ USUÁRIO NÃO ENCONTRADO!";
    }

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}