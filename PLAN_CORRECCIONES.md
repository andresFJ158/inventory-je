# Correcciones y Plan de Implementación — Sistema UniTech POS + Laboratorio

Documento de correcciones formalizadas y plan de desarrollo derivado de la revisión del proyecto presentado. Las correcciones están organizadas por módulo.

---

## 📋 Correcciones Formalizadas

### 🧾 Módulo de Facturación

1. **Factura opcional en el POS:** La generación de factura al momento de una venta debe ser opcional. El vendedor o cajero puede decidir si emite factura o no al cerrar una orden.
2. **Servicio de facturación (investigación pendiente):** Se debe investigar e integrar un servicio externo de facturación electrónica (SIN u otro proveedor compatible con Bolivia) para fases futuras del proyecto.

---

### 📊 Módulo de Reportes e Informes

3. **Informe mensual de gastos por vendedor:** Se debe generar un informe mensual detallado de los gastos realizados por cada vendedor, diferenciando gastos de caja y gastos asociados a órdenes de despacho.
4. **Nuevo módulo de informe general de la empresa:** Crear un nuevo módulo independiente de reportes que consolide los ingresos, egresos y operaciones de toda la empresa (todos los almacenes y sucursales) a nivel mensual.
5. **Reporte mensual de producción:** Generar un reporte mensual del laboratorio que detalle las producciones realizadas, sus costos reales y proyectados, rendimiento y cantidad producida.

---

### 🧪 Módulo de Laboratorio

6. **Proveedores exclusivos de laboratorio:** Los proveedores del laboratorio deben estar separados de los proveedores del POS. Cada módulo (Laboratorio y POS) debe gestionar sus propios proveedores de forma independiente.
7. **Catálogo de insumos del laboratorio:** Crear un catálogo de insumos específico para el laboratorio (etiquetas, botellas, tapas, empaques, etc.), separado de las materias primas.
8. **Registro de agua sin stock, con precio asignado:** El agua utilizada en producción no maneja stock propiamente (sale directamente del filtro), por lo que no se registra como un inventario de existencias. Sin embargo, sí debe registrarse su precio unitario para que sea incluida en el cálculo del costo de producción.
9. **Unidades de medida de materia prima e insumos:** Las materias primas deben manejarse en las unidades: **kg, litro y unidad**. Los insumos también deben soportar estas mismas unidades de medida.
10. **Receta formulada por litros de agua:** Al crear una receta de producción, la cantidad base se define en **litros de agua**, no en unidades de producto terminado.
11. **Costos indirectos como total de fabricación:** Los costos indirectos (electricidad, gas, alquiler, etc.) deben registrarse como un monto total por lote de fabricación, no como un costo por unidad.
12. **Manejo de merma — envase y paso a almacén:** Cuando existen restos (merma) al finalizar una producción, estos pueden envasarse y quedan registrados en el almacén como stock disponible. Desde el almacén, el responsable puede decidir:
    - **Distribuirlos a otra sucursal** (traspaso de inventario), donde podrán venderse desde el POS de esa sucursal, o
    - **Darlos de baja** con un concepto registrado (pérdida, regalo, consumo interno, etc.).
    La merma envasada **no se vende directamente** desde el punto de origen; primero debe distribuirse a una sucursal.
13. **Categorización de restos como desperdicio:** Los restos de producción que no son aprovechables deben categorizarse como "desperdicio". El desperdicio también consume insumos (etiquetas, tapas, etc.) que deben descontarse del inventario de insumos.
14. **Productos defectuosos — solo cuentan los insumos:** Cuando un producto sale defectuoso, únicamente se contabiliza el gasto de insumos utilizados en ese producto (no se considera el costo de materia prima por separado en dicho caso).
15. **Métricas de merma por receta:** El sistema debe registrar y mostrar las métricas de merma asociadas a cada receta, para analizar el rendimiento y eficiencia de cada producción.
16. **Reporte mensual de rendimiento de producción:** Generar un reporte mensual que compare la cantidad **proyectada vs. real** de cada producción. Si se esperaban 100 botellas y salieron 110, se reportan **+10 como ganancia de rendimiento**. Si salieron 90, se reportan **-10 como pérdida de rendimiento**. El reporte consolida todas las producciones del mes, permitiendo analizar eficiencia y variaciones por receta.
17. **Gráficos y reporte de merma:** Implementar gráficos visuales y un reporte exportable sobre la merma generada en las producciones, para facilitar el análisis del rendimiento y la toma de decisiones.
18. **Reportes profesionales del laboratorio:** Los reportes del módulo de laboratorio deben tener un formato profesional y exportable (PDF o Excel), incluyendo: producciones, costos, calidad y merma.

---

### 🛒 Módulo POS — Ventas

18. **Módulo de consignación se mantiene (sin nuevo registro desde el POS):** El módulo de consignación debe conservarse porque aún existen productos vigentes en consignación. No se elimina. Sin embargo, las **nuevas consignaciones ya no se registrarán desde el flujo de venta POS**, sino a través de un formulario independiente que permita registrar consignaciones antiguas o actuales.
19. **Stock inicial obligatorio al registrar un nuevo producto:** Al crear un producto nuevo en el POS, debe solicitarse el **stock inicial** del producto. Esto permite ingresar al sistema los productos que ya están físicamente en tienda. Una vez guardado, este campo no podrá modificarse directamente.
20. **Combos de productos:** Los combos se crean agrupando dos o más productos existentes. El precio del combo puede definirse de dos formas:
    - **Precio fijo:** Se establece un precio total manual para el conjunto (ej. 3 productos por Bs. 100), independientemente de los precios individuales.
    - **Descuento porcentual:** Se aplica un porcentaje de descuento sobre la suma de los precios individuales.
    Al vender un combo en el POS, el sistema descuenta el stock de **cada producto componente** de forma individual.
21. **Precio mayorista a partir de 12 unidades:** Cuando una orden incluye 12 o más unidades de un mismo producto, se aplica automáticamente el precio mayorista.
22. **QR de pago opcional:** La imagen o enlace del código QR de pago debe ser opcional al configurar una sucursal o medio de pago. No es obligatorio.
23. **Métodos de pago según tipo de vendedor:**
    - **Vendedor de caja (cajero):** Solo puede recibir pagos en **efectivo** y **QR**.
    - **Vendedor independiente:** Puede recibir pagos en **efectivo**, **QR** y **crédito** (fiado).
24. **Comprobante de venta requerido solo para vendedores independientes:** Los vendedores independientes deben subir el comprobante de pago (foto del QR o transferencia). Los cajeros **no necesitan** subir comprobante ya que gestionan pagos a través de la app de ganadero.

---

### 💰 Módulo de Caja

25. **Detalle de caja con desglose por método de pago:** El detalle de una sesión de caja debe mostrar explícitamente el monto recibido en **efectivo**, el monto recibido por **QR**, y el total de **gastos**, de forma separada.
26. **Monto de apertura de caja con valor sugerido:** Al abrir una sesión de caja, el campo de monto inicial viene prellenado con **Bs. 200** como valor sugerido, pero puede modificarse manualmente en caso de ser necesario.
27. **Botón de "Añadir Gasto" en el POS:**
    - Si quien opera es un **cajero**: el botón añade un gasto de caja directamente.
    - Si quien opera es un **vendedor independiente**: el botón añade un gasto a la orden activa en curso.
    - Al confirmar una orden de vendedor independiente, esta pasa a **despacho**, donde también se pueden añadir gastos adicionales.

---

### 🏪 Módulo de Almacén e Inventario

28. **Asignación de vendedores/cajeros a inventarios:** Cada vendedor o cajero puede estar asignado a un único inventario a la vez. Sin embargo, un inventario puede tener múltiples vendedores o cajeros asignados.
    - Estructura actual: 2 inventarios en la oficina de Santa Cruz (uno con 2 cajeros, otro con 2 vendedores) y 1 inventario en Montero.
29. **Traspasos de stock entre inventarios:** Implementar la funcionalidad para transferir stock de un inventario a otro, incluyendo el registro de la cantidad transferida, el origen y el destino.

---

### 👥 Módulo de Vendedores

30. **Porcentaje de comisión por vendedor:** Cada vendedor independiente tiene asignado un **porcentaje de comisión** configurable en su perfil. Este porcentaje se registra en el sistema y se incluye en el informe mensual de gastos por vendedor, calculando el monto de comisión acumulado en el mes. **No existe un módulo independiente de comisiones**; la configuración se hace desde el perfil del vendedor y el seguimiento desde los reportes.

---

### 🔧 Revisión de Base de Datos

31. **Revisión y actualización de atributos de las tablas:** Revisar todos los modelos de datos existentes y agregar los nuevos atributos necesarios para soportar las funcionalidades descritas en estas correcciones (métodos de pago, tipos de vendedor, stock inicial, merma envasada, etc.).

---

## 🗺️ Plan de Implementación

Las funcionalidades se agrupan en **3 fases** según urgencia e impacto operativo.

---

### 🔴 Fase 1 — Correcciones Críticas del POS (Alta Prioridad)

Estas correcciones afectan directamente el flujo de venta diario.

| # | Tarea | Archivos Involucrados |
|---|-------|-----------------------|
| 1 | Factura opcional al cerrar una orden | `pos.vue`, `orders.controller.php`, tabla `invoices` |
| 2 | Stock inicial al crear un producto nuevo | `pos.vue`, `admins.vue` (productos), `post.model.php` |
| 3 | Precio mayorista automático desde 12 unidades | `pos.vue`, lógica de cálculo de orden |
| 4 | Métodos de pago según tipo de vendedor (caja: efectivo+QR; independiente: efectivo+QR+crédito) | `pos.vue`, nuevo campo `type_seller` en `admins` |
| 5 | QR de pago opcional | `pos.vue`, configuración de sucursal |
| 6 | Botón "Añadir Gasto" contextual en el POS | `pos.vue`, tabla `bills` / gastos de orden |
| 7 | Detalle de caja con desglose efectivo/QR/gastos | `caja.vue`, tabla `cashs` (nuevos campos `cash_efectivo`, `cash_qr`) |
| 8 | Monto de apertura de caja fijo en Bs. 200 | `caja.vue` |
| 9 | Comprobante obligatorio solo para vendedores independientes | `pos.vue`, `despachos.vue` |
| 10 | Consignación sin nuevo registro desde el POS (mantener módulo) | `consignacion.vue`, `pos.vue` |

**Migraciones de BD — Fase 1:**
```sql
-- Tipo de vendedor en admins
ALTER TABLE admins ADD COLUMN type_seller VARCHAR(50) DEFAULT 'cajero';
-- Métodos de pago en caja
ALTER TABLE cashs ADD COLUMN cash_efectivo DOUBLE DEFAULT 0;
ALTER TABLE cashs ADD COLUMN cash_qr DOUBLE DEFAULT 0;
-- Stock inicial de producto
ALTER TABLE products ADD COLUMN initial_stock_product DOUBLE DEFAULT 0;
-- QR opcional en orders
ALTER TABLE orders ADD COLUMN qr_ref_order VARCHAR(255) DEFAULT NULL;
-- Método de pago enriquecido en orders
ALTER TABLE orders ADD COLUMN method_detail_order TEXT DEFAULT NULL;
```

---

### 🟡 Fase 2 — Almacén e Inventarios (Prioridad Media)

| # | Tarea | Archivos Involucrados |
|---|-------|-----------------------|
| 1 | Asignación de vendedores a inventarios (1 vendedor → 1 inventario; 1 inventario → N vendedores) | `admins`, `offices`, nueva tabla `inventory_assignments` |
| 2 | Traspasos de stock entre inventarios | `solicitar-inventario.vue` (extender), tabla `stock_transfers` |
| 3 | % de comisión en perfil del vendedor (sin módulo independiente); incluido en informe mensual | Campo `pct_commission_admin` en `admins`, `reportes.vue` |
| 4 | Informe mensual de gastos por vendedor (incluye comisión calculada) | `reportes.vue`, nuevas queries de agregación |
| 5 | Módulo de informe general de empresa | Nueva página `reportes-empresa.vue` |
| 6 | Combos: precio fijo **o** descuento % sobre total; descuento de stock por componente al vender | `combos.vue` (reimplementar lógica), `pos.vue`, tabla `combo_items` |

**Migraciones de BD — Fase 2:**
```sql
-- % de comisión e inventario asignado al vendedor
ALTER TABLE admins ADD COLUMN id_inventory_admin INT DEFAULT NULL;
ALTER TABLE admins ADD COLUMN pct_commission_admin DOUBLE DEFAULT 0;

-- Traspasos de stock entre inventarios
CREATE TABLE IF NOT EXISTS stock_transfers (
  id_transfer              INT AUTO_INCREMENT PRIMARY KEY,
  id_origin_office         INT NOT NULL,
  id_dest_office           INT NOT NULL,
  id_product_transfer      INT NOT NULL,
  qty_transfer             DOUBLE NOT NULL,
  id_admin_transfer        INT NOT NULL,
  notes_transfer           TEXT NULL,
  status_transfer          VARCHAR(50) DEFAULT 'pendiente',
  date_created_transfer    DATE NULL,
  date_updated_transfer    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- Nota: no se crea tabla de comisiones; el % se guarda en admins y se calcula en reportes.

-- Combos: ítems que componen cada combo
CREATE TABLE IF NOT EXISTS combo_items (
  id_combo_item       INT AUTO_INCREMENT PRIMARY KEY,
  id_combo_ci         INT NOT NULL,              -- referencia al producto combo
  id_product_ci       INT NOT NULL,              -- producto componente
  qty_ci              INT NOT NULL DEFAULT 1,    -- cantidad de ese componente
  date_created_ci     DATE NULL,
  date_updated_ci     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- El precio del combo se guarda en products.price_product (precio fijo manual)
--   o se calcula como: suma de precios - descuento % (products.discount_product)
-- Un nuevo campo indica el modo de precio:
ALTER TABLE products ADD COLUMN combo_price_mode VARCHAR(20) DEFAULT 'descuento'; -- 'fijo' | 'descuento'
-- Nota: is_compound_product (ya existente en migrations.php) marca al producto como combo.
```

---

### 🟢 Fase 3 — Laboratorio Avanzado y Reportes (Prioridad Normal)

| # | Tarea | Archivos Involucrados |
|---|-------|-----------------------|
| 1 | Proveedores separados por módulo (POS vs Laboratorio) | `proveedores.vue`, campo `type_supplier` en `suppliers` (ya existe) |
| 2 | Catálogo de insumos del laboratorio | `insumos.vue`, nueva tabla `lab_supplies` |
| 3 | Agua sin stock pero con precio registrado en recetas | `recetas.vue`, campos `no_stock_raw_material` y `price_raw_material` en `raw_materials` |
| 4 | Unidades de medida: kg, litro, unidad — materias primas e insumos | `materiales.vue`, `insumos.vue` |
| 5 | Receta basada en litros de agua | `recetas.vue`, ajustar `unit_batch_recipe` en tabla `recipes` |
| 6 | Costos indirectos como monto total por lote | `produccion.vue`, tabla `recipe_indirect_costs` |
| 7 | Merma envasada → almacén → distribución a sucursales (POS) o baja con concepto | `produccion.vue`, `almacen.vue`, tabla `waste_packaged` |
| 8 | Restos como desperdicio con consumo de insumos | `produccion.vue`, lógica de producción |
| 9 | Productos defectuosos — solo se contabilizan los insumos | `calidad.vue`, lógica de QC |
| 10 | Métricas de merma por receta | `recetas.vue`, `reportes.vue` |
| 11 | Reporte mensual de rendimiento: proyectado vs. real por producción (ganancia o pérdida de unidades) | `reportes.vue` o nueva página |
| 12 | Gráficos y reporte de merma | `reportes.vue` (nuevas gráficas) |
| 13 | Reporte mensual de producción (consolidado general) | `reportes.vue` o nueva página |
| 14 | Reportes profesionales del laboratorio (PDF/Excel) | `reportes.vue`, librería de exportación |
| 15 | Integración de servicio de facturación electrónica | Investigación + integración con proveedor confirmado (SIN Bolivia) |

**Migraciones de BD — Fase 3:**
```sql
-- Agua sin stock en materias primas
ALTER TABLE raw_materials ADD COLUMN no_stock_raw_material INT DEFAULT 0;
ALTER TABLE raw_materials ADD COLUMN price_raw_material DOUBLE DEFAULT 0;

-- Insumos de laboratorio
CREATE TABLE IF NOT EXISTS lab_supplies (
  id_supply              INT AUTO_INCREMENT PRIMARY KEY,
  name_supply            TEXT NOT NULL,
  unit_supply            VARCHAR(20) NOT NULL,  -- kg, litro, unidad
  stock_supply           DOUBLE DEFAULT 0,
  price_supply           DOUBLE DEFAULT 0,
  id_office_supply       INT NOT NULL,
  status_supply          INT DEFAULT 1,
  date_created_supply    DATE NULL,
  date_updated_supply    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Merma envasada (pasa a almacén; luego se distribuye a sucursal o da de baja)
CREATE TABLE IF NOT EXISTS waste_packaged (
  id_waste               INT AUTO_INCREMENT PRIMARY KEY,
  id_production_waste    INT NOT NULL,
  id_product_waste       INT NOT NULL,
  qty_waste              DOUBLE NOT NULL,
  id_office_waste        INT NOT NULL,          -- almacén donde ingresa
  status_waste           VARCHAR(50) DEFAULT 'en_almacen',  -- en_almacen, distribuido, baja
  concept_waste          TEXT NULL,             -- concepto en caso de baja
  id_admin_waste         INT NOT NULL,
  notes_waste            TEXT NULL,
  date_created_waste     DATE NULL,
  date_updated_waste     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Métricas de merma en producciones
ALTER TABLE productions ADD COLUMN waste_qty_production DOUBLE DEFAULT 0;
ALTER TABLE productions ADD COLUMN waste_packaged_qty DOUBLE DEFAULT 0;
ALTER TABLE productions ADD COLUMN waste_loss_qty DOUBLE DEFAULT 0;
```

---

## ✅ Verificación por Fase

### Fase 1
- Prueba del flujo de venta completo: cajero y vendedor independiente
- Verificar que la apertura de caja prellena Bs. 200 pero permite modificarlo
- Verificar que el desglose de caja muestra efectivo + QR + gastos por separado
- Verificar que el precio mayorista se aplica automáticamente desde 12 unidades
- Verificar que la factura es opcional al cerrar una orden

### Fase 2
- Verificar que un vendedor solo puede estar asignado a un inventario
- Verificar traspaso de stock entre inventarios con historial
- Verificar que el % de comisión del vendedor aparece correctamente en el informe mensual
- Verificar que al vender un combo se descuenta el stock de cada componente

### Fase 3
- Verificar que las recetas calculan correctamente con litros de agua como base
- Verificar el flujo de merma: producción → almacén → distribución a sucursal o baja con concepto
- Verificar que los reportes del laboratorio son exportables (PDF/Excel)
- Verificar métricas de merma asociadas a cada receta

---

## ✅ Decisiones Confirmadas

| Punto | Decisión |
|-------|----------|
| **Comisiones** | No existe módulo independiente de comisiones. El **% de comisión** se configura en el perfil del vendedor (`pct_commission_admin`) y se calcula en el **informe mensual de gastos**, sin tabla separada. |
| **Facturación electrónica** | Ya hay proveedor confirmado. Se integrará en Fase 3. Por ahora el módulo de factura queda con emisión opcional. |
| **Merma envasada** | La merma envasada **pasa al almacén** como stock. Desde ahí solo puede: **distribuirse a una sucursal** (donde se vende desde el POS de esa sucursal) o **darse de baja con concepto** (pérdida, regalo, consumo interno). No se vende directamente desde el punto de origen. |
| **Combos** | Se construyen agrupando productos en la tabla `combo_items`. El precio puede definirse como **precio fijo manual** (ej. 3 productos por Bs. 100) o como **descuento % sobre el total** de precios individuales. El modo de precio se controla con el campo `combo_price_mode`. Al vender el combo, se descuenta el stock de **cada componente individualmente**. |
