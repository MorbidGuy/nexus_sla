#!/bin/bash

# Nexus SLA - Instalador de Dependências para Ubuntu
echo "============================================"
echo "Configurando ambiente Nexus SLA no Ubuntu"
echo "============================================"

# Atualiza repositórios
sudo apt update

# Instala PHP e extensões necessárias
echo "[1/2] Instalando PHP e módulos..."
sudo apt install -y php-cli php-common php-mysql php-mbstring php-xml php-curl

# Instala MySQL Server
echo "[2/2] Instalando MySQL Server..."
sudo apt install -y mysql-server

# Habilita extensões no PHP
sudo phpenmod pdo_mysql mbstring

echo ""
echo "--------------------------------------------"
echo "Instalacao concluida com sucesso!"
echo "--------------------------------------------"
echo "DICA: configure credenciais fortes fora do codigo e informe DATABASE_URL ou DB_* no .env."
echo ""
echo "Agora voce pode iniciar o sistema com: ./RUN.sh"
echo "--------------------------------------------"

# Tenta dar permissão de execução ao RUN.sh automaticamente
if [ -f "RUN.sh" ]; then
    chmod +x RUN.sh
    echo "Permissoes para RUN.sh aplicadas."
fi

exit 0
