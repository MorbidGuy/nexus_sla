Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Configurando ambiente Nexus SLA no Windows" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

if (!([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "!!! ERRO: Execute este script como ADMINISTRADOR !!!" -ForegroundColor Red
    return
}

Write-Host "`n[1/2] Instalando PHP 8.2 via Winget..." -ForegroundColor Yellow
try {
    winget install -e --id PHP.PHP.8.2 --accept-source-agreements --accept-package-agreements --scope machine
} catch {
    Write-Host "Aviso: Falha ao instalar PHP ou já existente." -ForegroundColor Gray
}

Write-Host "`n[2/2] Instalando MySQL Server via Winget..." -ForegroundColor Yellow
try {
    winget install -e --id Oracle.MySQL --accept-source-agreements --accept-package-agreements --scope machine
} catch {
    Write-Host "Aviso: Falha ao instalar MySQL ou já existente." -ForegroundColor Gray
}

Write-Host "`n--------------------------------------------" -ForegroundColor Green
Write-Host "Instalação solicitada com sucesso!" -ForegroundColor Green
Write-Host "--------------------------------------------" -ForegroundColor Green
Write-Host "IMPORTANTE:" -ForegroundColor White
Write-Host "1. Você deve REINICIAR o seu terminal/CMD para que os comandos 'php' e 'mysql' sejam reconhecidos." -ForegroundColor White
Write-Host "2. Configure credenciais fortes fora do codigo e informe DATABASE_URL ou DB_* no .env." -ForegroundColor White
Write-Host "3. Após reiniciar o terminal, execute o arquivo RUN.bat." -ForegroundColor White
Write-Host "--------------------------------------------" -ForegroundColor Green

