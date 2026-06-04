#!/bin/sh
set -eu

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_DIR"

mkdir -p storage storage/logs
chmod 775 storage storage/logs
date +%s > storage/server_started_at.txt

if [ ! -f .env ]; then
    cp .env.example .env
    echo "[!] Arquivo .env criado a partir de .env.example."
    echo "[!] Preencha DATABASE_URL ou DB_HOST/DB_NAME/DB_USER/DB_PASS antes de iniciar."
    exit 1
fi

PORT="${PORT:-8000}"

echo "============================================"
echo "NEXUS SLA - Iniciador local"
echo "============================================"
echo "Acesse: http://127.0.0.1:${PORT}"
echo "Para criar o primeiro usuario, defina APP_SETUP_ENABLED=1 temporariamente."
echo "Pressione Ctrl+C para encerrar."
echo ""

php -S "0.0.0.0:${PORT}" -t public
