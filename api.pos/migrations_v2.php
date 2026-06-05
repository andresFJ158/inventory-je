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
    "ALTER TABLE productions ADD COLUMN waste_loss_qty DOUBLE DEFAULT 0"
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

if ($success) {
    echo "\nMigracion completada exitosamente.\n";
} else {
    echo "\nHubo errores durante la migracion.\n";
}
