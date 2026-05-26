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
    "ALTER TABLE productions ADD COLUMN yield_variance_pct DOUBLE DEFAULT NULL"
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
