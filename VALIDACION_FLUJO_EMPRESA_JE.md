# VALIDACIÓN Y AJUSTES: FLUJO PARA EMPRESA JE (PRODUCTOS NATURALES)

## CONTEXTO DE LA EMPRESA

**Empresa**: JE - Tienda de Productos Naturales

**Modelo de Negocio**:
- Venta de productos simples (comprados y revendidos)
- Producción propia de productos compuestos (elaborados con materia prima)
- Gestión de almacén central
- Múltiples sucursales
- **Datos sensibles**: Costos de producción y materia prima (solo rol específico)

---

## ANÁLISIS DEL FLUJO ACTUAL vs REQUERIMIENTOS

### ✅ CUMPLE CORRECTAMENTE

1. ✅ Productos simples (compra y venta con margen)
2. ✅ Productos compuestos basados en materia prima
3. ✅ Producción que consume materia prima
4. ✅ Almacén central
5. ✅ Salidas de almacén a sucursales
6. ✅ Estados en compras (pendiente/recibido)

### ❌ FALTANTES CRÍTICOS

1. ❌ **Costo de mano de obra en producción** - NO incluido
2. ❌ **Separación de roles y permisos** - NO detallado
3. ❌ **Dos inventarios separados** - NO especificado
4. ❌ **Precio de venta definido en almacén** - Flujo no claro
5. ❌ **Estado "recibido" vs "confirmado"** - Terminología diferente

---

## AJUSTES NECESARIOS

---

## 1. COSTO DE MANO DE OBRA EN PRODUCCIÓN

### 1.1 Cambios en Base de Datos

**Tabla: `productions`** (MODIFICAR)
- **Nuevo campo**: `labor_cost_production` (DOUBLE NULL DEFAULT '0')
  - Costo de mano de obra para esta producción
- **Nuevo campo**: `total_cost_production` (DOUBLE NULL DEFAULT '0')
  - Costo total = Costo materia prima + Mano de obra
- **Campo existente**: `cost_production` → Renombrar a `materials_cost_production`
  - Costo solo de materia prima

**Nueva Tabla: `production_labor_costs`** (Opcional - para historial)
- `id_production_labor_cost` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_production_labor_cost_production` (INT) - FK a productions
- `labor_cost` (DOUBLE)
- `hours_worked` (DOUBLE) - horas trabajadas (opcional)
- `cost_per_hour` (DOUBLE) - costo por hora (opcional)
- `date_created_labor_cost` (DATE)

### 1.2 Cambios en Controladores

**Archivo**: `controllers/productions.controller.php`
- **Método `createProduction()`**: 
  - Agregar campo de costo de mano de obra
  - Calcular: `total_cost = materials_cost + labor_cost`
- **Método `completeProduction()`**: 
  - Calcular costo unitario: `total_cost / qty_production`
  - Guardar en almacén con costo total (materia prima + mano de obra)

**Archivo**: `ajax/productions.ajax.php`
- **Método `calculateTotalCost()`**: 
  - Calcular costo materia prima
  - Sumar costo mano de obra
  - Retornar costo total y costo unitario

### 1.3 Cambios en Vistas

**Módulo de Producción**:
- Agregar campo "Costo de Mano de Obra" (Bs)
- Campo opcional: "Horas Trabajadas" y "Costo por Hora" (para cálculo automático)
- Mostrar desglose:
  - Costo Materia Prima: Bs X
  - Costo Mano de Obra: Bs Y
  - **Costo Total Producción: Bs Z**
  - Costo Unitario: Bs Z / cantidad

### 1.4 Flujo Ajustado

1. Crear producción
2. Sistema calcula costo de materia prima (automático)
3. Usuario ingresa costo de mano de obra (manual o calculado)
4. Sistema calcula: **Costo Total = Materia Prima + Mano de Obra**
5. Al completar producción:
   - Costo unitario = Costo Total / Cantidad producida
   - Se guarda en almacén con costo total

---

## 2. SEPARACIÓN DE ROLES Y PERMISOS

### 2.1 Nuevos Roles Necesarios

**Rol 1: "Inventario y Compras"** (Rol estándar)
- ✅ Ver y gestionar productos simples
- ✅ Registrar compras de productos simples
- ✅ Ver stock de productos simples
- ✅ Gestionar clientes
- ✅ Usar POS para ventas
- ✅ Ver reportes de ventas
- ❌ NO puede ver costos de producción
- ❌ NO puede ver materia prima
- ❌ NO puede ver costos de productos compuestos
- ❌ NO puede acceder a módulo de producción
- ❌ NO puede acceder a módulo de almacén (solo ver salidas recibidas)

**Rol 2: "Laboratorio/Producción"** (Rol privilegiado)
- ✅ Gestionar materia prima
- ✅ Registrar compras de materia prima
- ✅ Crear productos compuestos
- ✅ Gestionar producciones
- ✅ Ver costos de producción
- ✅ Gestionar almacén
- ✅ Definir precios de venta en almacén
- ✅ Hacer salidas a sucursales
- ❌ NO puede ver costos en módulo de ventas (solo precios de venta)
- ❌ Puede ver todo pero con restricciones en ventas

**Rol 3: "Superadmin"** (Existente)
- Acceso completo a todo

### 2.2 Cambios en Base de Datos

**Tabla: `admins`** (MODIFICAR)
- **Campo existente**: `rol_admin` (TEXT)
  - Valores actuales: 'superadmin', 'admin'
  - **Nuevos valores**: 'superadmin', 'inventory', 'laboratory', 'admin'
    - 'inventory' = Inventario y Compras
    - 'laboratory' = Laboratorio/Producción
    - 'admin' = Administrador de Sucursal (actual)

**Nueva Tabla: `role_permissions`** (Opcional - para permisos granulares)
- `id_role_permission` (INT AUTO_INCREMENT PRIMARY KEY)
- `rol_permission` (TEXT)
- `module_permission` (TEXT)
- `action_permission` (TEXT) - 'view', 'create', 'edit', 'delete'
- `can_see_costs_permission` (INT DEFAULT '0') - 1 = puede ver costos
- `date_created_permission` (DATE)

### 2.3 Cambios en Controladores

**Archivo**: `controllers/template.controller.php`
- **Nuevo método `checkPermission()`**: 
  - Verificar permisos según rol
  - Bloquear acceso a módulos no permitidos
- **Nuevo método `canSeeCosts()`**: 
  - Verificar si rol puede ver costos sensibles
  - Retornar true/false

**Archivo**: `controllers/productions.controller.php`
- Agregar validación de rol al inicio de cada método
- Solo rol 'laboratory' o 'superadmin' puede acceder

**Archivo**: `controllers/raw_materials.controller.php`
- Agregar validación de rol
- Solo rol 'laboratory' o 'superadmin' puede acceder

**Archivo**: `controllers/warehouse.controller.php`
- Agregar validación de rol
- Rol 'inventory' solo puede ver, no editar costos
- Rol 'laboratory' puede gestionar todo

**Archivo**: `ajax/pos.ajax.php`
- **Modificar `loadProducts()`**: 
  - Si rol no puede ver costos: no enviar campo `price_purchase`
  - Solo enviar `price_sale` o precio de venta

**Archivo**: `controllers/orders.controller.php`
- Ocultar costos en reportes si rol no tiene permiso

### 2.4 Cambios en Vistas

**Template Principal** (`views/template.php`):
- Filtrar menú según rol
- Ocultar módulos no permitidos:
  - Rol 'inventory': NO muestra "Producción", "Almacén", "Materia Prima"
  - Rol 'laboratory': Muestra todos los módulos

**Módulo de Productos**:
- Si rol no puede ver costos:
  - Ocultar campo "Precio de Compra"
  - Ocultar campo "Costo Calculado" (productos compuestos)
  - Solo mostrar "Precio de Venta"

**Módulo de Producción**:
- Solo visible para rol 'laboratory' y 'superadmin'
- Mostrar todos los costos

**Módulo de Almacén**:
- Rol 'inventory': Solo lectura, sin ver costos
- Rol 'laboratory': Gestión completa con costos

**POS**:
- No mostrar costos, solo precios de venta
- No mostrar información de producción

**Reportes**:
- Filtrar columnas según permisos
- Ocultar columnas de costo si no tiene permiso

### 2.5 Flujo de Permisos

1. Usuario inicia sesión
2. Sistema identifica rol
3. Menú se filtra según rol
4. Al acceder a módulo:
   - Sistema verifica permisos
   - Si no tiene permiso: redirigir o mostrar error
5. En consultas de datos:
   - Si no puede ver costos: no incluir campos de costo
   - Solo mostrar precios de venta

---

## 3. DOS INVENTARIOS SEPARADOS

### 3.1 Inventario 1: Productos Simples (Rol: Inventario)

**Tabla: `products`** (MODIFICAR)
- **Nuevo campo**: `inventory_type_product` (TEXT NULL DEFAULT 'simple')
  - Valores: 'simple', 'compound'
  - 'simple' = Inventario normal (rol inventario)
  - 'compound' = Inventario producción (rol laboratorio)

**Gestión**:
- Rol 'inventory' solo ve y gestiona productos con `inventory_type_product = 'simple'`
- Rol 'laboratory' ve todos los productos pero gestiona principalmente compuestos

### 3.2 Inventario 2: Materia Prima (Rol: Laboratorio)

**Tabla: `raw_materials`** (Ya definida)
- Solo visible para rol 'laboratory' y 'superadmin'
- Gestión independiente del inventario de productos simples

### 3.3 Separación Visual

**Módulo de Inventario (Rol: Inventory)**:
- Solo muestra productos simples
- Solo muestra compras de productos simples
- No muestra materia prima
- No muestra producciones

**Módulo de Laboratorio (Rol: Laboratory)**:
- Muestra materia prima
- Muestra compras de materia prima
- Muestra producciones
- Muestra productos compuestos
- Puede ver productos simples pero con enfoque en compuestos

### 3.4 Flujo Separado

**Flujo Inventario (Productos Simples)**:
1. Registrar compra de producto simple
2. Estado: Pendiente
3. Cuando llega físicamente: Cambiar a Recibido
4. Stock se actualiza
5. Producto disponible para venta en POS

**Flujo Laboratorio (Productos Compuestos)**:
1. Registrar compra de materia prima
2. Stock de materia prima se actualiza
3. Crear producto compuesto (define materia prima necesaria)
4. Crear producción
5. Al completar: pasa a almacén
6. En almacén: definir precio de venta
7. Hacer salida a sucursal
8. En sucursal: llega como compra pendiente
9. Cuando llega físicamente: cambiar a Recibido
10. Stock se actualiza en sucursal

---

## 4. PRECIO DE VENTA DEFINIDO EN ALMACÉN

### 4.1 Cambios en Base de Datos

**Tabla: `warehouse`** (MODIFICAR)
- **Campo existente**: `sale_price_warehouse` (ya existe)
- **Nuevo campo**: `price_defined_warehouse` (INT DEFAULT '0')
  - 0 = precio no definido
  - 1 = precio definido (listo para salida)

### 4.2 Cambios en Controladores

**Archivo**: `controllers/warehouse.controller.php`
- **Método `defineSalePrice()`**: 
  - Definir precio de venta del producto en almacén
  - Marcar `price_defined_warehouse = 1`
  - Validar que precio sea mayor al costo
- **Método `outputFromWarehouse()`**: 
  - Validar que precio esté definido antes de permitir salida
  - Al crear salida, usar precio definido en almacén

### 4.3 Cambios en Vistas

**Módulo de Almacén**:
- Mostrar productos con precio definido y sin definir
- Botón "Definir Precio de Venta" en productos sin precio
- Formulario para definir precio:
  - Mostrar costo unitario (solo rol laboratory)
  - Campo para precio de venta
  - Calcular margen de ganancia (opcional)
- Indicador visual: ✅ Precio definido / ⚠️ Pendiente definir precio
- No permitir salida si precio no está definido

### 4.4 Flujo Ajustado

1. Producción completada → Producto llega a almacén
2. Estado: Precio NO definido
3. Rol 'laboratory' define precio de venta:
   - Ver costo unitario
   - Ingresar precio de venta
   - Sistema valida que precio > costo
   - Marcar como "Precio Definido"
4. Ahora se puede hacer salida a sucursal
5. Al hacer salida:
   - Se usa precio definido en almacén
   - Se crea compra en sucursal con ese precio
   - Estado: Pendiente

---

## 5. ESTADO "RECIBIDO" EN COMPRAS

### 5.1 Cambios en Base de Datos

**Tabla: `purchases`** (MODIFICAR)
- **Campo existente**: `status_purchase` (modificar valores)
  - Valores: 'pendiente', 'recibido' (en lugar de 'confirmado')
- **Campo existente**: `confirmed_date_purchase` → Renombrar a `received_date_purchase`
- **Campo existente**: `confirmed_by_purchase` → Renombrar a `received_by_purchase`

### 5.2 Cambios en Controladores

**Archivo**: `controllers/purchases.controller.php`
- **Método `receivePurchase()`**: (en lugar de confirmPurchase)
  - Cambiar estado a "recibido"
  - Actualizar stock
  - Registrar fecha y usuario que recibió

**Archivo**: `ajax/stock.ajax.php`
- **Modificar `updateStock()`**: 
  - Solo contar compras con estado "recibido"
  - Ignorar compras "pendientes"

### 5.3 Cambios en Vistas

**Módulo de Compras**:
- Cambiar terminología: "Confirmar" → "Marcar como Recibido"
- Badge: "Pendiente" (amarillo), "Recibido" (verde)
- Botón: "Marcar como Recibido"
- Al recibir:
  - Cambiar estado
  - Actualizar stock
  - Registrar fecha de recepción

### 5.4 Flujo Ajustado

1. Registrar compra → Estado: "Pendiente"
2. Stock NO se actualiza
3. Cuando producto llega físicamente a sucursal
4. Usuario marca como "Recibido"
5. Estado cambia a "Recibido"
6. Stock se actualiza automáticamente
7. Se registra fecha y usuario que recibió

---

## 6. FLUJO COMPLETO AJUSTADO PARA EMPRESA JE

### Flujo 1: Producto Simple (Rol: Inventario)

1. **Compra de Producto Simple**:
   - Rol 'inventory' registra compra
   - Estado: "Pendiente"
   - Precio de compra: X
   - Precio de venta: Y (definido al crear producto)

2. **Recepción Física**:
   - Producto llega a sucursal
   - Rol 'inventory' marca como "Recibido"
   - Stock se actualiza

3. **Venta**:
   - Disponible en POS
   - Se vende con margen: Y - X

---

### Flujo 2: Producto Compuesto - Producción (Rol: Laboratorio)

1. **Compra de Materia Prima**:
   - Rol 'laboratory' registra compra de materia prima
   - Estado: "Pendiente"
   - Cuando llega: marca como "Recibido"
   - Stock de materia prima se actualiza

2. **Crear Producto Compuesto**:
   - Rol 'laboratory' crea producto
   - Tipo: "Compuesto"
   - Define componentes:
     - Materia Prima A: cantidad X
     - Materia Prima B: cantidad Y
   - Sistema calcula costo materia prima (automático)

3. **Crear Producción**:
   - Rol 'laboratory' crea producción
   - Producto: Producto Compuesto
   - Cantidad a producir: Z unidades
   - Fecha inicial: hoy
   - Fecha estimada: fecha futura
   - Estado: "Pendiente"

4. **Iniciar Producción**:
   - Cambiar estado a "En Proceso"
   - Sistema verifica stock de materia prima necesario

5. **Completar Producción**:
   - Rol 'laboratory' completa producción
   - Sistema calcula:
     - **Costo Materia Prima**: Suma de (cantidad_componente × costo_materia_prima)
     - **Costo Mano de Obra**: Ingresado manualmente
     - **Costo Total**: Materia Prima + Mano de Obra
     - **Costo Unitario**: Costo Total / Cantidad producida
   - Sistema descuenta materia prima del stock
   - Estado: "Completada"
   - **Producto pasa a Almacén**

6. **Definir Precio en Almacén**:
   - Rol 'laboratory' ve producto en almacén
   - Ve costo unitario (solo este rol puede verlo)
   - Define precio de venta
   - Sistema valida: precio > costo
   - Marca como "Precio Definido"

7. **Salida a Sucursal**:
   - Rol 'laboratory' hace salida
   - Selecciona sucursal destino
   - Cantidad a enviar
   - Sistema:
     - Crea registro en `warehouse_outputs`
     - Crea compra automática en sucursal con precio definido
     - Estado compra: "Pendiente"
     - Actualiza stock en almacén (resta)
     - Stock en sucursal NO se actualiza aún

8. **Recepción en Sucursal**:
   - Producto llega físicamente a sucursal
   - Rol 'inventory' (o cualquier rol de sucursal) marca compra como "Recibido"
   - Estado: "Recibido"
   - Stock en sucursal se actualiza
   - Producto disponible para venta en POS

9. **Venta en POS**:
   - Producto aparece en POS con precio de venta
   - Se vende normalmente
   - Rol 'inventory' NO ve costos, solo precio de venta

---

## 7. RESUMEN DE AJUSTES CRÍTICOS

### ✅ Agregados al Análisis Original:

1. ✅ **Costo de Mano de Obra**:
   - Campo `labor_cost_production` en tabla `productions`
   - Cálculo: Costo Total = Materia Prima + Mano de Obra
   - Costo unitario = Costo Total / Cantidad

2. ✅ **Separación de Roles**:
   - Rol 'inventory': Solo productos simples, sin ver costos
   - Rol 'laboratory': Producción, materia prima, costos sensibles
   - Permisos granulares por módulo

3. ✅ **Dos Inventarios Separados**:
   - Inventario productos simples (rol inventory)
   - Inventario materia prima (rol laboratory)
   - Separación visual y funcional

4. ✅ **Precio de Venta en Almacén**:
   - Definir precio antes de hacer salida
   - Validación: precio > costo
   - Precio se usa al crear compra en sucursal

5. ✅ **Estado "Recibido"**:
   - Terminología correcta
   - Flujo claro: Pendiente → Recibido

---

## 8. CAMBIOS ADICIONALES EN BASE DE DATOS

### Tablas a Modificar:

**`productions`**:
```sql
ALTER TABLE productions 
ADD COLUMN labor_cost_production DOUBLE NULL DEFAULT '0',
ADD COLUMN total_cost_production DOUBLE NULL DEFAULT '0',
CHANGE COLUMN cost_production materials_cost_production DOUBLE NULL DEFAULT '0';
```

**`warehouse`**:
```sql
ALTER TABLE warehouse 
ADD COLUMN price_defined_warehouse INT NULL DEFAULT '0';
```

**`purchases`**:
```sql
ALTER TABLE purchases 
CHANGE COLUMN status_purchase status_purchase TEXT NULL DEFAULT 'pendiente',
CHANGE COLUMN confirmed_date_purchase received_date_purchase DATE NULL,
CHANGE COLUMN confirmed_by_purchase received_by_purchase INT NULL;
```

**`admins`**:
```sql
-- El campo rol_admin ya existe, solo actualizar valores permitidos
-- Valores: 'superadmin', 'inventory', 'laboratory', 'admin'
```

**`products`**:
```sql
ALTER TABLE products 
ADD COLUMN inventory_type_product TEXT NULL DEFAULT 'simple';
```

---

## 9. VALIDACIONES Y REGLAS DE NEGOCIO

### Validaciones Críticas:

1. **Producción**:
   - ✅ Verificar stock de materia prima antes de completar
   - ✅ Costo mano de obra >= 0
   - ✅ Costo total = materia prima + mano de obra

2. **Almacén**:
   - ✅ Precio de venta > costo unitario
   - ✅ No permitir salida sin precio definido
   - ✅ Verificar stock disponible antes de salida

3. **Compras**:
   - ✅ No actualizar stock hasta que esté "Recibido"
   - ✅ Solo rol correspondiente puede recibir

4. **Permisos**:
   - ✅ Rol 'inventory' NO puede ver costos
   - ✅ Rol 'laboratory' puede ver todos los costos
   - ✅ Validar permisos en cada acción

---

## 10. PRIORIDADES DE IMPLEMENTACIÓN AJUSTADAS

### Fase 1 (Fundamentos y Separación):
1. Separación de roles y permisos
2. Dos inventarios separados
3. Estados en compras (pendiente/recibido)

### Fase 2 (Producción Completa):
1. Materia prima y compras
2. Productos compuestos
3. Producción con costo de mano de obra
4. Cálculo de costos totales

### Fase 3 (Almacén y Distribución):
1. Almacén con definición de precio
2. Salidas a sucursales
3. Integración con compras

### Fase 4 (Refinamiento):
1. Validaciones y reglas de negocio
2. Reportes por rol
3. Auditoría de costos

---

## CONCLUSIÓN

El análisis original cubre la mayoría de los requerimientos, pero necesita estos ajustes críticos:

1. ✅ **Costo de mano de obra** (FALTABA)
2. ✅ **Separación de roles** (NO estaba detallado)
3. ✅ **Dos inventarios** (NO estaba claro)
4. ✅ **Precio en almacén** (Flujo mejorado)
5. ✅ **Estado "Recibido"** (Terminología corregida)

Con estos ajustes, el sistema cumple completamente con el flujo de la empresa JE.

