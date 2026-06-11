<?php
require_once "models/connection.php";
$db = Connection::connect();

$sql = "
CREATE TABLE IF NOT EXISTS `qrs` (
  `id_qr` int(11) NOT NULL AUTO_INCREMENT,
  `image_qr` text DEFAULT NULL,
  `id_office_qr` int(11) NOT NULL DEFAULT 0,
  `date_created_qr` date DEFAULT NULL,
  `date_updated_qr` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_qr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $db->exec($sql);
    echo "Table 'qrs' created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
