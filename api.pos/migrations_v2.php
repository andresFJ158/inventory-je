<?php
require_once __DIR__."/models/connection.php";

$db = Connection::connect();

$queries = [
    "ALTER TABLE offices ADD COLUMN type_office TEXT NULL DEFAULT 'sucursal'",
    
    "ALTER TABLE products ADD COLUMN is_compound_product INT DEFAULT 0",
    "ALTER TABLE products ADD COLUMN origin_office_product INT DEFAULT 0",
    
    "ALTER TABLE purchases ADD COLUMN type_purchase TEXT NULL DEFAULT 'compra'",
    "ALTER TABLE purchases ADD COLUMN status_purchase TEXT NULL DEFAULT 'pendiente'",
    "ALTER TABLE purchases ADD COLUMN received_date_purchase DATE NULL",
    "ALTER TABLE purchases ADD COLUMN received_by_purchase INT NULL",
    "ALTER TABLE purchases ADD COLUMN may_product DOUBLE DEFAULT 0",
    "ALTER TABLE purchases ADD COLUMN wholesale_quantity INT DEFAULT 0",
    
    "ALTER TABLE raw_materials ADD COLUMN measure_type ENUM('weight','volume','unit') DEFAULT 'unit'",
    "ALTER TABLE raw_material_entries ADD COLUMN type_entry VARCHAR(50) DEFAULT 'ingreso'",
    "ALTER TABLE raw_material_entries ADD COLUMN concept_entry TEXT NULL",
    
    "CREATE TABLE IF NOT EXISTS raw_materials (
      id_raw_material           INT AUTO_INCREMENT PRIMARY KEY,
      name_raw_material         TEXT NOT NULL,
      unit_raw_material         TEXT NOT NULL,
      description_raw_material  TEXT NULL,
      stock_raw_material        DOUBLE DEFAULT 0,
      id_office_raw_material    INT NOT NULL,
      id_admin_raw_material     INT NOT NULL,
      status_raw_material       INT DEFAULT 1,
      date_created_raw_material DATE NULL,
      date_updated_raw_material TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS raw_material_entries (
      id_entry                INT AUTO_INCREMENT PRIMARY KEY,
      id_raw_material_entry   INT NOT NULL,
      qty_entry               DOUBLE NOT NULL,
      unit_price_entry        DOUBLE DEFAULT 0,
      total_cost_entry        DOUBLE DEFAULT 0,
      lot_number_entry        TEXT NULL,
      supplier_entry          TEXT NULL,
      notes_entry             TEXT NULL,
      status_entry            TEXT DEFAULT 'pendiente',
      id_admin_entry          INT NOT NULL,
      id_approved_by_entry    INT NULL,
      date_entry              DATE NULL,
      date_approved_entry     DATE NULL,
      date_created_entry      DATE NULL,
      date_updated_entry      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS indirect_cost_types (
      id_indirect_type          INT AUTO_INCREMENT PRIMARY KEY,
      name_indirect_type        TEXT NOT NULL,
      description_indirect_type TEXT NULL,
      id_office_indirect_type   INT NOT NULL,
      status_indirect_type      INT DEFAULT 1,
      date_created_indirect_type DATE NULL,
      date_updated_indirect_type TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS recipes (
      id_recipe             INT AUTO_INCREMENT PRIMARY KEY,
      id_product_recipe     INT NOT NULL UNIQUE,
      batch_size_recipe     DOUBLE NOT NULL,
      unit_batch_recipe     TEXT NULL,
      notes_recipe          TEXT NULL,
      id_office_recipe      INT NOT NULL,
      id_admin_recipe       INT NOT NULL,
      date_created_recipe   DATE NULL,
      date_updated_recipe   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS recipe_ingredients (
      id_ingredient              INT AUTO_INCREMENT PRIMARY KEY,
      id_recipe_ingredient       INT NOT NULL,
      id_raw_material_ingredient INT NOT NULL,
      qty_ingredient             DOUBLE NOT NULL,
      notes_ingredient           TEXT NULL,
      date_created_ingredient    DATE NULL,
      date_updated_ingredient    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS recipe_labor (
      id_labor            INT AUTO_INCREMENT PRIMARY KEY,
      id_recipe_labor     INT NOT NULL,
      description_labor   TEXT NOT NULL,
      type_labor          TEXT DEFAULT 'fixed',
      hours_labor         DOUBLE DEFAULT 0,
      cost_per_hour_labor DOUBLE DEFAULT 0,
      fixed_cost_labor    DOUBLE DEFAULT 0,
      total_cost_labor    DOUBLE DEFAULT 0,
      date_created_labor  DATE NULL,
      date_updated_labor  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS recipe_indirect_costs (
      id_recipe_indirect        INT AUTO_INCREMENT PRIMARY KEY,
      id_recipe_indirect_recipe INT NOT NULL,
      id_type_indirect          INT NOT NULL,
      amount_per_batch_indirect DOUBLE NOT NULL,
      date_created_indirect     DATE NULL,
      date_updated_indirect     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS productions (
      id_production              INT AUTO_INCREMENT PRIMARY KEY,
      id_recipe_production       INT NOT NULL,
      id_product_production      INT NOT NULL,
      batches_production         DOUBLE NOT NULL,
      total_qty_production       DOUBLE NOT NULL,
      proj_materials_cost        DOUBLE DEFAULT 0,
      proj_labor_cost            DOUBLE DEFAULT 0,
      proj_indirect_cost         DOUBLE DEFAULT 0,
      proj_total_cost            DOUBLE DEFAULT 0,
      proj_unit_cost             DOUBLE DEFAULT 0,
      real_materials_cost        DOUBLE DEFAULT 0,
      real_labor_cost            DOUBLE DEFAULT 0,
      real_indirect_cost         DOUBLE DEFAULT 0,
      real_total_cost            DOUBLE DEFAULT 0,
      real_unit_cost             DOUBLE DEFAULT 0,
      status_production          TEXT DEFAULT 'pendiente',
      start_date_production      DATE NULL,
      end_date_production        DATE NULL,
      notes_production           TEXT NULL,
      id_admin_production        INT NOT NULL,
      id_office_production       INT NOT NULL,
      date_created_production    DATE NULL,
      date_updated_production    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS production_material_costs (
      id_prod_material_cost         INT AUTO_INCREMENT PRIMARY KEY,
      id_production_mat_cost        INT NOT NULL,
      id_raw_material_mat_cost      INT NOT NULL,
      id_entry_used_mat_cost        INT NOT NULL,
      qty_used_mat_cost             DOUBLE NOT NULL,
      unit_price_at_production      DOUBLE NOT NULL,
      total_cost_mat_cost           DOUBLE NOT NULL
    )",
    "ALTER TABLE productions ADD COLUMN id_packaged_product INT DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN pkg_labor_cost DOUBLE DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN pkg_indirect_cost DOUBLE DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN real_bulk_qty DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN yield_variance DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN yield_variance_pct DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN qty_packaged_production DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN qty_approved_production DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN qty_rejected_production DOUBLE DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN result_qc_production VARCHAR(30) DEFAULT NULL",
    "ALTER TABLE productions ADD COLUMN notes_qc_production TEXT DEFAULT NULL",
    "ALTER TABLE suppliers ADD COLUMN status_supplier INT DEFAULT 1",
    "ALTER TABLE suppliers ADD COLUMN type_supplier VARCHAR(50) DEFAULT 'ambos'",
    "ALTER TABLE clients ADD COLUMN nit_client VARCHAR(50) DEFAULT NULL",
    "CREATE TABLE IF NOT EXISTS `credits` (
      `id_credit` int(11) NOT NULL AUTO_INCREMENT,
      `id_client_credit` int(11) NOT NULL,
      `id_office_credit` int(11) NOT NULL,
      `id_admin_credit` int(11) NOT NULL,
      `amount_credit` double NOT NULL DEFAULT 0,
      `balance_credit` double NOT NULL DEFAULT 0,
      `due_date_credit` date DEFAULT NULL,
      `notes_credit` text DEFAULT NULL,
      `status_credit` varchar(50) DEFAULT 'activo',
      `date_created_credit` date DEFAULT NULL,
      `date_updated_credit` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id_credit`),
      KEY `idx_credits_client` (`id_client_credit`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `credit_payments` (
      `id_payment` int(11) NOT NULL AUTO_INCREMENT,
      `id_credit_payment` int(11) NOT NULL,
      `amount_payment` double NOT NULL DEFAULT 0,
      `method_payment` varchar(50) DEFAULT 'efectivo',
      `reference_payment` varchar(255) DEFAULT NULL,
      `file_payment` varchar(255) DEFAULT NULL,
      `id_admin_payment` int(11) NOT NULL DEFAULT 0,
      `date_created_payment` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id_payment`),
      KEY `idx_creditpay_credit` (`id_credit_payment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `consignments` (
      `id_consignment` int(11) NOT NULL AUTO_INCREMENT,
      `id_admin_consignment` int(11) NOT NULL,
      `id_office_consignment` int(11) NOT NULL,
      `notes_consignment` text DEFAULT NULL,
      `date_created_consignment` date DEFAULT NULL,
      `status_consignment` varchar(50) DEFAULT 'pendiente',
      `file_consignment` varchar(255) DEFAULT NULL,
      `reference_consignment` varchar(255) DEFAULT NULL,
      `date_updated_consignment` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id_consignment`),
      KEY `idx_consign_office` (`id_office_consignment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `consignment_items` (
      `id_consignment_item` int(11) NOT NULL AUTO_INCREMENT,
      `id_consignment` int(11) NOT NULL,
      `id_product_consignment` int(11) NOT NULL,
      `qty_assigned` int(11) NOT NULL DEFAULT 0,
      `price_consignment` double NOT NULL DEFAULT 0,
      `qty_sold` int(11) DEFAULT 0,
      `qty_returned` int(11) DEFAULT 0,
      `date_created_item` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id_consignment_item`),
      KEY `idx_consignit_cons` (`id_consignment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `invoices` (
      `id_invoice` int(11) NOT NULL AUTO_INCREMENT,
      `id_order_invoice` int(11) NOT NULL,
      `nit_invoice` varchar(50) DEFAULT NULL,
      `subtotal_invoice` double NOT NULL DEFAULT 0,
      `total_invoice` double NOT NULL DEFAULT 0,
      `status_invoice` varchar(50) DEFAULT 'pendiente',
      `id_office_invoice` int(11) NOT NULL,
      `date_created_invoice` date DEFAULT NULL,
      `invoice_number` varchar(50) DEFAULT NULL,
      `client_name_invoice` varchar(255) DEFAULT NULL,
      `date_updated_invoice` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id_invoice`),
      KEY `idx_invoices_order` (`id_order_invoice`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "ALTER TABLE admins ADD COLUMN surname_admin TEXT NULL DEFAULT NULL",
    "ALTER TABLE admins ADD COLUMN img_admin TEXT NULL DEFAULT NULL",
    
    "ALTER TABLE admins ADD COLUMN type_seller VARCHAR(50) DEFAULT 'cajero'",
    "ALTER TABLE products ADD COLUMN initial_stock_product DOUBLE DEFAULT 0",
    "ALTER TABLE cashs ADD COLUMN cash_efectivo DOUBLE DEFAULT 0",
    "ALTER TABLE cashs ADD COLUMN cash_qr DOUBLE DEFAULT 0",
    "ALTER TABLE orders ADD COLUMN qr_ref_order VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN method_detail_order TEXT DEFAULT NULL",
    
    "ALTER TABLE raw_materials ADD COLUMN no_stock_raw_material INT DEFAULT 0",
    "ALTER TABLE raw_materials ADD COLUMN price_raw_material DOUBLE DEFAULT 0",
    
    "CREATE TABLE IF NOT EXISTS lab_supplies (
      id_supply              INT AUTO_INCREMENT PRIMARY KEY,
      name_supply            TEXT NOT NULL,
      unit_supply            VARCHAR(20) NOT NULL,
      stock_supply           DOUBLE DEFAULT 0,
      price_supply           DOUBLE DEFAULT 0,
      id_office_supply       INT NOT NULL,
      id_supplier_supply     INT DEFAULT NULL,
      status_supply          INT DEFAULT 1,
      date_created_supply    DATE NULL,
      date_updated_supply    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS waste_packaged (
      id_waste               INT AUTO_INCREMENT PRIMARY KEY,
      id_production_waste    INT NOT NULL,
      id_product_waste       INT NOT NULL,
      qty_waste              DOUBLE NOT NULL,
      id_office_waste        INT NOT NULL,
      status_waste           VARCHAR(50) DEFAULT 'en_almacen',
      concept_waste          TEXT NULL,
      id_admin_waste         INT NOT NULL,
      notes_waste            TEXT NULL,
      date_created_waste     DATE NULL,
      date_updated_waste     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "ALTER TABLE productions ADD COLUMN waste_qty_production DOUBLE DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN waste_packaged_qty DOUBLE DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN waste_loss_qty DOUBLE DEFAULT 0",
    "ALTER TABLE productions ADD COLUMN pkg_name_production TEXT DEFAULT NULL",

    "ALTER TABLE admins ADD COLUMN id_inventory_admin INT DEFAULT NULL",
    "ALTER TABLE admins ADD COLUMN pct_commission_admin DOUBLE DEFAULT 0",
    "CREATE TABLE IF NOT EXISTS stock_transfers (
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
    )",
    "CREATE TABLE IF NOT EXISTS combo_items (
      id_combo_item       INT AUTO_INCREMENT PRIMARY KEY,
      id_combo_ci         INT NOT NULL,
      id_product_ci       INT NOT NULL,
      qty_ci              INT NOT NULL DEFAULT 1,
      date_created_ci     DATE NULL,
      date_updated_ci     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "ALTER TABLE products ADD COLUMN combo_price_mode VARCHAR(20) DEFAULT 'descuento'",
    "ALTER TABLE combo_items ADD COLUMN IF NOT EXISTS price_ci DOUBLE DEFAULT 0",
    "CREATE INDEX IF NOT EXISTS idx_combo_ci ON combo_items(id_combo_ci)",
    // Tablas del flujo vendedor → despacho
    "CREATE TABLE IF NOT EXISTS order_expenses (
      id_expense              INT AUTO_INCREMENT PRIMARY KEY,
      id_order_expense        INT NOT NULL,
      concept_expense         VARCHAR(255) DEFAULT NULL,
      amount_expense          DOUBLE DEFAULT 0,
      charge_to_client_expense INT DEFAULT 0,
      id_admin_expense        INT DEFAULT 0,
      date_created_expense    DATE DEFAULT NULL,
      KEY idx_order_expense (id_order_expense)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS sale_payments (
      id_sale_payment         INT AUTO_INCREMENT PRIMARY KEY,
      id_order_payment        INT NOT NULL,
      method_payment          VARCHAR(30) DEFAULT NULL,
      reference_payment       VARCHAR(255) DEFAULT NULL,
      file_payment            VARCHAR(255) DEFAULT NULL,
      amount_payment          DOUBLE DEFAULT 0,
      id_admin_payment        INT DEFAULT 0,
      date_created_payment    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      KEY idx_order_payment (id_order_payment)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "ALTER TABLE order_expenses ADD COLUMN charge_to_client_expense INT DEFAULT 0",

    // Corregir textos con tilde rota por encoding (mojibake) en datos existentes
    "UPDATE columns SET alias_column = 'Almacén' WHERE title_column = 'id_office_purchase'",
    "UPDATE sub_warehouses SET name_sub_warehouse = 'Sub-Almacén de la Sucursal' WHERE name_sub_warehouse LIKE 'Sub-Almac%n de la Sucursal'",

    // Gestión de cartera de clientes por vendedor
    "ALTER TABLE clients ADD COLUMN id_admin_client INT DEFAULT 0",
    "ALTER TABLE clients ADD COLUMN notes_client TEXT DEFAULT NULL",
    "INSERT IGNORE INTO columns (title_column, alias_column, type_column, visible_column, id_module_column) VALUES ('id_admin_client','Vendedor Asignado','text',0,6)",

    // Bitácora de auditoría (quién/qué/cuándo/desde dónde + antes/después)
    "CREATE TABLE IF NOT EXISTS `audit_logs` (
      `id_audit`          BIGINT NOT NULL AUTO_INCREMENT,
      `action_audit`      VARCHAR(10) NOT NULL,
      `table_audit`       VARCHAR(64) NOT NULL,
      `record_audit`      VARCHAR(64) DEFAULT NULL,
      `id_admin_audit`    INT DEFAULT NULL,
      `email_admin_audit` VARCHAR(255) DEFAULT NULL,
      `role_admin_audit`  VARCHAR(50) DEFAULT NULL,
      `changes_audit`     LONGTEXT DEFAULT NULL,
      `ip_audit`          VARCHAR(45) DEFAULT NULL,
      `endpoint_audit`    VARCHAR(255) DEFAULT NULL,
      `created_at_audit`  TIMESTAMP NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id_audit`),
      KEY `idx_audit_table` (`table_audit`,`record_audit`),
      KEY `idx_audit_admin` (`id_admin_audit`),
      KEY `idx_audit_date` (`created_at_audit`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

// Normalización de charset: unificar tablas legacy utf8mb3 → utf8mb4
// (utf8mb4 es superset; conversión sin pérdida y soporta emojis/4 bytes).
// Se ejecuta en un bucle tolerante: un fallo aquí no invalida la migración.
$charsetTables = [
    "admins", "bills", "cashs", "categories", "clients", "columns",
    "files", "folders", "incomes", "modules", "offices", "orders",
    "pages", "products", "sales"
];

$success = true;
foreach($queries as $query) {
    try {
        $db->exec($query);
        echo "Exito: " . substr($query, 0, 50) . "...\n";
    } catch (PDOException $e) {
        // Ignore "Duplicate column name"
        if ($e->getCode() == '42S21') {
            echo "Ignorado (ya existe): " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error en: $query\n";
            echo $e->getMessage() . "\n";
            $success = false;
        }
    }
}

// Normalización de charset (tolerante: no afecta $success)
foreach($charsetTables as $tbl) {
    $sql = "ALTER TABLE `$tbl` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    try {
        $db->exec($sql);
        echo "Charset utf8mb4: $tbl\n";
    } catch (PDOException $e) {
        // Tabla inexistente u otra incidencia no crítica: registrar y continuar
        echo "Charset omitido ($tbl): " . $e->getMessage() . "\n";
    }
}

if ($success) {
    echo "\nMigracion completada exitosamente.\n";
} else {
    echo "\nHubo errores durante la migracion.\n";
}
