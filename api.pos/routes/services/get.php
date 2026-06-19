<?php

require_once "controllers/get.controller.php";
require_once "models/connection.php";

$select = $_GET["select"] ?? "*";
$orderBy = $_GET["orderBy"] ?? null;
$orderMode = $_GET["orderMode"] ?? null;
$startAt = $_GET["startAt"] ?? null;
$endAt = $_GET["endAt"] ?? null;
$filterTo = $_GET["filterTo"] ?? null;
$inTo = $_GET["inTo"] ?? null;

$response = new GetController();

/*=============================================
Endpoint especial: GET /api/purchasable-products
Devuelve productos comprables (no fabricados, no combos, no laboratorio)
=============================================*/
if ($table === 'purchasable-products') {
	try {
		$db = Connection::connect();
		$stmt = $db->prepare("
			SELECT p.id_product,
			       CONCAT(
			       	COALESCE(NULLIF(p.title_product, ''), CONCAT('Producto #', p.id_product)),
			       	CASE WHEN COALESCE(p.sku_product, '') <> '' THEN CONCAT(' · ', p.sku_product) ELSE '' END
			       ) AS title_product,
			       p.sku_product,
			       p.unit_product
			FROM products p
			WHERE p.status_product = 1
			  AND COALESCE(p.is_combo_product, 0) = 0
			  AND NOT EXISTS (SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product)
			ORDER BY p.title_product ASC
		");
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode(['status' => 200, 'results' => $results]);
	} catch (Throwable $e) {
		echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
	}
	return;
}

if ($table === 'cashs') {
	try {
		$db = Connection::connect();
		$stmtOpen = $db->query("SELECT id_cash FROM cashs WHERE status_cash = 1");
		$openCashs = $stmtOpen->fetchAll(PDO::FETCH_COLUMN);
		foreach ($openCashs as $idCash) {
			$stmtB = $db->prepare("SELECT SUM(cost_bill) as total_bills FROM bills WHERE id_cash_bill = :id");
			$stmtB->execute([':id' => $idCash]);
			$bills = floatval($stmtB->fetchColumn() ?: 0);

			$stmtI = $db->prepare("SELECT SUM(amount_income) as total_incomes FROM incomes WHERE id_cash_income = :id");
			$stmtI->execute([':id' => $idCash]);
			$incomes = floatval($stmtI->fetchColumn() ?: 0);

			$stmtC = $db->prepare("SELECT * FROM cashs WHERE id_cash = :id");
			$stmtC->execute([':id' => $idCash]);
			$cash = $stmtC->fetch(PDO::FETCH_ASSOC);

			if ($cash) {
				$startDate = $cash['date_start_cash'];
				$endDate = $cash['date_end_cash'] && $cash['date_end_cash'] !== '0000-00-00 00:00:00' ? $cash['date_end_cash'] : date('Y-m-d H:i:s');
				$officeId = $cash['id_office_cash'];

				$stmtS = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND date_order >= :start AND date_order <= :end");
				$stmtS->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
				$sales = floatval($stmtS->fetchColumn() ?: 0);

				$stmtEf = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND method_order = 'efectivo' AND date_order >= :start AND date_order <= :end");
				$stmtEf->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
				$sales_efectivo = floatval($stmtEf->fetchColumn() ?: 0);

				$stmtQr = $db->prepare("SELECT SUM(total_order) FROM orders WHERE id_office_order = :o AND status_order IN ('Completada', 'Venta Confirmada') AND method_order = 'QR' AND date_order >= :start AND date_order <= :end");
				$stmtQr->execute([':o' => $officeId, ':start' => $startDate, ':end' => $endDate]);
				$sales_qr = floatval($stmtQr->fetchColumn() ?: 0);

				$cash_efectivo = $sales_efectivo + $incomes;
				$cash_qr = $sales_qr;

				$money_cash = $sales + $incomes;
				$start_cash = floatval($cash['start_cash']);
				$end_cash = $start_cash + $money_cash - $bills;
				$diff_cash = $end_cash;

				$stmtU = $db->prepare("UPDATE cashs SET bills_cash = :b, money_cash = :m, end_cash = :e, diff_cash = :d, cash_efectivo = :cef, cash_qr = :cqr WHERE id_cash = :id");
				$stmtU->execute([
					':b' => $bills,
					':m' => $money_cash,
					':e' => $end_cash,
					':d' => $diff_cash,
					':cef' => $cash_efectivo,
					':cqr' => $cash_qr,
					':id' => $idCash
				]);
			}
		}
	} catch (Throwable $e) {}
}

if ($table === 'clients') {
	try {
		$db = Connection::connect();
		$token = $_GET["token"] ?? null;
		if (!$token) {
			$headers = getallheaders();
			foreach ($headers as $k => $v) {
				if (strtolower($k) === 'authorization') {
					$token = str_replace('Bearer ', '', $v);
					break;
				}
			}
		}

		$role = null;
		$idAdmin = 0;
		if ($token) {
			$stmtUser = $db->prepare("SELECT id_admin, rol_admin FROM admins WHERE token_admin = :token LIMIT 1");
			$stmtUser->execute([':token' => $token]);
			$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
			if ($userRow) {
				$role = $userRow['rol_admin'];
				$idAdmin = intval($userRow['id_admin']);
			}
		}

		if ($role === 'vendedor' || $role === 'cajero') {
			if ($role === 'vendedor') {
				$whereClause = "WHERE id_admin_client = " . $idAdmin;
			} else {
				$whereClause = "WHERE id_admin_client = 0 OR id_admin_client IN (SELECT id_admin FROM admins WHERE rol_admin = 'cajero')";
			}

			// Add basic search support if search param is present
			if (isset($_GET["linkTo"]) && isset($_GET["search"])) {
				$searchCol = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET["linkTo"]);
				$searchTerm = "%" . $_GET["search"] . "%";
				$whereClause .= " AND `$searchCol` LIKE " . $db->quote($searchTerm);
			}

			$orderByStr = "";
			if (isset($_GET["orderBy"])) {
				$col = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET["orderBy"]);
				$mode = isset($_GET["orderMode"]) && strtolower($_GET["orderMode"]) === 'desc' ? 'DESC' : 'ASC';
				$orderByStr = " ORDER BY `$col` $mode";
			}

			$queryStr = "SELECT * FROM clients $whereClause$orderByStr";
			$stmt = $db->prepare($queryStr);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_CLASS);

			echo json_encode([
				'status' => 200,
				'results' => $results
			]);
			return;
		}
	} catch (Throwable $e) {
		// fallback to default
	}
}

if ($table === 'bills') {
	try {
		$db = Connection::connect();
		$token = $_GET["token"] ?? null;
		if (!$token) {
			$headers = getallheaders();
			foreach ($headers as $k => $v) {
				if (strtolower($k) === 'authorization') {
					$token = str_replace('Bearer ', '', $v);
					break;
				}
			}
		}

		$role = null;
		$idAdmin = 0;
		$idOffice = 0;
		if ($token) {
			$stmtUser = $db->prepare("SELECT id_admin, rol_admin, id_office_admin FROM admins WHERE token_admin = :token LIMIT 1");
			$stmtUser->execute([':token' => $token]);
			$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
			if ($userRow) {
				$role = $userRow['rol_admin'];
				$idAdmin = intval($userRow['id_admin']);
				$idOffice = intval($userRow['id_office_admin']);
			}
		}

		if ($role === 'vendedor' || $role === 'cajero') {
			if ($role === 'vendedor') {
				$whereClause = "WHERE id_admin_bill = " . $idAdmin;
			} else {
				$whereClause = "WHERE id_office_bill = " . $idOffice . " AND id_admin_bill IN (SELECT id_admin FROM admins WHERE rol_admin <> 'vendedor')";
			}

			// Add basic search support if search param is present
			if (isset($_GET["linkTo"]) && isset($_GET["search"])) {
				$searchCol = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET["linkTo"]);
				$searchTerm = "%" . $_GET["search"] . "%";
				$whereClause .= " AND `$searchCol` LIKE " . $db->quote($searchTerm);
			}

			$orderByStr = "";
			if (isset($_GET["orderBy"])) {
				$col = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET["orderBy"]);
				$mode = isset($_GET["orderMode"]) && strtolower($_GET["orderMode"]) === 'desc' ? 'DESC' : 'ASC';
				$orderByStr = " ORDER BY `$col` $mode";
			}

			$queryStr = "SELECT * FROM bills $whereClause$orderByStr";
			$stmt = $db->prepare($queryStr);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_CLASS);

			echo json_encode([
				'status' => 200,
				'results' => $results
			]);
			return;
		}
	} catch (Throwable $e) {
		// fallback to default
	}
}



/*=============================================
Peticiones GET con filtro
=============================================*/

if(isset($_GET["linkTo"]) && isset($_GET["equalTo"]) && !isset($_GET["rel"]) && !isset($_GET["type"]) ){

	$response -> getDataFilter($table, $select,$_GET["linkTo"],$_GET["equalTo"],$orderBy,$orderMode,$startAt,$endAt);

/*=============================================
Peticiones GET sin filtro entre tablas relacionadas
=============================================*/

}else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && !isset($_GET["linkTo"]) && !isset($_GET["equalTo"])){

	$response -> getRelData($_GET["rel"],$_GET["type"],$select,$orderBy,$orderMode,$startAt,$endAt);
	
/*=============================================
Peticiones GET con filtro entre tablas relacionadas
=============================================*/

}else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && isset($_GET["linkTo"]) && isset($_GET["equalTo"])){

	$response -> getRelDataFilter($_GET["rel"],$_GET["type"],$select,$_GET["linkTo"],$_GET["equalTo"],$orderBy,$orderMode,$startAt,$endAt);

/*=============================================
Peticiones GET para el buscador sin relaciones
=============================================*/

}else if(!isset($_GET["rel"]) && !isset($_GET["type"]) && isset($_GET["linkTo"]) && isset($_GET["search"])){

	$response -> getDataSearch($table, $select,$_GET["linkTo"],$_GET["search"],$orderBy,$orderMode,$startAt,$endAt);

/*=============================================
Peticiones GET para el buscador con relaciones
=============================================*/

}else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && isset($_GET["linkTo"]) && isset($_GET["search"])){


	$response -> getRelDataSearch($_GET["rel"],$_GET["type"],$select,$_GET["linkTo"],$_GET["search"],$orderBy,$orderMode,$startAt,$endAt);

/*=============================================
Peticiones GET para selección de rangos
=============================================*/

}else if(!isset($_GET["rel"]) && !isset($_GET["type"]) && isset($_GET["linkTo"]) && isset($_GET["between1"]) && isset($_GET["between2"])){

	$response -> getDataRange($table,$select,$_GET["linkTo"],$_GET["between1"],$_GET["between2"],$orderBy,$orderMode,$startAt,$endAt, $filterTo, $inTo);

/*=============================================
Peticiones GET para selección de rangos con relaciones
=============================================*/

}else if(isset($_GET["rel"]) && isset($_GET["type"]) && $table == "relations" && isset($_GET["linkTo"]) && isset($_GET["between1"]) && isset($_GET["between2"])){

	$response -> getRelDataRange($_GET["rel"],$_GET["type"],$select,$_GET["linkTo"],$_GET["between1"],$_GET["between2"],$orderBy,$orderMode,$startAt,$endAt, $filterTo, $inTo);

}else{

	/*=============================================
	Peticiones GET sin filtro
	=============================================*/

	$response -> getData($table, $select,$orderBy,$orderMode,$startAt,$endAt);


}









