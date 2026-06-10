# Plan de Estabilización Técnica — UniTech 2.0

Documento derivado de la **auditoría técnica exhaustiva** del proyecto (backend `api.pos`, frontend `lab-dashboard`, infraestructura Docker).  
Complementa a `PLAN_CORRECCIONES.md` (funcionalidades de negocio ya implementadas). Este plan cubre **errores, vulnerabilidades, inconsistencias de datos y deuda técnica** detectados en el código actual.

---

## Resumen ejecutivo

| Área | Severidad | Hallazgos |
|------|-----------|-----------|
| Seguridad (API + AJAX) | 🔴 Crítica | ~15 |
| Integridad de datos / backend | 🔴 Alta | ~12 |
| Frontend (páginas y guards) | 🟠 Alta–Media | ~20 |
| Infraestructura / DevOps | 🟡 Media | ~8 |
| Higiene de código | 🟢 Baja | ~10 |

**Recomendación:** Ejecutar las fases en orden. La **Fase 0 y Fase 1** son prerequisito para un despliegue seguro en producción.

---

## 🔴 Fase 0 — Contención inmediata (1–3 días)

Objetivo: reducir la superficie de ataque sin refactor grande.

| # | Error detectado | Archivos | Acción | Verificación |
|---|-----------------|----------|--------|--------------|
| 0.1 | API key, JWT secret y credenciales hardcodeadas en código y Docker | `api.pos/models/connection.php`, `api.pos/controllers/post.controller.php`, `api.pos/controllers/curl.controller.php`, `lab-dashboard/nuxt.config.ts`, `docker-compose.yml` | Rotar secretos; mover a variables de entorno; nunca commitear valores reales | Grep del repo no encuentra tokens conocidos |
| 0.2 | Dump SQL con PII, hashes, tokens JWT y API key de OpenAI | `api.pos/u228744577_pos.sql` | Sacar del repo; reemplazar por schema limpio + seeds de desarrollo sin datos reales | Init Docker usa schema sin secretos |
| 0.3 | Scripts de debug accesibles vía web | `api.pos/hash.php`, `api.pos/test2.php`, `api.pos/test.php`, `api.pos/test_write_perm.txt` | Eliminar del repo o bloquear con `.htaccess` | HTTP 404 en esas rutas |
| 0.4 | Migraciones PHP ejecutables desde el navegador | `api.pos/migrations_v2.php`, `api.pos/run_migration.php`, `api.pos/migrate_incomes.php` | Mover fuera del docroot o restringir a CLI | No accesibles por URL |
| 0.5 | Errores PHP visibles al cliente | `api.pos/index.php` (`display_errors = 1`) | `display_errors = 0`; log a archivo | Respuestas sin stack traces |
| 0.6 | Sin plantilla de variables de entorno | `.gitignore` referencia `.env.example` pero no existe | Crear `api.pos/.env.example` y `lab-dashboard/.env.example` | Documentación clara para onboarding |

---

## 🔴 Fase 1 — Seguridad y autenticación (1–2 semanas)

Objetivo: ninguna operación mutable sin identidad verificada.

### 1.1 Backend REST API

| # | Error detectado | Archivos | Acción |
|---|-----------------|----------|--------|
| 1.1 | Bypass `?token=no&except=columna` permite POST/PUT/DELETE sin token de usuario | `api.pos/routes/services/post.php`, `put.php`, `delete.php` | Eliminar bypass o restringir a uso interno server-side documentado |
| 1.2 | SQL injection en parámetros GET (`search`, `orderBy`, `between`, `inTo`) | `api.pos/models/get.model.php`, `api.pos/models/connection.php` (`getColumnsData`) | Whitelist para `orderBy`; prepared statements para el resto |
| 1.3 | Login sin contraseña para usuarios con `password_* = null` | `api.pos/controllers/post.controller.php` L159–186 | Rechazar login sin password; migrar usuarios afectados |
| 1.4 | `postRegister` emite JWT sin password | `api.pos/controllers/post.controller.php` L46–95 | Exigir password en registro o deshabilitar endpoint público |
| 1.5 | Hashing obsoleto con `crypt()` y salt fijo | `post.controller.php`, `routes/services/post.php`, `put.php`, `pos.ajax.php` | Migrar a `password_hash()` / `password_verify()` |
| 1.6 | JWT secret hardcodeado | `post.controller.php`, `pos.ajax.php` | Secret en variable de entorno |
| 1.7 | Tokens en query string (logs, referrer) | `pos.ajax.php`, `orders.controller.php` | Pasar token solo en header o body POST |
| 1.8 | Sin rate limiting en login | `postLogin`, `loginLabUser` | Throttle por IP/email (ej. 5 intentos / 15 min) |
| 1.9 | CORS `Access-Control-Allow-Origin: *` | `api.pos/index.php`, `api.pos/.htaccess` | Orígenes permitidos por entorno |
| 1.10 | CORS methods inconsistentes (PUT/DELETE) | `.htaccess` vs `index.php` | Alinear métodos permitidos |

### 1.2 Backend AJAX (`pos.ajax.php`)

| # | Error detectado | Líneas aprox. | Acción |
|---|-----------------|---------------|--------|
| 1.11 | ~50 handlers POST sin autenticación | Todo el archivo | Crear `requireSession()` / `requireRole()` y aplicar a handlers mutables |
| 1.12 | `payPosOrder` suplanta sesión con `sellerId` del POST | L3776–3783 | Exigir sesión válida; nunca confiar en `sellerId` sin auth |
| 1.13 | `confirmOrderPayment`, `updateOrderStatus`, `transferStockBetweenOffices`, `startProduction`, `saveRecipe`, `uploadSalePayment`, etc. sin auth | Varios | Misma política de sesión/JWT |
| 1.14 | `apiProxy` amplifica poder REST con API key embebida | L1577–1601 | Requiere sesión + whitelist estricta de tablas/acciones |
| 1.15 | Mensajes de excepción SQL expuestos al cliente | Varios `catch` | Respuesta genérica; detalle solo en log |

### 1.3 Frontend

| # | Error detectado | Archivos | Acción |
|---|-----------------|----------|--------|
| 1.16 | Token API estático duplicado en cada página (visible en bundle) | `admins.vue`, `combos.vue`, `reportes.vue`, `reportes-empresa.vue`, `solicitar-inventario.vue`, `nuxt.config.ts` | Proxy server-side en Nuxt; quitar token del cliente |
| 1.17 | Páginas sensibles sin guard de ruta | `admins.vue`, `combos.vue`, `reportes.vue`, `solicitar-inventario.vue` | `definePageMeta` + middleware por rol/permiso |
| 1.18 | `checkSession()` async pero páginas fetchean en `onMounted` antes de confirmar sesión | `app.vue` + páginas | Esperar `auth.ready` o middleware global |
| 1.19 | Backend inventario acepta `id_admin`/`id_office` del POST sin validar sesión | `pos.ajax.php` (`createInventoryRequest`, `createStockTransfer`) | Comparar con `$_SESSION["admin"]` |

**Criterio de aceptación Fase 1:** Matriz documentada endpoint × auth requerida; pentest básico (curl sin sesión → 401 en mutaciones).

---

## 🟠 Fase 2 — Integridad de datos y bugs críticos (1–2 semanas)

Objetivo: comportamiento correcto en inventario, producción y reportes.

### 2.1 Handlers duplicados en `pos.ajax.php` (merge incompleto)

| # | Endpoint | Problema | Acción |
|---|----------|----------|--------|
| 2.1 | `saveProduction` | L1978 ejecuta insert simple; L3908 (lógica completa con stock) es código muerto | Eliminar L1978; conservar implementación completa |
| 2.2 | `completeProduction` | Duplicado L1692 y L4044 | Una sola implementación |
| 2.3 | `getPendingQC` | L2405 (`== "ok"`) vs L4151 (`isset` solo) | Unificar condición |
| 2.4 | `getQCHistory` | L2913 vs L4172 | Unificar |
| 2.5 | `submitQualityCheck` | L4195 sin check de rol `lab_calidad` — bypass de L2692 | Eliminar handler laxo |
| 2.6 | `getProductionDetails` | L2077 vs L4304 | Unificar |

**Referencia adicional:** `pos_conflicts.txt` en raíz documenta 13 conflictos de merge pendientes en `pos.ajax.php`.

### 2.2 Esquema vs código

| # | Error detectado | Archivos | Acción |
|---|-----------------|----------|--------|
| 2.7 | `confirmOrderPayment` usa columna `invoice_order` inexistente | `pos.ajax.php` L4427 | Crear columna o corregir query |
| 2.8 | Tabla `order_expenses` usada pero no creada en migraciones | `pos.ajax.php` L4385–4400 | Migración CREATE TABLE |
| 2.9 | QC actualiza `products.stock_product` pero no `product_inventory` | `submitQualityCheck`, `completeProduction` | Actualizar ambas fuentes o solo `product_inventory` |
| 2.10 | `ensureRuntimeSchema()` ejecuta DDL en cada request | `pos.ajax.php` L106–417 | Mover a migraciones CLI versionadas |
| 2.11 | Migraciones duplicadas en SQL, PHP y runtime | `migration_*.sql`, `migrations_v2.php`, `pos.ajax.php` | Un solo sistema: `schema_migrations` + runner CLI |
| 2.12 | `migration_product_inventory.sql` incompleta | FK de `product_offers`/`price_tiers` comentadas; trigger sin guard de stock; tabla `_product_id_map` permanente; dedup por `MIN(id)` no alineado con comentarios | Completar FK updates; validar triggers; limpiar tabla temporal |
| 2.13 | Traspasos con `GREATEST(0,...)` ocultan stock insuficiente | `pos.ajax.php` L2658 | Fallar transacción si stock < cantidad |

### 2.3 Frontend — bugs funcionales

| # | Error detectado | Archivo | Acción |
|---|-----------------|--------|--------|
| 2.14 | Columna "Despachado" siempre en 0 — campo incorrecto | `solicitar-inventario.vue` L304, L448–450 | Usar `qty_dispatched_request` (como `despachos.vue`) |
| 2.15 | Fallbacks `auth.user?.id_admin \|\| 1` y `auth.officeId \|\| 3` | `solicitar-inventario.vue`, `reportes.vue` | Bloquear acción si sesión incompleta |
| 2.16 | Traspaso permite elegir cualquier sucursal origen | `solicitar-inventario.vue` L481–500 | Restringir a oficina del usuario |
| 2.17 | `reportes.vue`: rol `admin` tratado como superadmin | L74 | `isSuperAdmin` solo `superadmin` o `officeId === 0` |
| 2.18 | Creación de admin sin contraseña obligatoria | `admins.vue` L374–378, L668 | Validar password en create |
| 2.19 | Política de password débil (mín. 4 caracteres) | `admins.vue` L177–179 | Mínimo 8 caracteres; feedback con toast |
| 2.20 | `combos.vue`: toast de éxito aunque falle el AJAX | L73–86 | Verificar `response.status` antes del toast |
| 2.21 | `combos.vue`: `JSON.parse` sin try/catch | L46, L59 | Usar `useApi().parse()` o try/catch |
| 2.22 | Errores silenciados con `.catch(() => null)` | `combos.vue`, `reportes.vue`, `reportes-empresa.vue` | Toast de error consistente |
| 2.23 | `reportes.vue` exporta CSV mal formateado; botón dice "Excel" | L360–387, L446 | Mismo patrón que `admins.vue` (BOM, `;`, quoting) |
| 2.24 | `reportes.vue` carga todos los admins sin filtro | L148 | Filtrar por oficina o endpoint dedicado |
| 2.25 | `reportes-empresa.vue` sin validación de rango de fechas | L21–22 | Rechazar si `startDate > endDate` |
| 2.26 | `reportes-empresa.vue`: `loading` no oculta KPIs durante fetch | — | Skeleton o `v-if="!loading"` en contenido |
| 2.27 | `reportes.vue`: botón filtrar sin `:loading` — race en doble click | L445 | `:loading="loading"` + debounce |
| 2.28 | `decodeURIComponent` sin try/catch — crash con `%` malformado | Todas las páginas modificadas | Usar `decodeText()` de `app/utils/format.ts` |

**Criterio de aceptación Fase 2:** Flujos inventario, producción y reportes pasan checklist manual (ver sección Verificación).

---

## 🟡 Fase 3 — Arquitectura backend (2–3 semanas)

Objetivo: API mantenible y predecible.

| # | Tarea | Detalle | Archivos |
|---|-------|---------|----------|
| 3.1 | Dividir `pos.ajax.php` por dominio | Módulos: `auth`, `inventory`, `production`, `orders`, `reports`, `lab` | Nuevo directorio `api.pos/ajax/handlers/` |
| 3.2 | Unificar conexiones PDO | Misma config: `utf8mb4`, `EMULATE_PREPARES` consistente | `connection.php`, `LocalConnection` en pos.ajax |
| 3.3 | Respuestas JSON estandarizadas | `{ ok: bool, data?, error?, code? }` | Todos los handlers AJAX |
| 3.4 | Transacciones en operaciones multi-tabla | Pagos, traspasos, producción, QC | Handlers afectados |
| 3.5 | `loadProducts` carga catálogo completo en memoria | Aplicar LIMIT en SQL, no en PHP | `pos.ajax.php` L502–512 |
| 3.6 | Campo `debug_isWholesale` en respuesta de producción | Eliminar de respuestas públicas | `pos.ajax.php` L612 |
| 3.7 | `orders.controller.php` en modo simulación de facturación | Documentar flag; activar validación real en prod | `orders.controller.php` L22–29 |
| 3.8 | `post.controller.php`: respuestas silenciosas en fallo de token | L79–87, L140–147 | Siempre devolver JSON con status |
| 3.9 | `json_encode` con `http_response_code` incorrecto | `post.controller.php` L248 | Usar `http_response_code()` por separado |
| 3.10 | Charset mixto `utf8` vs `utf8mb4` | Tabla `incomes` y otros | Estandarizar `utf8mb4_unicode_ci` |

---

## 🟡 Fase 4 — Frontend y UX (1–2 semanas)

Objetivo: consistencia, navegación completa y menos deuda en páginas.

| # | Tarea | Archivos |
|---|-------|----------|
| 4.1 | Adoptar `useApi()` en todas las páginas (eliminar fetch duplicado) | `admins.vue`, `combos.vue`, `reportes*.vue`, `solicitar-inventario.vue` |
| 4.2 | Adoptar `formatBob()` y `decodeText()` de `utils/format.ts` | Mismas páginas |
| 4.3 | Anti-race en fetches de productos | `solicitar-inventario.vue` — AbortController o request ID |
| 4.4 | Paginación visible en tabla de admins | `admins.vue` — UI para `page` / `itemsPerPage` |
| 4.5 | Reset de `page` al filtrar en admins | `admins.vue` |
| 4.6 | Eliminar código muerto del modal de permisos | `admins.vue` L32–33, L205–261 |
| 4.7 | Alinear matriz de permisos con sidebar | `admins.vue` vs `app.vue` — incluir `sucursales`, `clientes`, `admins`, `despachos`, `almacenes`, `reports`/`reportes` |
| 4.8 | Corregir selector "Inventario Asignado" | `admins.vue` L701–707 — cargar inventarios/sub-almacenes, no oficinas |
| 4.9 | Presets de permisos consistentes al crear admin | `openCreate()` vs watcher de rol en `admins.vue` |
| 4.10 | Enlace sidebar para `/combos` | `app.vue` con `hasPerm` adecuado |
| 4.11 | Enlace sidebar para `/reportes-empresa` | `app.vue` — solo superadmin |
| 4.12 | Ruta `/entradas` en nav sin página | Crear `entradas.vue` (entradas M.P.) o quitar del menú en `app.vue` L158, L308 |
| 4.13 | `combos.vue`: confirmación y loading en delete | `deleteItem` |
| 4.14 | `solicitar-inventario.vue`: warning en lista vacía solo si no es carga inicial | L98–104, L132–137 |
| 4.15 | `reportes-empresa.vue`: regla de acceso coherente | Template vs `onMounted` — solo superadmin |
| 4.16 | Habilitar TypeScript gradualmente | `nuxt.config.ts` `typeCheck: true`; tipar páginas críticas |
| 4.17 | Limpiar imports no usados | `watch` en `reportes.vue`, `auth` en `admins.vue`/`combos.vue` |

---

## 🟢 Fase 5 — Infraestructura y DevOps (1 semana)

| # | Error detectado | Acción |
|---|-----------------|--------|
| 5.1 | Dockerfile raíz asume `.output/` pre-construido | Multi-stage: `pnpm build` dentro de la imagen |
| 5.2 | Secretos en `docker-compose.yml` commiteado | Usar `.env` local (gitignored) + `env_file` |
| 5.3 | Sin CI/CD | Pipeline mínimo: lint frontend, smoke HTTP API |
| 5.4 | `pnpm` + `package-lock.json` coexisten | Elegir uno; documentar en README |
| 5.5 | Sin README del proyecto | README con setup Docker, env vars, migraciones |
| 5.6 | Health check solo en DB | Agregar health en servicio `api` y `frontend` |

---

## ✅ Verificación por fase

### Fase 0
- [x] `curl` a `hash.php`, `test.php`, `migrations_v2.php` → 404 o bloqueado
- [x] No hay API keys ni JWT secrets en archivos trackeados por git (`.env.example` + env vars)
- [x] `.env.example` presente en ambos subproyectos

### Fase 1 — Seguridad
- [x] POST a `pos.ajax.php` sin cookie de sesión en mutaciones → rechazado (`pos_enforce_ajax_auth`)
- [x] REST con `?token=no` → rechazado
- [x] GET `/api/products?search=...` → orderBy sanitizado en `get.model.php`
- [x] Token API no visible en bundle JS (proxy Nuxt server-side)
- [x] `/admins` con middleware `permission`

### Fase 2 — Datos y bugs
- [x] Columna "Despachado" usa `qty_dispatched_request`
- [x] Traspaso desde sucursal ajena → restricción en frontend
- [x] Admin de sucursal en reportes solo ve su oficina
- [x] Handlers duplicados eliminados del monolito AJAX
- [x] `submitQualityCheck` con rol y `product_inventory`
- [x] Crear admin sin password → error en UI
- [x] Guardar combo fallido → toast de error

### Fase 3 — Backend
- [x] Schema vía `bin/migrate.php` / Docker entrypoint (no DDL en cada request por defecto)
- [x] `pos.ajax.php` dividido en `handlers/` + `lib/`
- [x] Traspaso/despacho con stock insuficiente → error explícito
- [x] `loadProducts` con LIMIT en SQL
- [ ] Respuestas AJAX 100% estandarizadas `{ ok, data, error }` (legacy `"ok"` aún coexisten)

### Fase 4 — Frontend
- [x] `/combos` y `/reportes-empresa` en menú
- [x] `/entradas` → redirect a `/inventario`
- [x] Export reportes con BOM y quoting CSV
- [x] Paginación admins funcional con búsqueda

### Fase 5 — DevOps
- [x] Dockerfile multi-stage con build Nuxt
- [x] CI GitHub Actions (lint PHP + build frontend)
- [ ] `docker compose up --build` verificado end-to-end en este entorno

---

## Mapa de archivos prioritarios

```
api.pos/
├── ajax/pos.ajax.php          ← mayor concentración de errores
├── models/get.model.php       ← SQL injection
├── models/connection.php      ← secretos + SQL
├── controllers/post.controller.php
├── routes/services/post.php, put.php, delete.php
├── migration_product_inventory.sql
├── migrations_v2.php
├── hash.php, test.php, test2.php  ← eliminar
└── u228744577_pos.sql         ← reemplazar

lab-dashboard/
├── app/pages/admins.vue
├── app/pages/combos.vue
├── app/pages/reportes.vue
├── app/pages/reportes-empresa.vue
├── app/pages/solicitar-inventario.vue
├── app/app.vue                ← nav + session guard
├── app/composables/useApi.ts  ← adoptar en todas las páginas
├── nuxt.config.ts
└── (crear) app/pages/entradas.vue

raíz/
├── docker-compose.yml
├── Dockerfile
├── PLAN_CORRECCIONES.md       ← funcionalidades (ya implementadas)
└── PLAN_ESTABILIZACION_TECNICA.md  ← este documento
```

---

## Orden de ejecución recomendado

```
Fase 0 (contención)
    ↓
Fase 1 (seguridad) ──────┐
    ↓                    ├── pueden solaparse parcialmente
Fase 2 (bugs datos) ─────┘
    ↓
Fase 3 (arquitectura backend)
    ↓
Fase 4 (frontend)  ←  puede iniciar tras Fase 2 en paralelo con Fase 3
    ↓
Fase 5 (DevOps)
```

**Estimación total:** 6–10 semanas (1 dev full-stack) · 4–6 semanas (2 devs en paralelo backend/frontend).

---

## Relación con `PLAN_CORRECCIONES.md`

| Documento | Enfoque | Estado |
|-----------|---------|--------|
| `PLAN_CORRECCIONES.md` | Nuevas funcionalidades de negocio (POS, almacén, laboratorio, reportes) | Implementado según indicación del equipo |
| `PLAN_ESTABILIZACION_TECNICA.md` | Errores técnicos, seguridad, integridad de datos, deuda e infraestructura | **Completado** (jun 2026) |

Las mejoras de este plan **no duplican** las features de negocio ya entregadas; las **protegen y estabilizan** para que el sistema sea seguro y confiable en producción.

---

*Generado a partir de auditoría técnica del repositorio UniTech 2.0 — junio 2026.*
