# sync-clean.ps1 — Sincronización limpia del proyecto
# Ejecutar después de git pull o al recibir un .rar

Write-Host "🧹 Paso 1: Deteniendo contenedores y eliminando volúmenes..." -ForegroundColor Yellow
docker compose down -v --remove-orphans

Write-Host "🗑️  Paso 2: Eliminando imágenes cacheadas del proyecto..." -ForegroundColor Yellow
docker compose build --no-cache

Write-Host "🚀 Paso 3: Levantando el stack completo..." -ForegroundColor Green
docker compose up -d

Write-Host "⏳ Esperando que la BD esté lista..." -ForegroundColor Cyan
Start-Sleep -Seconds 15

Write-Host "✅ Proyecto listo en http://localhost:8080" -ForegroundColor Green
docker compose logs --tail=20
