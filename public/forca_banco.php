<?php
// Teste direto com as variáveis separadas (mais seguro que o link longo)
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');

try {
    // Conexão forçada ignorando erros de certificado
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ]);
    
    echo "<h1>✅ CONECTADO COM SUCESSO!</h1>";
    echo "O banco de dados está respondendo perfeitamente.<br>";
    
    // Testa se a tabela users existe
    $query = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($query->rowCount() > 0) {
        echo "✅ Tabelas encontradas! Pode logar em: <b>admin@admin.com</b> / <b>admin123</b>";
    } else {
        echo "❌ Tabelas não encontradas. Rode o script do Workbench novamente.";
    }
} catch (Exception $e) {
    echo "<h1>❌ ERRO DE CONEXÃO:</h1>" . $e->getMessage();
}