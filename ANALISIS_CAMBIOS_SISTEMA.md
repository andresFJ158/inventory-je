# ANÁLISIS DETALLADO: CAMBIOS REQUERIDOS PARA NUEVAS FUNCIONALIDADES

## RESUMEN EJECUTIVO

Este documento detalla todos los cambios necesarios en la base de datos, controladores, vistas y flujos del sistema para implementar las nuevas funcionalidades solicitadas. **NO se realizan cambios en el código**, solo se analiza y documenta.

---

## 1. SUBCATEGORÍAS

### 1.1 Cambios en Base de Datos

**Tabla: `categories`**
- **Nuevo campo**: `id_parent_category` (INT NULL DEFAULT '0')
  - Si es 0 = categoría principal
  - Si tiene valor = subcategoría (apunta a id_category padre)
- **Nuevo campo**: `level_category` (INT NULL DEFAULT '1')
  - 1 = categoría principal
  - 2 = subcategoría

**Relaciones**:
- Una categoría puede tener múltiples subcategorías
- Una subcategoría pertenece a una categoría padre

### 1.2 Cambios en Controladores

**Archivo**: `ajax/pos.ajax.php` - Método `loadProducts()`
- Modificar consultas para incluir subcategorías
- Actualizar filtrado: si se selecciona categoría padre, mostrar productos de todas sus subcategorías
- Agregar lógica para filtrar por subcategoría específica

**Archivo**: `views/pages/dynamic/custom/products/modules/categories.php`
- Modificar visualización para mostrar jerarquía
- Agregar indicador visual de subcategorías
- Permitir selección de categoría padre o subcategoría específica

### 1.3 Cambios en Vistas

**Módulo de Productos**:
- Agregar selector de categoría padre y subcategoría
- Mostrar jerarquía: Categoría > Subcategoría
- Validar que al seleccionar subcategoría, se asigne automáticamente la categoría padre

### 1.4 Flujo de Trabajo

1. Al crear/editar categoría, opción de seleccionar categoría padre
2. Si se selecciona padre, se convierte en subcategoría
3. En POS, mostrar categorías principales con opción de expandir subcategorías
4. Al filtrar por categoría principal, mostrar productos de todas sus subcategorías

---

## 2. TIPOS DE ÓRDENES (CONTADO, CRÉDITO, CONSIGNACIÓN)

### 2.1 Cambios en Base de Datos

**Tabla: `orders`**
- **Nuevo campo**: `type_order` (TEXT NULL DEFAULT 'contado')
  - Valores: 'contado', 'credito', 'consignacion'
- **Nuevo campo**: `days_credit_order` (INT NULL DEFAULT '0')
  - Días de plazo para órdenes a crédito
- **Nuevo campo**: `total_paid_order` (DOUBLE NULL DEFAULT '0')
  - Total pagado (para consignación y crédito)
- **Nuevo campo**: `remaining_balance_order` (DOUBLE NULL DEFAULT '0')
  - Saldo pendiente (para consignación y crédito)
- **Nuevo campo**: `allow_product_change_order` (INT NULL DEFAULT '0')
  - 0 = no permite cambio, 1 = permite cambio (solo crédito)

**Tabla: `sales`** (modificaciones)
- **Nuevo campo**: `can_exchange_sale` (INT NULL DEFAULT '0')
  - 0 = no permite cambio, 1 = permite cambio
- **Nuevo campo**: `exchanged_sale` (INT NULL DEFAULT '0')
  - 0 = no cambiado, 1 = cambiado

**Nueva Tabla: `order_payments`** (para abonos de consignación)
- `id_order_payment` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_order_payment_order` (INT) - FK a orders
- `amount_payment` (DOUBLE)
- `date_payment` (DATE)
- `method_payment` (TEXT)
- `id_admin_payment` (INT) - FK a admins
- `date_created_payment` (DATE)
- `date_updated_payment` (TIMESTAMP)

**Nueva Tabla: `order_returns`** (para devoluciones de consignación)
- `id_order_return` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_order_return_order` (INT) - FK a orders
- `id_product_return` (INT) - FK a products
- `qty_return` (INT)
- `reason_return` (TEXT)
- `date_return` (DATE)
- `id_admin_return` (INT) - FK a admins
- `date_created_return` (DATE)
- `date_updated_return` (TIMESTAMP)

### 2.2 Cambios en Controladores

**Archivo**: `ajax/pos.ajax.php`
- **Método `newOrder()`**: Agregar campo `type_order` al crear orden
- **Método `updateOrder()`**: Agregar campos de tipo de orden, días de crédito, etc.
- **Nuevo método `addPayment()`**: Para agregar abonos a órdenes de consignación
- **Nuevo método `addReturn()`**: Para registrar devoluciones de consignación
- **Nuevo método `exchangeProduct()`**: Para cambiar productos en órdenes a crédito

**Archivo**: `controllers/orders.controller.php`
- **Método `manageOrder()`**: 
  - Modificar lógica según tipo de orden
  - Para contado: comportamiento actual
  - Para crédito: no completar inmediatamente, permitir cambios
  - Para consignación: permitir abonos y devoluciones
- **Nuevo método `confirmCreditOrder()`**: Confirmar orden a crédito
- **Nuevo método `processConsignmentPayment()`**: Procesar abono de consignación
- **Nuevo método `processConsignmentReturn()`**: Procesar devolución de consignación

### 2.3 Cambios en Vistas

**POS (Punto de Venta)**:
- Agregar selector de tipo de orden antes de procesar
- Si es crédito: mostrar campo de días de plazo y checkbox "Permitir cambio de producto"
- Si es consignación: mostrar sección de abonos y devoluciones
- Mostrar estado de pago (total pagado / total orden)

**Módulo de Órdenes**:
- Agregar columna de tipo de orden
- Mostrar días de crédito si aplica
- Mostrar saldo pendiente para crédito/consignación
- Botones de acción según tipo:
  - Crédito: "Confirmar", "Agregar Abono", "Cambiar Producto"
  - Consignación: "Agregar Abono", "Registrar Devolución", "Completar"

**Nueva Vista**: `views/pages/dynamic/custom/orders/modules/payments.php`
- Lista de abonos realizados
- Formulario para agregar nuevo abono
- Historial de pagos

**Nueva Vista**: `views/pages/dynamic/custom/orders/modules/returns.php`
- Lista de devoluciones realizadas
- Formulario para registrar nueva devolución
- Historial de devoluciones

### 2.4 Flujo de Trabajo

#### Orden a Contado:
1. Seleccionar tipo "Contado" en POS
2. Procesar orden normalmente
3. Estado cambia a "Completada" inmediatamente
4. Stock se actualiza inmediatamente

#### Orden a Crédito:
1. Seleccionar tipo "Crédito" en POS
2. Ingresar días de plazo (opcional)
3. Marcar si permite cambio de producto (opcional)
4. Procesar orden
5. Estado queda en "Pendiente" o "En Crédito"
6. Administrador puede confirmar orden (cambia estado a "Completada")
7. Si permite cambio: se puede cambiar producto antes de confirmar
8. Stock NO se actualiza hasta confirmar

#### Orden en Consignación:
1. Seleccionar tipo "Consignación" en POS
2. Procesar orden
3. Estado queda en "En Consignación"
4. Se pueden agregar abonos parciales
5. Se pueden registrar devoluciones de productos
6. Cuando se complete pago total, estado cambia a "Completada"
7. Stock se actualiza restando devoluciones

---

## 3. COMPROBANTE DE PAGO POR TRANSFERENCIA

### 3.1 Cambios en Base de Datos

**Tabla: `orders`**
- **Nuevo campo**: `payment_proof_order` (TEXT NULL DEFAULT NULL)
  - Ruta/URL de la imagen del comprobante
- **Nuevo campo**: `payment_confirmed_order` (INT NULL DEFAULT '0')
  - 0 = pendiente de confirmación
  - 1 = confirmado
- **Nuevo campo**: `payment_confirmed_by_order` (INT NULL DEFAULT '0')
  - FK a admins (quien confirmó)
- **Nuevo campo**: `payment_confirmed_date_order` (DATE NULL DEFAULT NULL)
  - Fecha de confirmación

### 3.2 Cambios en Controladores

**Archivo**: `ajax/pos.ajax.php`
- **Método `updateOrder()`**: Agregar lógica para subir comprobante
- **Nuevo método `uploadPaymentProof()`**: 
  - Subir imagen del comprobante
  - Guardar ruta en base de datos
  - Cambiar estado a "Pendiente de Confirmación"

**Archivo**: `controllers/orders.controller.php`
- **Método `manageOrder()`**: 
  - Si método es transferencia y no hay comprobante: estado "Pendiente de Confirmación"
  - Si hay comprobante: validar antes de completar
- **Nuevo método `confirmPayment()`**: 
  - Confirmar pago por transferencia
  - Cambiar estado a "Completada"
  - Registrar quien confirmó y fecha

**Archivo**: `ajax/files.ajax.php` (o crear nuevo)
- Agregar endpoint para subir comprobantes de pago
- Validar tipo de archivo (solo imágenes)
- Guardar en carpeta específica: `/views/assets/files/payment_proofs/`

### 3.3 Cambios en Vistas

**POS**:
- Si método de pago es "Transferencia":
  - Mostrar campo para subir comprobante (input file)
  - Preview de imagen si ya se subió
  - Botón "Editar comprobante" si ya existe
- Mostrar estado: "Pendiente de Confirmación" si no está confirmado

**Módulo de Órdenes**:
- Agregar columna "Estado de Pago"
- Mostrar badge: "Pendiente", "Confirmado"
- Si está pendiente y método es transferencia:
  - Mostrar imagen del comprobante
  - Botón "Confirmar Pago"
  - Botón "Rechazar Pago" (opcional)
- Filtro para ver solo órdenes pendientes de confirmación

**Nueva Vista**: `views/pages/dynamic/custom/orders/modules/payment_confirmation.php`
- Lista de órdenes pendientes de confirmación
- Vista previa de comprobantes
- Botones de acción: Confirmar / Rechazar

### 3.4 Flujo de Trabajo

1. En POS, seleccionar método de pago "Transferencia"
2. Opción 1: Subir comprobante antes de procesar
   - Subir imagen
   - Procesar orden
   - Estado: "Pendiente de Confirmación"
3. Opción 2: Procesar sin comprobante
   - Procesar orden
   - Estado: "Pendiente de Confirmación"
   - Luego editar orden y subir comprobante
4. Administrador revisa órdenes pendientes
5. Administrador ve comprobante y confirma o rechaza
6. Si confirma: estado cambia a "Completada", se actualiza stock
7. Si rechaza: se notifica al vendedor (opcional)

---

## 4. TIPOS DE CLIENTES (COMÚN Y DISTRIBUIDOR)

### 4.1 Cambios en Base de Datos

**Tabla: `clients`**
- **Nuevo campo**: `type_client` (TEXT NULL DEFAULT 'comun')
  - Valores: 'comun', 'distribuidor'
- **Nuevo campo**: `discount_distributor_client` (DOUBLE NULL DEFAULT '0')
  - Descuento especial para distribuidor (%)

### 4.2 Cambios en Controladores

**Archivo**: `ajax/pos.ajax.php`
- **Método `newClient()`**: Agregar campo `type_client`
- **Método `addProductPos()`**: 
  - Si cliente es distribuidor, aplicar descuento automático
  - Calcular precio con descuento de distribuidor

**Archivo**: `controllers/orders.controller.php`
- Aplicar descuento de distribuidor si aplica

### 4.3 Cambios en Vistas

**Módulo de Clientes**:
- Agregar selector de tipo de cliente
- Si es distribuidor: mostrar campo de descuento
- Mostrar badge de tipo en listado

**POS**:
- Mostrar tipo de cliente seleccionado
- Aplicar descuento automático si es distribuidor
- Mostrar descuento aplicado en resumen

### 4.4 Flujo de Trabajo

1. Al crear/editar cliente, seleccionar tipo
2. Si es distribuidor, configurar descuento especial
3. En POS, al seleccionar cliente distribuidor:
   - Se aplica descuento automáticamente
   - Se muestra en resumen de orden
4. El descuento se suma al descuento de producto si aplica

---

## 5. DESCUENTO A VENTA TOTAL EN POS

### 5.1 Cambios en Base de Datos

**Tabla: `orders`**
- **Campo existente**: `discount_order` (ya existe)
- **Modificación**: Permitir descuento manual además del calculado

### 5.2 Cambios en Controladores

**Archivo**: `ajax/pos.ajax.php`
- **Método `updateOrder()`**: Ya existe campo `discountOrder`
- **Modificar**: Agregar campo en formulario POS para descuento manual
- **Lógica**: 
  - Descuento total = Descuento de productos + Descuento manual de orden
  - Recalcular totales al aplicar descuento

### 5.3 Cambios en Vistas

**POS**:
- Agregar campo numérico "Descuento Total (%)" o "Descuento Total (Bs)"
- Botón "Aplicar Descuento"
- Mostrar descuento aplicado en resumen
- Recalcular totales en tiempo real

### 5.4 Flujo de Trabajo

1. En POS, después de agregar productos
2. Ingresar descuento total (porcentaje o monto fijo)
3. Aplicar descuento
4. Sistema recalcula:
   - Subtotal
   - Descuento total (productos + manual)
   - Impuestos
   - Total final
5. Guardar descuento en orden

---

## 6. MÓDULO DE GASTOS

### 6.1 Cambios en Base de Datos

**Nueva Tabla: `expense_types`**
- `id_expense_type` (INT AUTO_INCREMENT PRIMARY KEY)
- `name_expense_type` (TEXT)
- `description_expense_type` (TEXT)
- `status_expense_type` (INT DEFAULT '1')
- `date_created_expense_type` (DATE)
- `date_updated_expense_type` (TIMESTAMP)

**Nueva Tabla: `expenses`**
- `id_expense` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_expense_type_expense` (INT) - FK a expense_types
- `type_expense` (TEXT) - 'caja' o 'orden'
- `id_cash_expense` (INT) - FK a cashs (si es gasto en caja)
- `id_order_expense` (INT) - FK a orders (si es gasto en orden)
- `amount_expense` (DOUBLE)
- `description_expense` (TEXT)
- `id_admin_expense` (INT) - FK a admins
- `id_office_expense` (INT) - FK a offices
- `date_expense` (DATE)
- `date_created_expense` (DATE)
- `date_updated_expense` (TIMESTAMP)

**Tabla: `cashs`** (verificar si existe, si no crear)
- `id_cash` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_office_cash` (INT) - FK a offices
- `date_created_cash` (DATE)
- `status_cash` (INT) - 1 = abierta, 0 = cerrada
- `initial_amount_cash` (DOUBLE)
- `final_amount_cash` (DOUBLE)
- `date_updated_cash` (TIMESTAMP)

### 6.2 Cambios en Controladores

**Nuevo Archivo**: `controllers/expenses.controller.php`
- **Método `createExpense()`**: Crear gasto (caja u orden)
- **Método `listExpenses()`**: Listar gastos con filtros
- **Método `updateExpense()`**: Actualizar gasto
- **Método `deleteExpense()`**: Eliminar gasto

**Nuevo Archivo**: `ajax/expenses.ajax.php`
- **Método `addExpenseToCash()`**: Agregar gasto a caja activa
- **Método `addExpenseToOrder()`**: Agregar gasto a orden
- **Método `listExpenseTypes()`**: Listar tipos de gastos

**Archivo**: `ajax/pos.ajax.php`
- **Nuevo método `addExpenseToOrder()`**: Agregar gasto a orden desde POS

### 6.3 Cambios en Vistas

**Nuevo Módulo**: `views/pages/dynamic/custom/expenses/`
- **expenses.php**: Lista principal de gastos
- **expense_types.php**: Gestión de tipos de gastos
- **add_expense.php**: Formulario para agregar gasto

**POS**:
- Agregar sección "Gastos de Orden"
- Botón "Agregar Gasto"
- Lista de gastos agregados
- Sumar gastos al total de la orden

**Módulo de Gastos**:
- Filtro por tipo (caja u orden)
- Filtro por fecha
- Filtro por tipo de gasto
- Agregar gasto a caja activa del día
- Ver gastos de órdenes específicas

### 6.4 Flujo de Trabajo

#### Gastos en Caja:
1. Ir a módulo de Gastos
2. Seleccionar "Gasto en Caja"
3. Seleccionar tipo de gasto (o crear nuevo tipo)
4. Ingresar monto y descripción
5. Sistema asocia a caja activa del día actual
6. Se registra en historial de caja

#### Gastos en Orden:
**Opción 1 desde POS**:
1. En POS, después de agregar productos
2. Click en "Agregar Gasto"
3. Seleccionar tipo de gasto
4. Ingresar monto y descripción
5. Se agrega a la orden
6. Se suma al total de la orden

**Opción 2 desde Módulo de Gastos**:
1. Ir a módulo de Gastos
2. Seleccionar "Gasto en Orden"
3. Seleccionar orden específica
4. Agregar gasto
5. Se asocia a la orden

---

## 7. MÓDULO DE PRODUCCIÓN

### 7.1 Cambios en Base de Datos

**Nueva Tabla: `productions`**
- `id_production` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_product_production` (INT) - FK a products
- `qty_production` (INT)
- `start_date_production` (DATE)
- `estimated_end_date_production` (DATE)
- `actual_end_date_production` (DATE NULL)
- `status_production` (TEXT) - 'pendiente', 'en_proceso', 'completada', 'cancelada'
- `cost_production` (DOUBLE) - Costo calculado de producción
- `id_admin_production` (INT) - FK a admins
- `id_office_production` (INT) - FK a offices
- `notes_production` (TEXT)
- `date_created_production` (DATE)
- `date_updated_production` (TIMESTAMP)

### 7.2 Cambios en Controladores

**Nuevo Archivo**: `controllers/productions.controller.php`
- **Método `createProduction()`**: Crear registro de producción
- **Método `updateProduction()`**: Actualizar producción
- **Método `completeProduction()`**: Completar producción (mover a almacén)
- **Método `listProductions()`**: Listar producciones con filtros

**Nuevo Archivo**: `ajax/productions.ajax.php`
- **Método `createProduction()`**: Crear producción vía AJAX
- **Método `updateStatus()`**: Cambiar estado de producción
- **Método `completeProduction()`**: Completar y mover a almacén

### 7.3 Cambios en Vistas

**Nuevo Módulo**: `views/pages/dynamic/custom/productions/`
- **productions.php**: Lista de producciones
- **add_production.php**: Formulario para crear producción
- **edit_production.php**: Formulario para editar producción
- **production_detail.php**: Detalle de producción

**Características**:
- Filtro por estado
- Filtro por producto
- Filtro por fecha
- Indicadores visuales de estado
- Botón "Completar" que mueve a almacén

### 7.4 Flujo de Trabajo

1. Crear registro de producción:
   - Seleccionar producto
   - Ingresar cantidad
   - Fecha inicial (automática: hoy)
   - Fecha estimada de salida
   - Estado inicial: "Pendiente"
2. Cambiar estado a "En Proceso" cuando se inicie
3. Al completar producción:
   - Cambiar estado a "Completada"
   - Calcular costo de producción (si es producto compuesto, basado en materia prima)
   - Crear registro automático en almacén
   - Fecha de salida actual = hoy

---

## 8. MÓDULO DE ALMACÉN

### 8.1 Cambios en Base de Datos

**Nueva Tabla: `warehouse`**
- `id_warehouse` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_product_warehouse` (INT) - FK a products
- `qty_warehouse` (INT)
- `cost_warehouse` (DOUBLE) - Costo unitario
- `sale_price_warehouse` (DOUBLE) - Precio de venta
- `id_production_warehouse` (INT) - FK a productions (si viene de producción)
- `id_purchase_warehouse` (INT) - FK a purchases (si viene de compra directa)
- `date_entry_warehouse` (DATE)
- `id_admin_warehouse` (INT) - FK a admins
- `date_created_warehouse` (DATE)
- `date_updated_warehouse` (TIMESTAMP)

**Nueva Tabla: `warehouse_outputs`**
- `id_warehouse_output` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_warehouse_output_warehouse` (INT) - FK a warehouse
- `id_office_output` (INT) - FK a offices (sucursal destino)
- `qty_output` (INT)
- `id_purchase_output` (INT) - FK a purchases (se crea como compra)
- `id_admin_output` (INT) - FK a admins
- `date_output` (DATE)
- `date_created_output` (DATE)
- `date_updated_output` (TIMESTAMP)

### 8.2 Cambios en Controladores

**Nuevo Archivo**: `controllers/warehouse.controller.php`
- **Método `listWarehouse()`**: Listar productos en almacén
- **Método `addToWarehouse()`**: Agregar producto a almacén (desde producción)
- **Método `outputFromWarehouse()`**: Salida de almacén a sucursal
- **Método `updateWarehouse()`**: Actualizar cantidad/costo/precio

**Nuevo Archivo**: `ajax/warehouse.ajax.php`
- **Método `outputToOffice()`**: Crear salida a sucursal
- **Método `listOutputs()`**: Listar salidas realizadas

**Archivo**: `controllers/productions.controller.php`
- **Método `completeProduction()`**: 
  - Al completar, crear registro en almacén
  - Calcular costo basado en materia prima (si es compuesto)

### 8.3 Cambios en Vistas

**Nuevo Módulo**: `views/pages/dynamic/custom/warehouse/`
- **warehouse.php**: Lista de productos en almacén
- **add_to_warehouse.php**: Agregar producto manualmente
- **outputs.php**: Lista de salidas de almacén
- **create_output.php**: Formulario para crear salida

**Características**:
- Mostrar cantidad disponible por producto
- Mostrar costo y precio de venta
- Botón "Salida" para enviar a sucursal
- Al hacer salida:
  - Seleccionar sucursal destino
  - Ingresar cantidad
  - Se crea automáticamente como compra en la sucursal
  - Se actualiza stock en almacén

### 8.4 Flujo de Trabajo

#### Entrada a Almacén (desde Producción):
1. Al completar producción, se crea registro automático en almacén
2. Se guarda:
   - Producto
   - Cantidad producida
   - Costo calculado
   - Precio de venta
   - Referencia a producción

#### Salida de Almacén:
1. Ver productos en almacén
2. Seleccionar producto
3. Click en "Salida"
4. Seleccionar sucursal destino
5. Ingresar cantidad a enviar
6. Sistema:
   - Crea registro en `warehouse_outputs`
   - Crea compra automática en la sucursal destino
   - Actualiza stock en almacén (resta cantidad)
   - Actualiza stock en sucursal (suma cantidad)

---

## 9. ESTADOS EN COMPRAS (PENDIENTE Y CONFIRMADO)

### 9.1 Cambios en Base de Datos

**Tabla: `purchases`** (modificar si no existe campo)
- **Nuevo campo**: `status_purchase` (TEXT NULL DEFAULT 'pendiente')
  - Valores: 'pendiente', 'confirmado'
- **Nuevo campo**: `confirmed_date_purchase` (DATE NULL)
  - Fecha de confirmación
- **Nuevo campo**: `confirmed_by_purchase` (INT NULL)
  - FK a admins (quien confirmó)

### 9.2 Cambios en Controladores

**Archivo**: `controllers/purchases.controller.php` (crear si no existe)
- **Método `confirmPurchase()`**: Confirmar compra
  - Cambiar estado a "confirmado"
  - Actualizar stock (solo cuando se confirma)
  - Registrar fecha y usuario que confirmó

**Archivo**: `ajax/purchase.ajax.php` (verificar si existe)
- **Método `confirmPurchase()`**: Confirmar compra vía AJAX

**Archivo**: `ajax/stock.ajax.php`
- **Modificar `updateStock()`**: 
  - Solo contar compras con estado "confirmado"
  - Ignorar compras "pendientes"

### 9.3 Cambios en Vistas

**Módulo de Compras**:
- Agregar columna "Estado"
- Mostrar badge: "Pendiente" (amarillo), "Confirmado" (verde)
- Botón "Confirmar" en compras pendientes
- Filtro por estado
- Al confirmar:
  - Cambiar estado
  - Actualizar stock automáticamente
  - Registrar fecha de confirmación

### 9.4 Flujo de Trabajo

1. Registrar compra (estado: "Pendiente")
2. Stock NO se actualiza aún
3. Cuando el producto llega físicamente a la sucursal:
4. Administrador confirma la compra
5. Estado cambia a "Confirmado"
6. Stock se actualiza automáticamente
7. Se registra fecha y usuario que confirmó

---

## 10. PRODUCTOS SIMPLES Y COMPUESTOS

### 10.1 Cambios en Base de Datos

**Tabla: `products`**
- **Nuevo campo**: `type_product` (TEXT NULL DEFAULT 'simple')
  - Valores: 'simple', 'compuesto'
- **Nuevo campo**: `calculated_cost_product` (DOUBLE NULL DEFAULT '0')
  - Costo calculado basado en materia prima (solo compuestos)

**Nueva Tabla: `raw_materials`** (Materia Prima)
- `id_raw_material` (INT AUTO_INCREMENT PRIMARY KEY)
- `name_raw_material` (TEXT)
- `unit_raw_material` (TEXT) - unidad de medida
- `stock_raw_material` (DOUBLE) - stock disponible
- `cost_raw_material` (DOUBLE) - costo unitario actual
- `id_office_raw_material` (INT) - FK a offices
- `status_raw_material` (INT DEFAULT '1')
- `date_created_raw_material` (DATE)
- `date_updated_raw_material` (TIMESTAMP)

**Nueva Tabla: `raw_material_purchases`** (Compras de Materia Prima)
- `id_raw_material_purchase` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_raw_material_purchase_raw_material` (INT) - FK a raw_materials
- `qty_raw_material_purchase` (DOUBLE)
- `cost_raw_material_purchase` (DOUBLE) - costo unitario de compra
- `total_cost_raw_material_purchase` (DOUBLE)
- `id_admin_raw_material_purchase` (INT) - FK a admins
- `id_office_raw_material_purchase` (INT) - FK a offices
- `date_purchase_raw_material_purchase` (DATE)
- `date_created_raw_material_purchase` (DATE)
- `date_updated_raw_material_purchase` (TIMESTAMP)

**Nueva Tabla: `product_components`** (Componentes de Producto Compuesto)
- `id_product_component` (INT AUTO_INCREMENT PRIMARY KEY)
- `id_product_component_product` (INT) - FK a products (producto compuesto)
- `id_raw_material_component` (INT) - FK a raw_materials
- `qty_component` (DOUBLE) - cantidad de materia prima necesaria
- `unit_component` (TEXT) - unidad
- `date_created_component` (DATE)
- `date_updated_component` (TIMESTAMP)

### 10.2 Cambios en Controladores

**Archivo**: `controllers/products.controller.php` (crear si no existe)
- **Método `createProduct()`**: 
  - Si es compuesto, validar que tenga componentes
- **Método `calculateCompoundCost()`**: 
  - Calcular costo de producto compuesto
  - Sumar: (cantidad_materia_prima * costo_materia_prima) para cada componente

**Nuevo Archivo**: `controllers/raw_materials.controller.php`
- **Método `createRawMaterial()`**: Crear materia prima
- **Método `updateRawMaterial()`**: Actualizar materia prima
- **Método `purchaseRawMaterial()`**: Registrar compra de materia prima
- **Método `updateStock()`**: Actualizar stock de materia prima

**Nuevo Archivo**: `ajax/raw_materials.ajax.php`
- **Método `addComponent()`**: Agregar componente a producto compuesto
- **Método `removeComponent()`**: Eliminar componente
- **Método `calculateCost()`**: Calcular costo de producto compuesto
- **Método `purchaseRawMaterial()`**: Registrar compra vía AJAX

**Archivo**: `controllers/productions.controller.php`
- **Método `completeProduction()`**: 
  - Si producto es compuesto:
    - Descontar materia prima usada del stock
    - Calcular costo real de producción
    - Guardar en almacén con costo calculado

### 10.3 Cambios en Vistas

**Módulo de Productos**:
- Agregar selector de tipo: "Simple" o "Compuesto"
- Si es compuesto:
  - Sección "Componentes"
  - Tabla de materia prima necesaria
  - Botón "Agregar Componente"
  - Mostrar costo calculado
  - Botón "Recalcular Costo"

**Nuevo Módulo**: `views/pages/dynamic/custom/raw_materials/`
- **raw_materials.php**: Lista de materia prima
- **add_raw_material.php**: Crear materia prima
- **purchases.php**: Compras de materia prima
- **add_purchase.php**: Registrar compra

**Características**:
- Gestión de materia prima (CRUD)
- Registrar compras de materia prima
- Stock de materia prima
- Costo promedio de materia prima

### 10.4 Flujo de Trabajo

#### Crear Producto Compuesto:
1. Crear producto, seleccionar tipo "Compuesto"
2. Agregar componentes:
   - Seleccionar materia prima
   - Ingresar cantidad necesaria
   - Agregar a lista
3. Recalcular costo:
   - Sistema suma: (cantidad * costo_actual_materia_prima) de cada componente
   - Se guarda en `calculated_cost_product`
4. Guardar producto

#### Comprar Materia Prima:
1. Ir a módulo de Materia Prima > Compras
2. Seleccionar materia prima
3. Ingresar cantidad comprada
4. Ingresar costo unitario
5. Registrar compra
6. Stock se actualiza automáticamente
7. Costo promedio se recalcula

#### Producir Producto Compuesto:
1. Crear producción de producto compuesto
2. Al completar producción:
   - Sistema verifica stock de materia prima necesaria
   - Si hay stock suficiente:
     - Descuenta materia prima del stock
     - Calcula costo real: suma de materia prima usada
     - Crea registro en almacén con costo calculado
   - Si no hay stock: error

---

## 11. RESUMEN DE CAMBIOS POR MÓDULO

### Módulo de Categorías
- ✅ Agregar subcategorías
- ✅ Modificar consultas para incluir jerarquía

### Módulo de Órdenes
- ✅ Tipos de orden: contado, crédito, consignación
- ✅ Comprobante de pago por transferencia
- ✅ Estados de confirmación de pago
- ✅ Descuento a venta total

### Módulo de Clientes
- ✅ Tipos: común y distribuidor
- ✅ Descuento para distribuidor

### Módulo de Gastos (NUEVO)
- ✅ Gastos en caja
- ✅ Gastos en orden
- ✅ Tipos de gastos configurables

### Módulo de Producción (NUEVO)
- ✅ Registro de producciones
- ✅ Estados de producción
- ✅ Cálculo de costos

### Módulo de Almacén (NUEVO)
- ✅ Gestión de almacén central
- ✅ Salidas a sucursales
- ✅ Integración con compras

### Módulo de Compras
- ✅ Estados: pendiente y confirmado
- ✅ Confirmación de compras

### Módulo de Productos
- ✅ Productos simples y compuestos
- ✅ Cálculo de costo de compuestos

### Módulo de Materia Prima (NUEVO)
- ✅ Gestión de materia prima
- ✅ Compras de materia prima
- ✅ Stock de materia prima

---

## 12. FLUJO COMPLETO DEL SISTEMA ACTUALIZADO

### Flujo de Venta Normal (Contado):
1. Abrir POS
2. Validar caja abierta
3. Crear orden tipo "Contado"
4. Agregar productos (simples o compuestos)
5. Seleccionar cliente (común o distribuidor)
6. Aplicar descuentos (producto, distribuidor, total)
7. Agregar gastos a orden (opcional)
8. Seleccionar método de pago
9. Si es transferencia: subir comprobante
10. Procesar orden
11. Si transferencia sin comprobante: estado "Pendiente de Confirmación"
12. Administrador confirma pago
13. Orden completada, stock actualizado

### Flujo de Venta a Crédito:
1. Crear orden tipo "Crédito"
2. Configurar días de plazo
3. Marcar si permite cambio de producto
4. Agregar productos
5. Procesar orden
6. Estado: "En Crédito"
7. Administrador puede confirmar (actualiza stock)
8. Si permite cambio: se puede cambiar producto antes de confirmar

### Flujo de Venta en Consignación:
1. Crear orden tipo "Consignación"
2. Agregar productos
3. Procesar orden
4. Estado: "En Consignación"
5. Agregar abonos parciales
6. Registrar devoluciones de productos
7. Cuando se complete pago: orden completada
8. Stock se actualiza restando devoluciones

### Flujo de Producción:
1. Crear materia prima (si no existe)
2. Comprar materia prima (actualiza stock)
3. Crear producto compuesto:
   - Agregar componentes (materia prima + cantidades)
   - Calcular costo automático
4. Crear producción:
   - Seleccionar producto compuesto
   - Cantidad a producir
   - Fechas
5. Cambiar estado a "En Proceso"
6. Al completar:
   - Verificar stock de materia prima
   - Descontar materia prima
   - Calcular costo real
   - Crear registro en almacén
7. Desde almacén, hacer salida a sucursal:
   - Se crea compra automática en sucursal
   - Stock se actualiza en sucursal

### Flujo de Compra:
1. Registrar compra (estado: "Pendiente")
2. Stock NO se actualiza
3. Cuando producto llega físicamente
4. Confirmar compra
5. Estado: "Confirmado"
6. Stock se actualiza automáticamente

---

## 13. ARCHIVOS A CREAR/MODIFICAR

### Archivos Nuevos a Crear:

**Controladores**:
- `controllers/expenses.controller.php`
- `controllers/productions.controller.php`
- `controllers/warehouse.controller.php`
- `controllers/raw_materials.controller.php`
- `controllers/purchases.controller.php` (si no existe)

**AJAX**:
- `ajax/expenses.ajax.php`
- `ajax/productions.ajax.php`
- `ajax/warehouse.ajax.php`
- `ajax/raw_materials.ajax.php`
- `ajax/purchase.ajax.php` (si no existe)

**Vistas**:
- `views/pages/dynamic/custom/expenses/`
- `views/pages/dynamic/custom/productions/`
- `views/pages/dynamic/custom/warehouse/`
- `views/pages/dynamic/custom/raw_materials/`
- `views/pages/dynamic/custom/orders/modules/payments.php`
- `views/pages/dynamic/custom/orders/modules/returns.php`
- `views/pages/dynamic/custom/orders/modules/payment_confirmation.php`

### Archivos a Modificar:

**Controladores**:
- `controllers/orders.controller.php`
- `ajax/pos.ajax.php`
- `ajax/stock.ajax.php`

**Vistas**:
- `views/pages/dynamic/custom/products/` (agregar tipo compuesto)
- `views/pages/dynamic/custom/clients/` (agregar tipo distribuidor)
- `views/pages/dynamic/custom/orders/` (agregar tipos de orden)
- `views/pages/dynamic/custom/categories/` (agregar subcategorías)

---

## 14. CONSIDERACIONES TÉCNICAS

### Validaciones Necesarias:
1. **Stock de materia prima**: Verificar antes de completar producción
2. **Stock de productos**: Verificar antes de agregar a orden
3. **Caja abierta**: Validar antes de crear orden
4. **Comprobante de pago**: Validar formato de imagen
5. **Días de crédito**: Validar que sea número positivo
6. **Cantidades**: Validar que sean positivas

### Cálculos Automáticos:
1. **Costo de producto compuesto**: Suma de (cantidad_componente * costo_materia_prima)
2. **Descuento total**: Descuento productos + Descuento distribuidor + Descuento manual
3. **Total orden**: Subtotal - Descuentos + Impuestos + Gastos
4. **Stock materia prima**: Compras - Uso en producción
5. **Stock productos**: Compras + Producción - Ventas

### Integraciones:
1. **Producción → Almacén**: Automática al completar
2. **Almacén → Compras**: Automática al hacer salida
3. **Compras → Stock**: Solo cuando se confirma
4. **Producción → Materia Prima**: Descuento automático al completar

---

## 15. PRIORIDADES DE IMPLEMENTACIÓN

### Fase 1 (Fundamentos):
1. Subcategorías
2. Tipos de clientes (común/distribuidor)
3. Descuento a venta total
4. Estados en compras

### Fase 2 (Órdenes Avanzadas):
1. Tipos de órdenes (contado, crédito, consignación)
2. Comprobante de pago por transferencia
3. Abonos y devoluciones

### Fase 3 (Gastos):
1. Módulo de gastos
2. Tipos de gastos
3. Gastos en caja y orden

### Fase 4 (Producción):
1. Materia prima
2. Productos compuestos
3. Módulo de producción
4. Módulo de almacén

---

Este documento proporciona una guía completa para la implementación de todas las funcionalidades solicitadas. Cada sección detalla los cambios específicos necesarios en base de datos, controladores, vistas y flujos de trabajo.

