#!/bin/bash
# sync-clean.sh

echo "🧹 Paso 1: Deteniendo contenedores y eliminando volúmenes..."
docker compose down -v --remove-orphans

echo "🗑️  Paso 2: Reconstruyendo imágenes sin caché..."
docker compose build --no-cache

echo "🚀 Paso 3: Levantando el stack..."
docker compose up -d

echo "⏳ Esperando que la BD esté lista..."
sleep 15

echo "✅ Listo en http://localhost:8080"
docker compose logs --tail=20
