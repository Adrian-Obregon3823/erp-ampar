# ========================================
# Script de inicio para Docker - AMPAR
# ========================================
# Uso: .\docker-start.ps1

Write-Host "🐳 AMPAR - Docker Setup" -ForegroundColor Cyan
Write-Host "========================" -ForegroundColor Cyan
Write-Host ""

# Verificar si Docker está corriendo
try 
{
    docker ps 2>&1 | Out-Null
    if ($LASTEXITCODE -ne 0) 
    {
        throw "Docker no responde"
    }
    Write-Host "✓ Docker está corriendo" -ForegroundColor Green
}
catch 
{
    Write-Host "✗ Docker no está corriendo. Por favor inicia Docker Desktop." -ForegroundColor Red
    exit 1
}

# Verificar si existe .env
if (-Not (Test-Path ".env")) 
{
    Write-Host "⚠ Archivo .env no encontrado" -ForegroundColor Yellow
    
    if (Test-Path ".env.example") 
    {
        Write-Host "📋 Copiando .env.example a .env..." -ForegroundColor Yellow
        Copy-Item ".env.example" ".env"
        Write-Host "✓ Archivo .env creado. Por favor edítalo con tus configuraciones." -ForegroundColor Green
        Write-Host ""
        Write-Host "Importante: Ajusta las siguientes variables en .env:" -ForegroundColor Yellow
        Write-Host "  - DB_PATH: Ruta a tu base de datos Firebird" -ForegroundColor Yellow
        Write-Host "  - DB_HOST: Generalmente 'host.docker.internal'" -ForegroundColor Yellow
        Write-Host "  - Credenciales de la base de datos" -ForegroundColor Yellow
        Write-Host ""
        
        $continue = Read-Host "¿Deseas continuar con los valores por defecto? (s/n)"
        if ($continue -ne "s") 
        {
            Write-Host "Edita el archivo .env y ejecuta este script nuevamente." -ForegroundColor Yellow
            exit 0
        }
    }
    else 
    {
        Write-Host "✗ No se encontró .env.example. Crea un archivo .env manualmente." -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "🔨 Construyendo imagen Docker..." -ForegroundColor Cyan
docker-compose build

if ($LASTEXITCODE -eq 0) 
{
    Write-Host "✓ Imagen construida exitosamente" -ForegroundColor Green
    Write-Host ""
    Write-Host "🚀 Levantando contenedor..." -ForegroundColor Cyan
    docker-compose up -d
    
    if ($LASTEXITCODE -eq 0) 
    {
        Write-Host ""
        Write-Host "✓ Contenedor levantado exitosamente" -ForegroundColor Green
        Write-Host ""
        Write-Host "📊 Estado del contenedor:" -ForegroundColor Cyan
        docker-compose ps
        Write-Host ""
        Write-Host "🌐 Aplicación disponible en: http://localhost:8080" -ForegroundColor Green
        Write-Host ""
        Write-Host "📝 Comandos útiles:" -ForegroundColor Cyan
        Write-Host "  Ver logs:      docker-compose logs -f" -ForegroundColor White
        Write-Host "  Detener:       docker-compose stop" -ForegroundColor White
        Write-Host "  Reiniciar:     docker-compose restart" -ForegroundColor White
        Write-Host "  Eliminar:      docker-compose down" -ForegroundColor White
    }
    else 
    {
        Write-Host "✗ Error al levantar el contenedor" -ForegroundColor Red
        Write-Host "Revisa los logs con: docker-compose logs" -ForegroundColor Yellow
        exit 1
    }
}
else 
{
    Write-Host "✗ Error al construir la imagen" -ForegroundColor Red
    exit 1
}
