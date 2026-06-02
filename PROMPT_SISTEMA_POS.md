# PROMPT DESCRIPTIVO: Sistema POS (Point of Sale)

## DESCRIPCIÓN GENERAL

Sistema POS completo desarrollado en PHP que permite gestionar ventas, inventario, clientes, órdenes y reportes para negocios con múltiples sucursales. El sistema incluye integración con facturación electrónica, gestión de caja, módulos personalizables y un dashboard administrativo.

---

## MÓDULOS DEL SISTEMA

---

## MÓDULO 1: AUTENTICACIÓN Y SEGURIDAD

### Funcionalidades

- **Login de administradores**
  - Autenticación mediante email y contraseña
  - Validación de credenciales
  - Manejo de sesiones

- **Gestión de usuarios y roles**
  - **Superadmin**: Acceso completo al sistema, gestión de todas las sucursales, creación de páginas y módulos
  - **Administrador de Sucursal**: Acceso limitado a su sucursal, gestión de productos, clientes y ventas

- **Gestión de perfiles**
  - Actualización de datos personales
  - Cambio de contraseñas
  - Configuración de preferencias
  - Personalización de interfaz (colores, fuentes, símbolos)

- **Recuperación de contraseña**
  - Sistema de restablecimiento vía correo electrónico
  - Generación automática de contraseñas temporales
  - Envío de credenciales por email

- **Código de seguridad**
  - Sistema opcional de doble autenticación
  - Envío de código de seguridad por email
  - Validación de código antes de acceso

- **Configuración avanzada**
  - Integración opcional con ChatGPT para funcionalidades de IA
  - Configuración de tokens y organización

### Seguridad

- Encriptación de contraseñas con crypt() y salt
- Validación de tokens en cada operación
- Sanitización de datos de entrada
- Control de acceso por sesión
- Validación de permisos por rol

---

## MÓDULO 2: PUNTO DE VENTA (POS)

### Funcionalidades

- **Creación y gestión de órdenes**
  - Generación de nuevas órdenes de venta
  - Número de transacción único (12 dígitos)
  - Validación de caja abierta antes de crear órdenes
  - Continuar con órdenes existentes

- **Gestión de carrito de compras**
  - Agregar productos a la orden
  - Modificar cantidades (con validación de stock)
  - Eliminar productos individuales
  - Limpiar carrito completo
  - Validación de stock antes de agregar

- **Búsqueda y selección de productos**
  - Búsqueda por nombre, SKU, código de barras o unidad
  - Filtrado por categorías
  - Paginación de resultados
  - Búsqueda en múltiples campos simultáneamente

- **Visualización de productos**
  - Tarjetas con imágenes de productos
  - Indicadores de stock con colores:
    - Stock bajo (< 50 unidades): color marrón
    - Stock medio (50-99 unidades): color índigo
    - Stock alto (≥ 100 unidades): color verde
  - Precios con descuentos aplicados
  - Badges de ofertas y descuentos
  - SKU visible en cada producto

- **Cálculo automático de totales**
  - Subtotal de productos
  - Aplicación de descuentos
  - Cálculo de impuestos (IVA)
  - Total general de la orden
  - Actualización en tiempo real

- **Gestión de clientes en POS**
  - Asignación de cliente a la orden (opcional)
  - Búsqueda de clientes existentes
  - Creación rápida de nuevos clientes desde POS
  - Selección de "Consumidor Final"

- **Procesamiento de pagos**
  - Selección de método de pago (efectivo, transferencia, etc.)
  - Registro de transferencia (opcional)
  - Validación de facturación electrónica (si aplica)
  - Procesamiento y finalización de orden

### Flujo de Venta

1. Usuario inicia sesión
2. Validación de caja abierta del día
3. Creación de nueva orden o continuación de existente
4. Búsqueda y selección de productos
5. Agregar productos al carrito con cantidades
6. Asignación de cliente (opcional)
7. Cálculo automático de totales
8. Selección de método de pago
9. Procesamiento de orden:
   - Validación de facturación (si aplica)
   - Actualización de estado a "Completada"
   - Actualización de stock de productos
   - Generación de factura (si aplica)
   - Impresión de ticket (opcional)
10. Reinicio del POS para nueva venta

---

## MÓDULO 3: GESTIÓN DE PRODUCTOS E INVENTARIO

### Funcionalidades de Productos

- **CRUD completo de productos**
  - Crear nuevos productos
  - Leer y visualizar productos
  - Actualizar información de productos
  - Eliminar productos

- **Información del producto**
  - Título del producto
  - SKU (código único)
  - Código de barras
  - Categoría asignada
  - Precio de compra
  - Precio de venta
  - Stock disponible
  - Imágenes del producto
  - Unidad de medida
  - Descuentos aplicables
  - Impuestos (IVA) - tipo y porcentaje
  - Estado (activo/inactivo)

- **Asignación por sucursal**
  - Productos asociados a oficinas específicas
  - Filtrado automático por sucursal del usuario
  - Gestión independiente por sucursal

- **Búsqueda avanzada**
  - Búsqueda en múltiples campos simultáneamente
  - Filtrado por categoría
  - Ordenamiento por diferentes criterios

### Funcionalidades de Inventario/Stock

- **Cálculo automático de stock**
  - Fórmula: Stock = Compras - Ventas
  - Actualización automática al procesar compras y ventas
  - Actualización masiva de todos los productos de una sucursal

- **Indicadores visuales de stock**
  - Stock bajo (< 50 unidades): color marrón
  - Stock medio (50-99 unidades): color índigo
  - Stock alto (≥ 100 unidades): color verde
  - Indicadores en tarjetas de productos y listados

- **Control de disponibilidad**
  - Validación de stock antes de agregar productos a órdenes
  - Prevención de ventas sin stock disponible
  - Alertas de stock insuficiente

- **Gestión automática de estado**
  - Productos con stock > 0: estado activo (1)
  - Productos con stock ≤ 0: estado inactivo (0)
  - Actualización automática del estado

### Flujo de Actualización de Stock

1. Obtención de todos los productos de la sucursal
2. Para cada producto:
   - Suma de todas las compras (cantidad total comprada)
   - Suma de todas las ventas (cantidad total vendida)
   - Cálculo: Stock = Compras - Ventas
   - Actualización del stock en base de datos
   - Si stock > 0: estado = activo (1)
   - Si stock ≤ 0: estado = inactivo (0)

---

## MÓDULO 4: GESTIÓN DE CLIENTES

### Funcionalidades

- **CRUD completo de clientes**
  - Crear nuevos clientes
  - Leer y visualizar información de clientes
  - Actualizar datos de clientes
  - Eliminar clientes

- **Información del cliente**
  - Nombre
  - Apellido
  - DNI/NIT (documento de identidad)
  - Email
  - Teléfono
  - Dirección
  - Fecha de registro

- **Asignación por sucursal**
  - Clientes asociados a oficinas específicas
  - Filtrado automático por sucursal del usuario
  - Gestión independiente por sucursal

- **Integración con POS**
  - Creación rápida de clientes desde el punto de venta
  - Búsqueda de clientes durante el proceso de venta
  - Asignación de cliente a órdenes

- **Historial de compras**
  - Seguimiento de órdenes por cliente
  - Visualización de compras anteriores
  - Análisis de comportamiento de compra

---

## MÓDULO 5: GESTIÓN DE COMPRAS

### Funcionalidades

- **Registro de compras**
  - Ingreso de productos al inventario mediante compras
  - Registro de compras de proveedores
  - Control de entrada de mercancía

- **Información de compra**
  - Producto comprado
  - Cantidad adquirida
  - Precio de compra unitario
  - Fecha de compra
  - Relación con producto y sucursal

- **Actualización automática de stock**
  - Incremento automático del stock al registrar compras
  - Actualización en tiempo real del inventario
  - Recalculo de estado del producto (activo/inactivo)

- **Gestión de proveedores**
  - Registro de información de proveedores (implícito)
  - Historial de compras por producto

---

## MÓDULO 6: GESTIÓN DE VENTAS Y ÓRDENES

### Funcionalidades

- **Estados de orden**
  - **Pendiente**: Orden creada pero no completada
  - **Completada**: Orden finalizada y pagada

- **Estados de venta**
  - **Pendiente**: Venta registrada pero orden no completada
  - **Completada**: Venta finalizada y procesada

- **Procesamiento de órdenes**
  - Validación de facturación electrónica (opcional)
  - Actualización de estado a "Completada"
  - Actualización de todas las ventas asociadas
  - Generación de factura electrónica (si aplica)
  - Impresión de tickets (opcional)
  - Actualización de stock de productos vendidos

- **Información de venta**
  - Producto vendido
  - Cantidad vendida
  - Precio unitario
  - Tipo de impuesto (IVA)
  - Porcentaje de impuesto
  - Descuento aplicado
  - Subtotal de la venta
  - Cliente asociado
  - Vendedor (administrador)
  - Sucursal
  - Fecha de venta
  - Estado de la venta

- **Información de orden**
  - Número de transacción único
  - Cliente asociado (opcional)
  - Vendedor
  - Sucursal
  - Subtotal de la orden
  - Descuento total
  - Impuesto total
  - Total de la orden
  - Método de pago
  - Transferencia (si aplica)
  - Estado de la orden
  - Fecha de creación
  - Lista de productos vendidos

- **Control de eliminación**
  - Solo se pueden eliminar órdenes en estado "Pendiente"
  - Solo se pueden eliminar ventas en estado "Pendiente"
  - Protección de datos de órdenes completadas

---

## MÓDULO 7: GESTIÓN DE SUCURSALES Y OFICINAS

### Funcionalidades

- **CRUD completo de sucursales**
  - Crear nuevas sucursales
  - Leer y visualizar información de sucursales
  - Actualizar datos de sucursales
  - Eliminar sucursales

- **Información de sucursal**
  - Nombre/título de la sucursal
  - Dirección física
  - Teléfono de contacto
  - NIT/DNI de la sucursal
  - Estado (activa/inactiva)
  - Fecha de creación

- **Asignación de usuarios**
  - Administradores asociados a sucursales específicas
  - Un administrador puede estar asignado a una sucursal
  - Superadmin puede acceder a todas las sucursales (id_office = 0)

- **Filtrado por sucursal**
  - Los usuarios solo ven datos de su sucursal asignada
  - Excepción: Superadmin ve datos de todas las sucursales
  - Productos, clientes, ventas y órdenes filtrados por sucursal

- **Gestión multi-sucursal**
  - Operación independiente por sucursal
  - Inventario separado por sucursal
  - Reportes por sucursal o consolidados

---

## MÓDULO 8: SISTEMA DE CAJA

### Funcionalidades

- **Apertura de caja**
  - Validación de caja abierta del día actual
  - Una caja por día por sucursal
  - Estado de caja: Abierta (1) o Cerrada (0)

- **Cierre de caja**
  - Validación de cierre de caja del día anterior
  - Prevención de apertura de nueva caja sin cerrar la anterior
  - Control de flujo de caja diario

- **Control de caja diaria**
  - Una caja por día por sucursal
  - Fecha de creación de la caja
  - Estado de la caja (abierta/cerrada)
  - Relación con sucursal

- **Validación de órdenes**
  - No se pueden crear órdenes sin caja abierta
  - Verificación automática antes de crear orden
  - Mensaje de error si no hay caja abierta

- **Integración con ventas**
  - Todas las ventas se asocian a la caja del día
  - Control de ingresos por día
  - Trazabilidad de transacciones

---

## MÓDULO 9: REPORTES Y ANÁLISIS

### Funcionalidades

- **Reportes de órdenes**
  - Filtrado por rango de fechas
  - Filtrado por sucursal (automático según usuario)
  - Visualización de órdenes completadas y pendientes
  - Información incluida:
    - Número de transacción
    - Cliente
    - Fecha de creación
    - Método de pago
    - Estado
    - Subtotal, descuento, impuesto, total
  - Exportación a Excel (.xls)

- **Reportes de ventas**
  - Filtrado por rango de fechas
  - Filtrado por sucursal (automático según usuario)
  - Visualización de ventas completadas y pendientes
  - Información incluida:
    - Producto vendido
    - Cantidad
    - Precio unitario
    - IVA (%)
    - Descuento (%)
    - Subtotal
    - Estado
    - Fecha de venta
  - Exportación a Excel (.xls)

- **Generación de PDFs**
  - Comprobantes de orden
  - Facturas electrónicas
  - Tickets de venta
  - Formato profesional con información completa

- **Filtros avanzados**
  - Selección de rango de fechas
  - Filtrado por sucursal (para superadmin)
  - Filtrado por estado
  - Filtrado por método de pago

---

## MÓDULO 10: DASHBOARD Y MÉTRICAS

### Funcionalidades

- **Métricas en tiempo real**
  - Ventas del día
  - Órdenes pendientes
  - Productos con stock bajo
  - Total de clientes
  - Otros KPIs configurables

- **Gráficos interactivos**
  - Visualización de datos con Chart.js
  - Gráficos de barras
  - Gráficos de líneas
  - Gráficos circulares
  - Actualización en tiempo real

- **Navegación personalizada**
  - Menú lateral dinámico
  - Breadcrumbs jerárquicos
  - Accesos rápidos a funciones principales

- **Vista consolidada**
  - Resumen ejecutivo del negocio
  - Indicadores clave de rendimiento
  - Alertas y notificaciones importantes

---

## MÓDULO 11: ADMINISTRACIÓN DE CONTENIDO

### Submódulo 11.1: Gestión de Páginas

- **Creación de páginas**
  - Páginas personalizables con módulos
  - Asignación de múltiples módulos por página
  - Configuración de rutas y navegación

- **Gestión de contenido**
  - Asignación de módulos a páginas
  - Ordenamiento de módulos
  - Configuración de ancho y layout

- **Sistema de navegación**
  - Menú dinámico basado en páginas
  - Rutas personalizables
  - Breadcrumbs automáticos

### Submódulo 11.2: Gestión de Módulos

- **Tipos de módulos**
  - **Breadcrumbs**: Navegación jerárquica
  - **Métricas**: Indicadores numéricos (KPIs)
  - **Gráficos**: Visualizaciones de datos (Chart.js)
  - **Tablas**: Tablas dinámicas con CRUD completo
  - **Personalizables**: Módulos con código PHP personalizado

- **Gestión de módulos**
  - Crear nuevos módulos
  - Editar módulos existentes
  - Eliminar módulos
  - Configurar ancho y editabilidad
  - Asignar a páginas específicas

- **Módulos tipo tabla**
  - Creación dinámica de tablas en MySQL
  - Gestión de columnas (agregar, editar, eliminar)
  - Tipos de datos configurables (texto, número, fecha, etc.)
  - Visibilidad de columnas
  - Matrices de datos para selects
  - Relaciones con otras tablas

- **Módulos personalizables**
  - Creación de carpetas y archivos PHP personalizados
  - Total libertad de programación
  - Integración con el sistema base

### Submódulo 11.3: Formularios Dinámicos

- **Generación automática**
  - Formularios generados desde estructura de tablas
  - Detección automática de tipos de campos
  - Validación automática

- **Validación**
  - Validación de campos en frontend
  - Validación de campos en backend
  - Mensajes de error personalizados

- **Tipos de campos**
  - Texto
  - Número
  - Fecha
  - Select (con opciones)
  - Textarea
  - Archivo
  - Checkbox
  - Radio

- **Integración con ChatGPT**
  - Generación de consultas SQL mediante IA (opcional)
  - Asistencia en creación de formularios complejos
  - Sugerencias de estructura de datos

### Submódulo 11.4: Tablas Dinámicas

- **CRUD completo**
  - Crear nuevos registros
  - Leer y visualizar registros
  - Actualizar registros existentes
  - Eliminar registros

- **Funcionalidades avanzadas**
  - Paginación de resultados
  - Búsqueda en tiempo real
  - Ordenamiento por columnas
  - Filtrado avanzado de datos
  - Exportación de datos a Excel

- **Personalización**
  - Configuración de columnas visibles
  - Formato de datos personalizable
  - Acciones personalizadas por fila

---

## MÓDULO 12: GESTIÓN DE ARCHIVOS Y MULTIMEDIA

### Funcionalidades

- **Gestión de archivos**
  - Subida de archivos al sistema
  - Organización por tipo de archivo
  - Almacenamiento seguro

- **Visualización de archivos**
  - **Imágenes**: Visualización directa (PNG, JPG, JPEG, etc.)
  - **PDFs**: Visualización y descarga
  - **Videos**: Reproducción de videos
  - **Archivos ZIP**: Descarga y gestión

- **Organización**
  - Clasificación automática por tipo
  - Iconos según tipo de archivo
  - Búsqueda de archivos

- **Integración con productos**
  - Asociación de imágenes a productos
  - Galería de imágenes por producto
  - Gestión de archivos relacionados

---

## MÓDULO 13: INSTALACIÓN Y CONFIGURACIÓN

### Funcionalidades

- **Instalador del sistema**
  - Proceso de instalación inicial
  - Guía paso a paso
  - Validación de requisitos

- **Configuración de base de datos**
  - Conexión a MySQL
  - Creación automática de tablas
  - Inicialización de datos base

- **Configuración inicial**
  - Setup de administrador principal (superadmin)
  - Configuración de primera sucursal
  - Configuración de parámetros del sistema

- **Verificación de instalación**
  - Validación de conexión a BD
  - Verificación de permisos de archivos
  - Comprobación de extensiones PHP requeridas

---

## CARACTERÍSTICAS TÉCNICAS

### Tecnologías Utilizadas

- **Backend**: PHP
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Base de datos**: MySQL
- **Librerías**:
  - Bootstrap 5 (UI framework)
  - Chart.js (gráficos)
  - DomPDF (generación de PDFs)
  - PHPMailer (envío de correos)
  - SweetAlert2 (alertas)
  - Toastr (notificaciones)
  - Select2 (selects avanzados)
  - Summernote (editor WYSIWYG)
  - Moment.js (manejo de fechas)
  - DateRangePicker (selección de rangos de fechas)

### Arquitectura

- **Patrón MVC**: Separación de controladores, modelos y vistas
- **API REST**: Comunicación mediante peticiones HTTP (GET, POST, PUT, DELETE)
- **AJAX**: Operaciones asíncronas sin recargar página
- **Sesiones**: Manejo de sesiones de usuario
- **Tokens**: Autenticación mediante tokens

### Seguridad

- Encriptación de contraseñas con crypt() y salt
- Validación de tokens en cada operación
- Sanitización de datos de entrada
- Validación de sesiones
- Control de acceso por rol

---

## PERMISOS Y ROLES

### Superadmin

- Acceso completo al sistema
- Gestión de todas las sucursales
- Creación y edición de páginas y módulos
- Configuración global del sistema
- Visualización de reportes de todas las sucursales
- Personalización de interfaz (colores, fuentes, símbolos)

### Administrador de Sucursal

- Acceso limitado a su sucursal asignada
- Gestión de productos, clientes, ventas de su sucursal
- Uso del POS
- Visualización de reportes de su sucursal
- Gestión de su perfil
- No puede crear páginas o módulos
- No puede gestionar otras sucursales

---

## INTEGRACIONES

- **Título Valor**: Facturación electrónica colombiana
  - Generación de facturas electrónicas
  - Validación de calidad y resolución
  - Generación de CUDE y XML

- **DIAN**: Validación de documentos electrónicos
  - Enlaces de validación de facturas
  - Consulta de documentos en portal DIAN

- **ChatGPT**: Generación de consultas SQL mediante IA (opcional)
  - Asistencia en creación de formularios
  - Generación de consultas complejas
  - Sugerencias de estructura de datos

- **PHPMailer**: Envío de correos electrónicos
  - Recuperación de contraseñas
  - Códigos de seguridad
  - Notificaciones del sistema

---

## NOTAS ADICIONALES

- El sistema está diseñado para operar con múltiples sucursales de forma independiente
- Soporta facturación electrónica según normativa colombiana (DIAN)
- Sistema modular que permite extender funcionalidades fácilmente
- Interfaz responsive y moderna, adaptable a diferentes dispositivos
- Sistema de notificaciones en tiempo real (Toastr, SweetAlert)
- Manejo robusto de errores y validaciones en frontend y backend
- Cálculo automático de stock basado en compras y ventas
- Control de caja diario para trazabilidad financiera
- Exportación de reportes a Excel para análisis externo
- Generación de PDFs para comprobantes y facturas
