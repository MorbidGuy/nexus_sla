@echo off
setlocal enabledelayedexpansion

title Nexus SLA

set "PROJECT_DIR=%~dp0"
set "XAMPP_DIR=C:\xampp"
set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "APP_URL=http://127.0.0.1:8000"

cd /d "%PROJECT_DIR%"

if not exist "storage" mkdir "storage"
powershell -Command "[int][(Get-Date -UFormat %s)]" > "storage\server_started_at.txt"

echo [0] Auto-Configurando ambiente...

set "MYSQL_EXE="
for /d %%D in ("C:\Program Files\MySQL\MySQL Server *") do (
    if exist "%%D\bin\mysql.exe" (
        set "MYSQL_EXE=%%D\bin\mysql.exe"
        echo [!] MySQL Manual detectado em: %%D
    )
)

if "!MYSQL_EXE!"=="" (
    if exist "%XAMPP_DIR%\mysql\bin\mysql.exe" set "MYSQL_EXE=%XAMPP_DIR%\mysql\bin\mysql.exe"
)

if exist "%PHP_EXE%" (
    for /f "delims=" %%i in ('"%PHP_EXE%" -i ^| findstr "Loaded Configuration File"') do set "PHP_INI=%%i"
    set "PHP_INI=!PHP_INI:*=>=!"
    set "PHP_INI=!PHP_INI: =!"
    
    if exist "!PHP_INI!" (
        echo [!] Verificando extensoes no php.ini...
        powershell -Command "(gc '!PHP_INI!') -replace ';extension=pdo_mysql', 'extension=pdo_mysql' | Out-File -encoding ASCII '!PHP_INI!'"
        powershell -Command "(gc '!PHP_INI!') -replace ';extension=mbstring', 'extension=mbstring' | Out-File -encoding ASCII '!PHP_INI!'"
    )
)

cls
echo ============================================
echo NEXUS SLA - Iniciador
echo ============================================
echo.

if not exist ".env" (
    echo [!] Arquivo .env nao encontrado. Criando com valores padrao...
    echo DB_HOST=127.0.0.1> .env
    echo DB_PORT=3306>> .env
    echo DB_NAME=nexus_sla>> .env
    echo DB_USER=root>> .env
    echo DB_PASS=cicada3301>> .env
)

setlocal disabledelayedexpansion
for /f "tokens=1,2 delims==" %%A in (.env) do set "%%A=%%B"
setlocal enabledelayedexpansion

if not defined DB_PASS set DB_PASS=cicada3301

echo [1] Verificando banco de dados...
echo Host: !DB_HOST!
echo Database: !DB_NAME!
echo.

echo [2] Validando conexao e estrutura...

if exist "%MYSQL_EXE%" (
    set "MYSQL_CMD="%MYSQL_EXE%""
) else (
    where mysql >nul 2>nul
    if !errorlevel! equ 0 (
        set "MYSQL_CMD=mysql"
    ) else (
        echo [!] ERRO: Comando 'mysql' nao encontrado.
        echo Adicione a pasta 'bin' do seu MySQL ao PATH do Windows.
        goto :ERROR
    )
)

echo [2.1] Testando conexao e criando banco...
%MYSQL_CMD% -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p!DB_PASS! -e "CREATE DATABASE IF NOT EXISTS !DB_NAME! CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if !errorlevel! neq 0 (
    echo [!] Erro persistente. Tentando usar comando 'mysql' do PATH...
    mysql -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p!DB_PASS! -e "CREATE DATABASE IF NOT EXISTS !DB_NAME!;" 2>nul
    if !errorlevel! neq 0 (
        echo.
        echo [!] ERRO CRITICO: Nao consigo conectar ao MySQL.
        echo Por favor, abra seu "MySQL Command Line Client" e digite:
        echo ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'cicada3301';
        goto :ERROR
    )
)

echo [2.2] Importando estrutura de tabelas...
%MYSQL_CMD% -h !DB_HOST! -P !DB_PORT! -u !DB_USER! -p!DB_PASS! !DB_NAME! < database\schema.sql

echo [3] Banco de dados pronto.

echo [4] Iniciando servidor PHP...
echo.

if not exist "%PHP_EXE%" (
    where php >nul 2>nul
    if !errorlevel! equ 0 (
        set "PHP_EXE=php"
    ) else (
        echo [!] AVISO: Comando 'php' nao encontrado no PATH.
    )
)

start "PHP - Nexus SLA" /D "%PROJECT_DIR%" cmd /k "%PHP_EXE%" -S 0.0.0.0:8000 -t public

timeout /t 2 /nobreak >nul

for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /C:"IPv4"') do set MACHINE_IP=%%a
set MACHINE_IP=!MACHINE_IP: =!

cls
echo ============================================
echo TUDO PRONTO!
echo ============================================
echo.
echo Acesso Local:
echo   http://127.0.0.1:8000
echo.
echo Acesso Pela Rede:
echo   http://!MACHINE_IP!:8000
echo.
echo [ Credenciais Padrao (Inseridas no Banco de Dados) ]
echo [ LOGIN DE ACESSO ]
echo.
echo   USUARIO: usuario@nexus.local   / SENHA: Demandas@2026
echo   GESTOR:  gestor@nexus.local    / SENHA: Gestor@2026
echo   ADMIN:   kirashi_natsu@administrator.local / SENHA: Kirashi@2026
echo.
echo Pressione qualquer tecla para encerrar este inicializador...

start "" "%APP_URL%"
pause >nul
exit /b

:ERROR
pause
exit /b
