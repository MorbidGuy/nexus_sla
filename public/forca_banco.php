<?php
// DADOS REAIS COPIADOS DA SUA IMAGEM DA AIVEN
$host = 'mysql-c288a3-kirashinatsu-38a0.i.aivencloud.com';
$port = '26724';
$user = 'avnadmin';
$pass = 'AVNS_T7e6ZzJLLtfGj060u5v'; // Sua senha da Aiven
$name = 'defaultdb';

try {
    // Conexão direta ignorando variáveis de ambiente
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ CONECTADO COM SUCESSO!<br>Iniciando criação de tabelas...<br>";

    $schema = file_get_contents('../database/schema.sql');
    $rbac = file_get_contents('../database/rbac.sql');

    $pdo->exec($schema);
    echo "✅ Tabelas do Schema criadas!<br>";

    $pdo->exec($rbac);
    echo "✅ Tabelas de permissões criadas!<br>";

    $pass_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT IGNORE INTO users (name, email, password, role, active) 
                VALUES ('Administrador', 'admin@admin.com', '$pass_hash', 'administrator', 1)");
    
    echo "<h2>🚀 TUDO PRONTO! Tente logar agora.</h2>";

} catch (Exception $e) {
    echo "❌ ERRO AINDA PERSISTE: " . $e->getMessage();
}