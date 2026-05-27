PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

clear
echo "============================================"
echo "NEXUS SLA - Iniciador (Linux/macOS)"
echo "============================================"
echo ""

mkdir -p storage
chmod 775 storage
date +%s > storage/server_started_at.txt

if [ ! -f .env ]; then
    echo "[!] Arquivo .env nao encontrado. Criando com valores padrao..."
    cat <<EOF > .env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nexus_sla
DB_USER=root
DB_PASS=cicada3301
EOF
fi

export $(grep -v '^#' .env | xargs)

if command -v mysql &> /dev/null; then
    echo "[1] Validando conexao e estrutura do banco..."
    
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
        echo "[2] Banco de dados pronto."
    else
        echo "[!] AVISO: Nao foi possivel conectar ao MySQL automaticamente."
        echo "Verifique se o servico esta ativo e se a senha no .env esta correta."
    fi
else
    echo "[!] AVISO: Comando 'mysql' nao encontrado. Pulando verificacao automatica."
fi

if command -v php &> /dev/null; then
    echo ""
    echo "[3] Iniciando servidor PHP em http://0.0.0.0:8000"
    echo "--------------------------------------------"
    echo "[ LOGIN DE ACESSO ]"
    echo ""
    echo "  USUARIO: usuario@nexus.local   / SENHA: Demandas@2026"
    echo "  GESTOR:  gestor@nexus.local    / SENHA: Gestor@2026"
    echo "  ADMIN:   kirashi_natsu@administrator.local / SENHA: Kirashi@2026"
    echo "--------------------------------------------"
    echo "Pressione Ctrl+C para encerrar o servidor."
    echo ""
    
    php -S 0.0.0.0:8000 -t public
else
    echo "[!] ERRO: PHP nao encontrado. Por favor, instale o php-cli e pdo_mysql."
    exit 1
fi

exit 0