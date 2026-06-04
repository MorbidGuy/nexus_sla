<?php
// Script de emergência ajustado para ignorar validação de SSL no setup
$url = getenv('DATABASE_URL');
$dbopts = parse_url($url);

$host = $dbopts["host"];
$port = $dbopts["port"];
$user = $dbopts["user"];
$pass = $dbopts["pass"];
$name = ltrim($dbopts["path"],'/');

try {
    // Configuração ajustada para ignorar a verificação de certificado SSL
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Isso pula o erro de SSL
    ]);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conectado ao banco! Iniciando criação de tabelas...<br>";

    $schema = file_get_contents('../database/schema.sql');
    $rbac = file_get_contents('../database/rbac.sql');

    // Executa o Schema
    $pdo->exec($schema);
    echo "✅ Tabelas do Schema criadas!<br>";

    // Executa o RBAC
    $pdo->exec($rbac);
    echo "✅ Tabelas de permissões e cargos criadas!<br>";

    // Cria o usuário administrador
    $pass_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $sql_user = "INSERT IGNORE INTO users (name, email, password, role, active) 
                 VALUES ('Administrador Master', 'admin@admin.com', '$pass_hash', 'administrator', 1)";
    $pdo->exec($sql_user);
    echo "✅ Usuário mestre criado!<br>";

    echo "<h3>TUDO PRONTO! Agora você pode logar em: admin@admin.com / admin123</h3>";

} catch (Exception $e) {
    echo "❌ ERRO DE CONEXÃO: " . $e->getMessage();
}