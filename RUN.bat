@echo off
setlocal enabledelayedexpansion

title Nexus SLA

set "PROJECT_DIR=%~dp0"
cd /d "%PROJECT_DIR%"

if not exist "storage" mkdir "storage"
if not exist "storage\logs" mkdir "storage\logs"
powershell -Command "[int][(Get-Date -UFormat %%s)]" > "storage\server_started_at.txt"

if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo [!] Arquivo .env criado a partir de .env.example.
    echo [!] Preencha DATABASE_URL ou DB_HOST/DB_NAME/DB_USER/DB_PASS antes de iniciar.
    pause
    exit /b 1
)

if not defined PORT set "PORT=8000"

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [!] PHP nao encontrado no PATH.
    echo Instale PHP 8.2+ ou use o Dockerfile para deploy.
    pause
    exit /b 1
)

echo ============================================
echo NEXUS SLA - Iniciador local
echo ============================================
echo Acesse: http://127.0.0.1:%PORT%
echo Para criar o primeiro usuario, defina APP_SETUP_ENABLED=1 temporariamente.
echo Pressione Ctrl+C para encerrar.
echo.

php -S 0.0.0.0:%PORT% -t public
