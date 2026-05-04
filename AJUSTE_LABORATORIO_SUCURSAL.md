# AJUSTE: LABORATORIO COMO SUCURSAL ESPECIAL

## COMPRENSIÓN CORREGIDA DEL MODELO

### Estructura de la Empresa JE:

1. **Laboratorio** = Sucursal especial (tipo de oficina)
   - Donde se fabrican productos compuestos
   - Maneja materia prima
   - Realiza producciones
   - Datos sensibles (costos)

2. **Almacén Central** = Depósito intermedio
   - Recibe productos del laboratorio
   - Define precios de venta
   - Distribuye a sucursales

3. **Sucursales Normales** = Puntos de venta
   - Reciben productos como compras simples
   - Ven productos como simples (no saben que son compuestos)
   - Solo ven precio de venta (no costos)

---

## CAMBIOS CRÍTICOS EN EL MODELO

---

## 1. LABORATORIO COMO SUCURSAL

### 1.1 Cambios en Base de Datos

**Tabla: `offices`** (MODIFICAR)
- **Nuevo campo**: `type_office` (TEXT NULL DEFAULT 'sucursal')
  - Valores: 'sucursal', 'laboratorio', 'almacen'
  - 'sucursal' = Sucursal normal de venta
  - 'laboratorio' = Sucursal especial de producción
  - 'almacen' = Almacén central (opcional, puede ser virtual)

**Tabla: `products`** (MODIFICAR)
- **Nuevo campo**: `is_compound_product` (INT NULL DEFAULT '0')
  - 0 = producto simple
  - 1 = producto compuesto (solo existe en laboratorio)
- **Nuevo campo**: `origin_office_product` (INT NULL DEFAULT '0')
  - FK a offices
  - Si es compuesto: siempre apunta al laboratorio
  - Si es simple: puede ser cualquier sucursal

**Tabla: `warehouse`** (MODIFICAR concepto)
- El almacén NO es una sucursal física, es un registro de productos listos para distribuir
- **Nuevo campo**: `id_laboratory_warehouse` (INT)
  - FK a offices (laboratorio que produjo)
- **Nuevo campo**: `price_defined_warehouse` (INT NULL DEFAULT '0')
  - 0 = precio no definido, 1 = precio definido

### 1.2 Concepto Clave: Inventario Compartido con Vista Limitada

**IMPORTANTE**: NO se crean productos simples equivalentes. El mismo producto se comparte entre laboratorio y sucursales, pero con diferentes vistas según el rol.

**En Laboratorio**:
- Producto se crea como **COMPUESTO**
- Tiene componentes (materia prima)
- Tiene costo de producción
- Ve toda la información completa
- Puede ver y gestionar materia prima

**En Sucursales**:
- **Mismo producto físico**, pero con **vista limitada**
- **NO ve materia prima** (componentes ocultos)
- Solo ve información básica:
  - ✅ Nombre
  - ✅ Imagen
  - ✅ Categoría
  - ✅ SKU
  - ✅ Medida
  - ✅ Stock
  - ✅ Descuento en %
  - ✅ Estado
  - ✅ Sucursal
  - ✅ Código de Barras
- Solo ve precio de venta (no costos)
- No ve costos de producción
- No sabe que es un producto compuesto

**Relación**:
- Un producto compuesto (laboratorio) = Mismo producto en sucursales (vista limitada)
- **NO se crean productos equivalentes** - esto evitaría duplicación incorrecta
- El sistema filtra la información mostrada según el rol/sucursal

---

## 2. FLUJO COMPLETO CORREGIDO

### Flujo: Producto Compuesto desde Laboratorio hasta Venta

#### FASE 1: LABORATORIO (Producción)

1. **Crear Laboratorio como Sucursal**:
   - Crear oficina con `type_office = 'laboratorio'`
   - Asignar rol 'laboratory' a usuarios del laboratorio

2. **Compra de Materia Prima al Laboratorio**:
   - Rol 'laboratory' registra compra de materia prima
   - `id_office_raw_material_purchase = id_laboratorio`
   - Estado: "Pendiente"
   - Cuando llega físicamente: marca "Recibido"
   - Stock de materia prima se actualiza en laboratorio

3. **Crear Producto Compuesto en Laboratorio**:
   - Rol 'laboratory' crea producto
   - `is_compound_product = 1`
   - `origin_office_product = id_laboratorio`
   - Define componentes (materia prima + cantidades)
   - Sistema calcula costo materia prima
   - **Este producto solo existe en laboratorio**

4. **Crear Producción en Laboratorio**:
   - Rol 'laboratory' crea producción
   - `id_office_production = id_laboratorio`
   - Producto: Producto Compuesto
   - Cantidad: Z unidades
   - Ingresa costo mano de obra
   - Estado: "Pendiente"

5. **Completar Producción**:
   - Cambiar estado a "Completada"
   - Sistema calcula:
     - Costo Materia Prima
     - Costo Mano de Obra
     - Costo Total
     - Costo Unitario
   - Sistema descuenta materia prima del stock del laboratorio
   - **Producto pasa a Almacén**

#### FASE 2: ALMACÉN (Distribución)

6. **Producto en Almacén**:
   - Registro en tabla `warehouse`
   - `id_laboratory_warehouse = id_laboratorio`
   - `id_product_warehouse = id_producto_compuesto` (mismo producto, NO equivalente)
   - Cantidad: Z unidades
   - Costo unitario: calculado
   - Precio de venta: **PENDIENTE** (no definido aún)

7. **Definir Precio de Venta**:
   - Rol 'laboratory' define precio en almacén
   - Precio se asigna al producto (se guarda en campo de precio de venta del producto)
   - Marca `price_defined_warehouse = 1`
   - Ahora listo para salida
   - **NO se crea producto equivalente** - se usa el mismo producto

#### FASE 3: SALIDA A SUCURSAL

9. **Hacer Salida de Almacén**:
   - Rol 'laboratory' hace salida
   - Selecciona sucursal destino
   - Cantidad a enviar
   - Sistema:
     - Crea registro en `warehouse_outputs`
     - **Crea ingreso automático en sucursal** (registrado como compra/ingreso):
       - `id_office_purchase = id_sucursal_destino`
       - `id_product_purchase = id_producto_compuesto` (mismo producto, NO equivalente)
       - `price_purchase = precio_definido_en_almacen`
       - `qty_purchase = cantidad_enviada`
       - Estado: "Pendiente"
       - **Se registra como un nuevo ingreso de producción** con precio definido por almacén
     - Actualiza stock en almacén (resta)
     - Stock en sucursal NO se actualiza aún

10. **Recepción en Sucursal**:
    - Producto llega físicamente a sucursal
    - Rol de sucursal marca ingreso como "Recibido"
    - Estado: "Recibido"
    - Stock en sucursal se actualiza
    - **Producto aparece en inventario con vista limitada** (sin materia prima)

#### FASE 4: VENTA EN SUCURSAL

11. **Venta en POS**:
    - Producto aparece en POS de la sucursal
    - Se ve como producto simple
    - Solo muestra precio de venta
    - No muestra costos
    - No muestra que es compuesto
    - Se vende normalmente

---

## 3. CAMBIOS EN BASE DE DATOS DETALLADOS

### 3.1 Tabla: `offices`

```sql
ALTER TABLE offices 
ADD COLUMN type_office TEXT NULL DEFAULT 'sucursal';
-- Valores: 'sucursal', 'laboratorio', 'almacen'
```

### 3.2 Tabla: `products`

```sql
ALTER TABLE products 
ADD COLUMN is_compound_product INT NULL DEFAULT '0',
ADD COLUMN origin_office_product INT NULL DEFAULT '0';
-- is_compound_product: 0 = simple, 1 = compuesto
-- origin_office_product: FK a offices (laboratorio si es compuesto)
```

### 3.3 Tabla: `warehouse`

```sql
ALTER TABLE warehouse 
ADD COLUMN id_laboratory_warehouse INT NULL,
ADD COLUMN price_defined_warehouse INT NULL DEFAULT '0';
-- id_laboratory_warehouse: FK a offices (laboratorio que produjo)
-- price_defined_warehouse: 0 = no definido, 1 = definido
```

**NOTA**: NO se crea tabla `product_equivalents` porque NO se crean productos equivalentes. El mismo producto se comparte con vista limitada.

### 3.4 Tabla: `raw_materials`

```sql
-- Asegurar que tiene id_office_raw_material
-- El laboratorio es una oficina, así que materia prima pertenece al laboratorio
```

### 3.6 Tabla: `productions`

```sql
-- Ya tiene id_office_production
-- Este campo apunta al laboratorio (oficina tipo 'laboratorio')
```

---

## 4. CAMBIOS EN CONTROLADORES

### 4.1 Archivo: `controllers/products.controller.php`

**Método `createProduct()`**:
- Si `is_compound_product = 1`:
  - Validar que `origin_office_product` sea un laboratorio
  - Validar que tenga componentes
- Si `is_compound_product = 0`:
  - No requiere componentes

**Método `getProductView()`** (nuevo):
- Retornar vista del producto según rol/sucursal
- Si es laboratorio: vista completa (con materia prima)
- Si es sucursal: vista limitada (sin materia prima, solo campos básicos)

### 4.2 Archivo: `controllers/warehouse.controller.php`

**Método `addToWarehouse()`** (desde producción):
- Recibir producto compuesto
- **NO crear producto equivalente** - usar el mismo producto
- Guardar en almacén:
  - `id_product_warehouse = id_compuesto` (mismo producto)
  - `id_laboratory_warehouse = id_laboratorio`

**Método `defineSalePrice()`**:
- Definir precio en el producto (campo de precio de venta)
- Marcar `price_defined_warehouse = 1`

**Método `outputFromWarehouse()`**:
- Validar que precio esté definido
- Al crear ingreso en sucursal:
  - Usar `id_product_warehouse` (mismo producto, NO equivalente)
  - Usar precio definido
  - Asociar a sucursal destino
  - **Se registra como ingreso de producción** con precio definido por almacén

### 4.3 Archivo: `controllers/productions.controller.php`

**Método `completeProduction()`**:
- Verificar que oficina sea laboratorio
- Calcular costos
- Descontar materia prima del laboratorio
- Llamar a `warehouse->addToWarehouse()` con producto compuesto
- **NO se crea producto equivalente** - se usa el mismo producto con vista limitada

### 4.4 Archivo: `ajax/pos.ajax.php`

**Método `loadProducts()`**:
- Filtrar productos visibles para la sucursal
- Si es laboratorio: mostrar productos compuestos con vista completa
- Si es sucursal: mostrar productos del laboratorio pero con **vista limitada**:
  - Solo campos: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras
  - **NO mostrar materia prima** (componentes ocultos)
  - NO mostrar costos de producción
- El mismo producto se comparte, pero con filtrado de campos según rol

### 4.5 Archivo: `controllers/purchases.controller.php` → **RENOMBRAR A `ingresos.controller.php`**

**NOTA IMPORTANTE**: El módulo de compras se renombra a **"Ingresos"** porque ahora incluye:
- Compras tradicionales (de proveedores)
- Ingresos de producción (desde almacén/laboratorio)

**Método `createIngresoFromWarehouse()`** (nuevo):
- Crear ingreso automático cuando hay salida de almacén
- Usar el mismo producto (NO equivalente)
- Asociar a sucursal destino
- Estado: "Pendiente"
- **Se registra como ingreso de producción** con precio definido por almacén

---

## 5. CAMBIOS EN VISTAS

### 5.1 Módulo de Oficinas/Sucursales

- Agregar selector de tipo: Sucursal / Laboratorio / Almacén
- Mostrar tipo en listado
- Filtrar por tipo

### 5.2 Módulo de Productos (Laboratorio)

- Al crear producto compuesto:
  - Selector: "Producto Compuesto"
  - Campo: "Laboratorio" (automático según usuario)
  - Sección de componentes (materia prima)
  - Vista completa con todos los campos

### 5.3 Módulo de Productos (Sucursales)

- Muestra productos del laboratorio pero con **vista limitada**
- Solo muestra campos básicos:
  - ✅ Nombre
  - ✅ Imagen
  - ✅ Categoría
  - ✅ SKU
  - ✅ Medida
  - ✅ Stock
  - ✅ Descuento en %
  - ✅ Estado
  - ✅ Sucursal
  - ✅ Código de Barras
- **NO muestra materia prima** (componentes ocultos)
- NO muestra costos de producción
- NO muestra que es un producto compuesto

### 5.4 Módulo de Almacén

- Mostrar productos del laboratorio
- Al definir precio:
  - Se define en el producto (campo de precio de venta)
  - Se muestra costo del compuesto (solo rol laboratory)
  - El mismo producto se usará en sucursales con vista limitada

### 5.5 POS (Sucursales)

- Muestra productos del laboratorio con vista limitada
- Solo campos básicos (nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras)
- No muestra información de producción
- No muestra costos
- No muestra materia prima

---

## 6. PERMISOS Y VISIBILIDAD

### 6.1 Rol: Laboratorio

**Puede ver**:
- ✅ Materia prima (solo de su laboratorio)
- ✅ Compras de materia prima (solo de su laboratorio)
- ✅ Productos compuestos (solo de su laboratorio)
- ✅ Producciones (solo de su laboratorio)
- ✅ Almacén (todos los productos)
- ✅ Costos y precios
- ✅ Salidas a sucursales

**NO puede ver**:
- ❌ Productos simples de sucursales (excepto equivalentes)
- ❌ Ventas de sucursales
- ❌ Compras de sucursales (excepto las que él creó desde almacén)

### 6.2 Rol: Sucursal (Inventario)

**Puede ver**:
- ✅ Productos del laboratorio con **vista limitada** (sin materia prima)
- ✅ Solo campos: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras
- ✅ Ingresos (solo de su sucursal) - incluye ingresos de producción
- ✅ Ventas (solo de su sucursal)
- ✅ POS

**NO puede ver**:
- ❌ Materia prima (componentes del producto)
- ❌ Producciones
- ❌ Costos de producción
- ❌ Que un producto viene de producción (lo ve como ingreso normal)

### 6.3 Rol: Superadmin

**Puede ver todo**:
- ✅ Todos los productos (compuestos y simples)
- ✅ Todas las sucursales y laboratorio
- ✅ Todas las producciones
- ✅ Todos los costos
- ✅ Relaciones compuesto → simple

---

## 7. FLUJO DE DATOS: VISTA COMPLETA → VISTA LIMITADA

### 7.1 Compartir Inventario sin Duplicar

**IMPORTANTE**: NO se crean productos equivalentes. El mismo producto se comparte con diferentes vistas.

**Momento**: Cuando producción se completa y pasa a almacén

**Proceso**:
1. Sistema recibe producto compuesto
2. **NO crea producto equivalente** - usa el mismo producto
3. Guarda en almacén con ID del producto compuesto
4. El sistema filtra campos mostrados según rol/sucursal

### 7.2 Vista Limitada en Sucursales

**En Almacén**:
- Se muestra producto compuesto con vista completa
- Se define precio de venta en el producto

**En Salida**:
- Se usa el mismo producto (NO equivalente) para crear ingreso
- Sucursal recibe el mismo producto pero con vista limitada
- Se registra como ingreso de producción con precio definido

**En Sucursal**:
- Ve el mismo producto pero con **vista limitada**:
  - Solo: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras
  - **NO ve materia prima** (componentes ocultos)
  - NO ve costos
- Lo trata como cualquier otro producto
- No sabe que es compuesto (lo ve como ingreso normal)

---

## 8. EJEMPLO PRÁCTICO

### Producto: "Jabón Natural de Lavanda"

#### En Laboratorio:
- **Producto Compuesto**: "Jabón Natural de Lavanda"
  - ID: 100
  - `is_compound_product = 1`
  - `origin_office_product = 5` (laboratorio)
  - Componentes:
    - Aceite de coco: 200ml
    - Aceite de lavanda: 50ml
    - Sosa cáustica: 30g
    - Agua: 100ml
  - Costo materia prima: Bs 15
  - Costo mano de obra: Bs 5
  - Costo total: Bs 20
  - Costo unitario: Bs 20

#### Producción:
- Se producen 50 unidades
- Se descuenta materia prima del laboratorio
- Pasa a almacén

#### En Almacén:
- **Registro Almacén**:
  - `id_product_warehouse = 100` (mismo producto compuesto)
  - `id_laboratory_warehouse = 5`
  - Cantidad: 50
  - Costo unitario: Bs 20
  - Precio de venta: Bs 35 (definido en almacén)
  - **NO se crea producto equivalente** - se usa el mismo producto (ID 100)

#### Salida a Sucursal "Centro":
- Se envían 20 unidades
- Sistema crea ingreso en sucursal:
  - `id_office_purchase = 3` (sucursal Centro)
  - `id_product_purchase = 100` (mismo producto, NO equivalente)
  - `price_purchase = 35`
  - `qty_purchase = 20`
  - Estado: "Pendiente"
  - **Se registra como ingreso de producción** con precio definido por almacén

#### En Sucursal "Centro":
- Ve ingreso pendiente
- Producto: "Jabón Natural de Lavanda" (ID 100, mismo producto)
- Lo ve con **vista limitada** (sin materia prima):
  - Solo: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras
- No sabe que viene de producción (lo ve como ingreso normal)
- Cuando llega físicamente: marca "Recibido"
- Stock se actualiza: +20 unidades
- Disponible en POS

#### En POS de Sucursal:
- Producto aparece: "Jabón Natural de Lavanda" (ID 100)
- Precio: Bs 35
- Vista limitada (sin materia prima)
- Se vende normalmente
- No muestra costos
- No muestra que es compuesto

---

## 9. VALIDACIONES Y REGLAS

### 9.1 Validaciones de Laboratorio

1. ✅ Solo oficinas tipo 'laboratorio' pueden tener producciones
2. ✅ Solo oficinas tipo 'laboratorio' pueden tener materia prima
3. ✅ Productos compuestos solo pueden crearse en laboratorio
4. ✅ Materia prima solo se descuenta del laboratorio que produce

### 9.2 Validaciones de Almacén

1. ✅ NO se crean productos equivalentes - se usa el mismo producto
2. ✅ No se puede hacer salida sin precio definido
3. ✅ Precio debe ser mayor al costo
4. ✅ Al hacer salida, se usa el mismo producto (NO equivalente)
5. ✅ Se registra como ingreso de producción en la sucursal

### 9.3 Validaciones de Sucursales

1. ✅ Ven productos del laboratorio con vista limitada
2. ✅ Solo ven campos básicos (nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras)
3. ✅ NO ven materia prima (componentes ocultos)
4. ✅ No pueden ver costos de producción
5. ✅ Los ingresos de producción se registran como compras/ingresos normales

---

## 10. RESUMEN DE CAMBIOS VS ANÁLISIS ANTERIOR

### Cambios Clave:

1. ✅ **Laboratorio = Sucursal especial** (no módulo separado)
2. ✅ **Producto compuesto solo en laboratorio**
3. ✅ **NO se crean productos equivalentes** - el mismo producto se comparte
4. ✅ **Sucursales ven producto con vista limitada** (sin materia prima, solo campos básicos)
5. ✅ **Salida de almacén crea ingreso con el mismo producto** (registrado como ingreso de producción)
6. ✅ **Separación completa de datos sensibles** mediante filtrado de campos según rol
7. ✅ **Módulo de compras renombrado a "Ingresos"** (incluye compras e ingresos de producción)

### Estructura Final:

```
LABORATORIO (Sucursal tipo 'laboratorio')
  ├── Materia Prima
  ├── Compras Materia Prima
  ├── Productos Compuestos
  └── Producciones
       │
       ↓ (Producción completada)
       │
ALMACÉN
  ├── Producto Compuesto (mismo producto)
  └── Precio de Venta Definido
       │
       ↓ (Salida - Ingreso de Producción)
       │
SUCURSALES (Sucursales tipo 'sucursal')
  ├── Reciben como Ingreso (registrado como compra)
  ├── Mismo Producto con Vista Limitada
  │   └── Solo: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal, código de barras
  │   └── NO ve: materia prima, costos
  └── Venta en POS
```

---

Este ajuste refleja correctamente que el laboratorio es una sucursal especial y que el inventario se comparte con las sucursales, pero con vista limitada (sin materia prima) para mantener la privacidad de los datos sensibles.

---

## 11. RENOMBRAMIENTO DEL MÓDULO DE COMPRAS A "INGRESOS"

### 11.1 Justificación

El módulo de "Compras" ahora maneja dos tipos de transacciones:
1. **Compras tradicionales**: Adquisiciones de productos de proveedores externos
2. **Ingresos de producción**: Productos que llegan desde el almacén/laboratorio con precio definido

Por lo tanto, es más apropiado llamarlo **"Ingresos"** para reflejar ambos tipos de transacciones.

### 11.2 Cambios Necesarios

**Archivos a renombrar/actualizar**:
- `controllers/purchases.controller.php` → `controllers/ingresos.controller.php`
- Referencias en vistas: "Compras" → "Ingresos"
- Referencias en menú/navegación
- Referencias en base de datos (tabla `purchases` puede mantenerse por compatibilidad, pero la interfaz mostrará "Ingresos")

**Tabla de base de datos**:
- La tabla `purchases` puede mantenerse con su nombre actual por compatibilidad
- Se puede agregar un campo `type_purchase` para distinguir:
  - `'compra'` = Compra tradicional de proveedor
  - `'ingreso_produccion'` = Ingreso desde almacén/laboratorio

### 11.3 Sugerencias de Nombres Alternativos

Si "Ingresos" no es el término preferido, se pueden considerar:
- **"Ingresos"** ✅ (Recomendado - claro y directo)
- **"Entradas"** (Alternativa)
- **"Recepción"** (Alternativa)
- **"Compras e Ingresos"** (Más descriptivo pero más largo)

**Recomendación**: **"Ingresos"** es el término más apropiado porque:
- Es claro y conciso
- Cubre ambos casos (compras e ingresos de producción)
- Es un término común en sistemas de inventario

---

## 12. REQUISITOS FUNCIONALES Y NO FUNCIONALES

### 12.1 Situación actual (capacidades vigentes)

**Requisitos funcionales**
- RF-01: Registrar compras de materia prima y actualizar el stock del laboratorio.
- RF-02: Crear productos compuestos asociados a un laboratorio y configurar sus componentes.
- RF-03: Completar producciones que consumen materia prima, calculan costos y pasan productos al almacén.
- RF-04: Administrar almacén central para definir precios de venta y gestionar salidas a sucursales.
- RF-05: Registrar compras en sucursales (ingresos de stock) y posteriormente ventas en POS.
- RF-06: Gestionar usuarios y roles por sucursal con permisos diferenciados (laboratorio, sucursal, superadmin).

**Requisitos no funcionales**
- RNF-01: Seguridad basada en roles que protege datos sensibles (costos, materia prima).
- RNF-02: Integridad del inventario mediante cálculo de stock (ingresos − ventas).
- RNF-03: Auditabilidad de cada movimiento (fecha, usuario, sucursal).
- RNF-04: Disponibilidad 24/7 del sistema web sin interrumpir operaciones.

### 12.2 Cambios solicitados

**Inventario compartido con vista limitada**
- RF-07: El laboratorio mantiene la vista completa del producto (componentes, costos, materia prima).
- RF-08: Las sucursales consultan el **mismo producto** pero solo ven los campos básicos: nombre, imagen, categoría, SKU, medida, stock, descuento %, estado, sucursal y código de barras.
- RF-09: El POS y cualquier módulo que cargue productos debe aplicar el filtrado automático según rol.
- RNF-05: El ocultamiento de datos sensibles debe ocurrir en capa de API/serialización, evitando exponer esos campos al frontend de sucursal.

**Ingresos automáticos desde almacén**
- RF-10: Cada salida de almacén genera automáticamente un “Ingreso de producción” pendiente en la sucursal (tipo `ingreso_produccion`).
- RF-11: Al marcarlo como “Recibido”, el stock de la sucursal se actualiza y queda trazabilidad completa.
- RF-12: Permitir diferenciar compras tradicionales vs ingresos de producción para reportes y auditoría.
- RNF-06: La salida y la creación del ingreso deben ejecutarse como una operación atómica (si falla una parte, se revierte).
- RNF-07: Debe existir traza de quién generó la salida y quién confirmó el ingreso.

**Renombramiento del módulo**
- RF-13: Toda la interfaz (menús, tablas, reportes, formularios) usa el término **“Ingresos”**.
- RF-14: El módulo permite filtrar y reportar por `type_purchase` (compra | ingreso_produccion).
- RNF-08: Mantener compatibilidad con integraciones actuales (nombres de tablas y endpoints pueden permanecer igual).

**Datos y migraciones**
- RF-15: `warehouse` incorpora `id_laboratory_warehouse` y `price_defined_warehouse`.
- RF-16: `purchases` agrega `type_purchase` para clasificar ingresos.
- RNF-09: Migraciones ejecutadas con respaldo previo y ventana controlada para evitar downtime.

---

## 13. CRONOGRAMA DE IMPLEMENTACIÓN (MODULAR E INCREMENTAL)

| Semana | Entregable | Actividades principales |
| --- | --- | --- |
| 1 | Diseño detallado | Revisar alcance con negocio, definir reglas de filtrado por rol, planificar migraciones y casos de prueba. |
| 2 | Migraciones de datos | Agregar campos (`price_defined_warehouse`, `id_laboratory_warehouse`, `type_purchase`), crear scripts de respaldo y verificación. |
| 3 | Backend inventario compartido | Actualizar controladores/API para reutilizar un solo producto, aplicar filtros por rol y registrar `id_laboratory_warehouse`. |
| 4 | Módulo “Ingresos” | Renombrar módulo, actualizar controladores/vistas, crear flujo de ingresos automáticos desde almacén. |
| 5 | Frontend & POS | Ajustar UI para mostrar solo campos permitidos, actualizar listados/pos, pruebas funcionales por rol. |
| 6 | QA integral y despliegue | Pruebas end-to-end (laboratorio → almacén → sucursal), capacitación de usuarios y documentación final. |

**Estrategia incremental**
1. **Semanas 1-2**: Cambios invisibles para usuarios (migraciones, diseño técnico).
2. **Semana 3**: Activar inventario compartido en ambiente de pruebas (laboratorio + almacén).
3. **Semana 4**: Liberar el nuevo módulo “Ingresos” a un grupo piloto.
4. **Semana 5**: Extender al resto de sucursales/pos con monitoreo cercano.
5. **Semana 6**: Cierre del proyecto con QA, capacitación y checklist de aceptación.

