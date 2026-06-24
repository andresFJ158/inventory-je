<?php
$dsn = "mysql:host=127.0.0.1;port=3307;dbname=u228744577_pos;charset=utf8mb4";
$db = new PDO($dsn, "root", "root", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$queries = [
    "ALTER TABLE consignments ADD COLUMN id_client_consignment INT DEFAULT 0",
    "ALTER TABLE consignments ADD COLUMN total_consignment DOUBLE DEFAULT 0",
    "ALTER TABLE consignments ADD COLUMN paid_consignment DOUBLE DEFAULT 0",
    "ALTER TABLE consignments ADD COLUMN id_order_consignment INT DEFAULT 0",
    "ALTER TABLE consignment_items ADD COLUMN qty_reponed INT DEFAULT 0",
    
    "CREATE TABLE IF NOT EXISTS `consignment_payments` (
      `id_payment` int(11) NOT NULL AUTO_INCREMENT,
      `id_consignment` int(11) NOT NULL,
      `amount_payment` double NOT NULL DEFAULT 0,
      `method_payment` varchar(50) DEFAULT 'efectivo',
      `reference_payment` varchar(255) DEFAULT NULL,
      `file_payment` varchar(255) DEFAULT NULL,
      `id_admin_payment` int(11) NOT NULL DEFAULT 0,
      `notes_payment` text DEFAULT NULL,
      `date_created_payment` date DEFAULT NULL,
      PRIMARY KEY (`id_payment`),
      KEY `idx_cons_pay` (`id_consignment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `consignment_replacements` (
      `id_replacement` int(11) NOT NULL AUTO_INCREMENT,
      `id_consignment` int(11) NOT NULL,
      `id_item_out` int(11) NOT NULL,
      `id_item_in` int(11) NOT NULL,
      `id_admin_replacement` int(11) NOT NULL,
      `notes_replacement` text DEFAULT NULL,
      `date_created_replacement` date DEFAULT NULL,
      PRIMARY KEY (`id_replacement`),
      KEY `idx_cons_repl` (`id_consignment`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $q) {
    try {
        $db->exec($q);
        echo "OK: " . substr($q, 0, 50) . "\n";
    } catch (Exception $e) {
        echo "Error (" . substr($q, 0, 30) . "): " . $e->getMessage() . "\n";
    }
}
echo "Migration finished.\n";
