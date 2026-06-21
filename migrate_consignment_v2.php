<?php
require 'api.pos/load_env.php';
require 'api.pos/models/connection.php';
$db = Connection::connect();

$migrations = [
// 1. Add columns to consignments
"ALTER TABLE consignments ADD COLUMN IF NOT EXISTS id_client_consignment INT DEFAULT NULL",
"ALTER TABLE consignments ADD COLUMN IF NOT EXISTS total_consignment DECIMAL(10,2) DEFAULT 0",
"ALTER TABLE consignments ADD COLUMN IF NOT EXISTS paid_consignment DECIMAL(10,2) DEFAULT 0",
"ALTER TABLE consignments ADD COLUMN IF NOT EXISTS id_order_consignment INT DEFAULT NULL",

// 2. Create consignment_payments
"CREATE TABLE IF NOT EXISTS consignment_payments (
  id_payment            INT AUTO_INCREMENT PRIMARY KEY,
  id_consignment        INT NOT NULL,
  amount_payment        DECIMAL(10,2) NOT NULL,
  method_payment        VARCHAR(30) DEFAULT 'efectivo',
  reference_payment     VARCHAR(255) DEFAULT NULL,
  file_payment          VARCHAR(255) DEFAULT NULL,
  id_admin_payment      INT DEFAULT NULL,
  notes_payment         TEXT DEFAULT NULL,
  date_created_payment  DATE DEFAULT (CURDATE()),
  INDEX idx_cp_consign (id_consignment)
)",

// 3. Create consignment_replacements
"CREATE TABLE IF NOT EXISTS consignment_replacements (
  id_replacement            INT AUTO_INCREMENT PRIMARY KEY,
  id_consignment            INT NOT NULL,
  id_item_out               INT NOT NULL,
  id_item_in                INT NOT NULL,
  qty_replacement           INT NOT NULL,
  notes_replacement         TEXT DEFAULT NULL,
  id_admin_replacement      INT DEFAULT NULL,
  date_created_replacement  DATE DEFAULT (CURDATE()),
  INDEX idx_cr_consign (id_consignment)
)",
];

foreach ($migrations as $sql) {
    try {
        $db->exec($sql);
        $preview = substr(trim($sql), 0, 60);
        echo "OK: $preview...\n";
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "SQL: " . substr($sql, 0, 100) . "\n";
    }
}
echo "\nDone.\n";
