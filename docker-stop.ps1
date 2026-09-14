# ========================================
# Script de detención para Docker - AMPAR
# ========================================
# Uso: .\docker-stop.ps1

param(
    [switch]$Clean
)

Write-Host "🐳 AMPAR - Docker Stop" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host ""

if ($Clean) {
    Write-Host "🗑️  Deteniendo y eliminando contenedores..." -ForegroundColor Yellow
    docker-compose down
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Contenedores eliminados exitosamente" -ForegroundColor Green
        
        $removeImages = Read-Host "¿Deseas eliminar también las imágenes? (s/n)"
        if ($removeImages -eq "s") {
            Write-Host "🗑️  Eliminando imágenes..." -ForegroundColor Yellow
            docker-compose down --rmi all
            Write-Host "✓ Imágenes eliminadas" -ForegroundColor Green
        }
    } else {
        Write-Host "✗ Error al eliminar contenedores" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "⏸️  Deteniendo contenedores..." -ForegroundColor Yellow
    docker-compose stop
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Contenedores detenidos exitosamente" -ForegroundColor Green
        Write-Host ""
        Write-Host "Para iniciar nuevamente:" -ForegroundColor Cyan
        Write-Host "  docker-compose start" -ForegroundColor White
        Write-Host "  o ejecuta: .\docker-start.ps1" -ForegroundColor White
        Write-Host ""
        Write-Host "Para eliminar completamente:" -ForegroundColor Cyan
        Write-Host "  .\docker-stop.ps1 -Clean" -ForegroundColor White
    } else {
        Write-Host "✗ Error al detener contenedores" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "📊 Estado actual:" -ForegroundColor Cyan
docker-compose ps
