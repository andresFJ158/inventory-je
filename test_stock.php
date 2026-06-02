<?php
require 'controllers/curl.controller.php';
require 'controllers/template.controller.php';
class LocalConnection {
	static public function connect(){
		$host = getenv('DB_HOST') ?: '127.0.0.1';
		$db = getenv('DB_NAME') ?: 'u228744577_pos';
		$user = getenv('DB_USER') ?: 'root';
		$pass = getenv('DB_PASS') ?: '';
		$port = getenv('DB_PORT') ?: '3306';
		$link = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$db, $user, $pass);
		$link->exec('set names utf8');
		$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $link;
	}
}

$db = LocalConnection::connect();
$stmt = $db->query("SELECT * FROM admins WHERE name_admin LIKE '%Santiago%'");
$santiago = $stmt->fetch(PDO::FETCH_ASSOC);
echo 'Santiago ID: ' . $santiago['id_admin'] . ' Office: ' . $santiago['id_office_admin'] . "\n";

$stmt = $db->prepare("
	SELECT wa.id_product_assignment as id_product,
		   COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
		   COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0) as stock
	FROM warehouse_assignments wa
	JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
	WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
	GROUP BY wa.id_product_assignment
");
$stmt->execute(['admin' => $santiago['id_admin'], 'office' => $santiago['id_office_admin']]);
$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($stocks);
