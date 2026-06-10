# 🔍 Diagnóstico: Por qué el proyecto se rompe al compartir entre desarrolladores

## Resumen del Problema

El proyecto funciona perfecto en una máquina pero se rompe en otra al hacer `git pull` o descomprimir un `.rar`. Esto es un **problema clásico de entorno no reproducible**, y tras analizar tus archivos de configuración, encontré **4 causas raíz concretas** en tu proyecto.

---

## 🚨 CAUSA RAÍZ #1 — El Dockerfile sube el `.output/` ya compilado (La más crítica)

### El problema
```dockerfile
# Dockerfile (raíz del proyecto — para el frontend)
COPY lab-dashboard/ ./
RUN rm -rf .output/server/node_modules && cd .output/server && npm install --omit=dev
CMD ["node", ".output/server/index.mjs"]
```

Tu Dockerfile **NO compila Nuxt**. En cambio, copia el directorio `lab-dashboard/` completo, que incluye `.output/` (el build ya compilado). Esto significa que:

1. **El Desarrollador A** hace `nuxt build` en su máquina → genera `.output/` con su versión local.
2. Sube `.output/` al `.rar` o a Git.
3. **El Desarrollador B** descarga ese `.output/` PRE-COMPILADO que fue generado en otra máquina.
4. Docker simplemente copia ese `.output/` viejo y lo ejecuta **sin recompilar nunca**.

### Evidencia en tu `.gitignore`
```gitignore
lab-dashboard/.output/   ✅ Ignorado en Git
```

Está bien para Git, **pero con el `.rar` esto no aplica**. Si el Desarrollador A empaqueta en `.rar` directamente desde la carpeta, `.output/` y `node_modules/` se incluyen completamente.

### La solución correcta
El Dockerfile debe compilar Nuxt desde el código fuente:

```dockerfile
# ✅ Dockerfile CORRECTO para el frontend
FROM node:20-alpine AS builder
WORKDIR /app
# Instalar pnpm (tu proyecto usa pnpm@11.1.3)
RUN corepack enable && corepack prepare pnpm@11.1.3 --activate
COPY lab-dashboard/package.json lab-dashboard/pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile
COPY lab-dashboard/ ./
RUN pnpm run build

FROM node:20-alpine AS runner
WORKDIR /app
COPY --from=builder /app/.output/ ./.output/
EXPOSE 3000
ENV PORT=3000
ENV HOST=0.0.0.0
CMD ["node", ".output/server/index.mjs"]
```

> [!CAUTION]
> Tu `package.json` declara `"packageManager": "pnpm@11.1.3"` pero el Dockerfile usa `npm install`. Esto puede generar árboles de dependencias distintos entre desarrolladores.

---

## 🚨 CAUSA RAÍZ #2 — El volumen de la Base de Datos persiste entre reinicios

### El problema
```yaml
# docker-compose.yml
volumes:
  - db_data:/var/lib/mysql
  - ./api.pos/u228744577_pos.sql:/docker-entrypoint-initdb.d/01-init.sql:ro
```

El archivo `01-init.sql` **solo se ejecuta cuando el volumen `db_data` está vacío** (primera vez que corre el contenedor). Si el volumen ya existe de una corrida anterior, MariaDB **ignora completamente el SQL de inicialización**.

### Escenario de fallo:
1. Desarrollador A corre el proyecto → BD se inicializa con `u228744577_pos.sql` → hace modificaciones a la BD (nuevas tablas, migraciones, datos).
2. Desarrollador A no exporta esos cambios al `.sql`.
3. Desarrollador B hace `docker compose up` → el volumen `db_data` en su máquina está vacío → BD se inicializa con el `.sql` desactualizado.
4. La API falla porque espera tablas/columnas que no existen.

### La solución
```bash
# Al recibir código nuevo, SIEMPRE limpiar volúmenes primero:
docker compose down -v   # -v elimina los volúmenes nombrados
docker compose up --build
```

Y además: **cada vez que cambies la BD, actualiza el `.sql`**:
```bash
# Exportar el estado actual de la BD al archivo .sql
docker exec pos-db mariadb-dump -uroot -proot u228744577_pos > ./api.pos/u228744577_pos.sql
```

---

## 🚨 CAUSA RAÍZ #3 — `node_modules/` y `.nuxt/` presentes en el proyecto

### El problema

Al revisar el directorio `lab-dashboard/`, encontré que **existen físicamente** las carpetas:
- `lab-dashboard/node_modules/` ✅ ignorada en `.gitignore` pero NO en `.rar`
- `lab-dashboard/.nuxt/` ✅ ignorada en `.gitignore` pero NO en `.rar`

Cuando se empaqueta en `.rar`, estas carpetas se incluyen. El `node_modules/` contiene binarios compilados para el sistema operativo específico de quien lo generó.

| Carpeta | Windows (Dev A) | Linux (Docker) |
|---|---|---|
| `node_modules/` binarios | `.dll` / `.exe` | `.so` / ELF |

Docker está en Linux. Si el `node_modules/` fue generado en Windows y se copia al contenedor Linux, **los binarios nativos fallan**.

### La solución
Antes de empaquetar en `.rar`, el Desarrollador A debe:
```bash
# Limpiar todo lo generado localmente ANTES de hacer el .rar
Remove-Item -Recurse -Force lab-dashboard\node_modules
Remove-Item -Recurse -Force lab-dashboard\.nuxt
Remove-Item -Recurse -Force lab-dashboard\.output
```

O mejor aún: **usar Git siempre en lugar de `.rar`** ya que `.gitignore` excluye estas carpetas correctamente.

---

## 🚨 CAUSA RAÍZ #4 — El `vendor/` de PHP está subido al repositorio

### El problema
```
api.pos/vendor/   ← Directorio EXISTE en el proyecto
```

En el `.gitignore`:
```gitignore
api.pos/vendor/   ✅ Ignorado
```

Pero al igual que con `node_modules/`, si se comparte via `.rar`, el `vendor/` de Composer se incluye. Este directorio contiene dependencias PHP compiladas para el OS del Desarrollador A.

El `api.pos/Dockerfile` tampoco ejecuta `composer install`:
```dockerfile
FROM php:8.2-apache
COPY . /var/www/html
# ← No hay RUN composer install aquí
```

Esto significa que si el `vendor/` copiado es de Windows, puede fallar en el contenedor Linux de Apache.

### La solución
Agregar en el `api.pos/Dockerfile`:
```dockerfile
FROM php:8.2-apache
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip curl \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader
```

---

## ✅ PLAN DE ACCIÓN — Flujo Estandarizado para Ambos Desarrolladores

### Script de inicio limpio (PowerShell — Windows)

Crea un archivo `sync-clean.ps1` en la raíz del proyecto:

```powershell
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
```

### Script equivalente (Bash — Linux/Mac)

```bash
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
```

---

## 📋 Reglas de Equipo a Establecer

| Regla | Motivo |
|---|---|
| ❌ **Nunca usar `.rar`** para compartir código | Incluye `node_modules`, `.output`, `vendor` con binarios incompatibles |
| ✅ **Siempre usar Git** | `.gitignore` excluye los directorios problemáticos |
| ✅ **Ejecutar `sync-clean.ps1` tras cada `git pull`** | Evita estado sucio de la BD y caché de Docker |
| ✅ **Actualizar `u228744577_pos.sql` al cambiar la BD** | Mantiene la BD sincronizada entre desarrolladores |
| ✅ **`docker compose build --no-cache`** al recibir cambios grandes | Fuerza recompilación desde cero |

---

## 🗺️ Resumen Visual del Flujo Correcto

```
Desarrollador A                          Desarrollador B
──────────────                           ───────────────
1. Modifica código fuente                
2. Si cambió la BD:                      
   docker exec pos-db mariadb-dump ...   
   → actualiza u228744577_pos.sql        
3. git add . && git commit && git push   
                                         4. git pull
                                         5. Ejecuta sync-clean.ps1:
                                            - docker compose down -v
                                            - docker compose build --no-cache
                                            - docker compose up -d
                                         6. ✅ Todo funciona
```
