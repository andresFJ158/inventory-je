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
			  AND COALESCE(p.is_manufactured_product, 0) = 0
			  AND COALESCE(p.is_combo_product, 0) = 0
			  AND COALESCE(NULLIF(p.source_type_product, ''), 'externo') <> 'laboratorio'
			  AND NOT EXISTS (SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product)
			  AND NOT EXISTS (SELECT 1 FROM productions pr WHERE pr.id_packaged_product = p.id_product)
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

/*=============================================
Filtro de cartera: vendedor sin gestionar_clientes
solo ve sus clientes + pool (id_admin_client = 0)
=============================================*/
if ($table === 'clients' && isset($_GET['token']) && !isset($_GET['rel'])) {
	$tableToken = $_GET['table'] ?? 'admins';
	$sfx        = $_GET['suffix'] ?? 'admin';
	$adminRows  = GetModel::getDataFilter($tableToken, "rol_admin,permissions_admin,id_admin", "token_".$sfx, $_GET["token"], null, null, null, null);
	$adminRow   = !empty($adminRows) ? $adminRows[0] : null;
	$role       = $adminRow->rol_admin ?? '';
	$perms      = json_decode(urldecode($adminRow->permissions_admin ?? '{}'), true);
	$canSeeAll  = in_array($role, ['superadmin', 'admin', 'cajero'])
		|| ($perms['gestionar_clientes'] ?? '') === 'on';

	if (!$canSeeAll && $role === 'vendedor') {
		$myId  = (int)($adminRow->id_admin ?? 0);
		$db    = Connection::connect();
		$stmt  = $db->prepare("SELECT * FROM clients WHERE id_admin_client = ? OR id_admin_client = 0 ORDER BY id_client DESC");
		$stmt->execute([$myId]);
		$rows  = $stmt->fetchAll(PDO::FETCH_OBJ);
		echo json_encode(['status' => 200, 'results' => $rows]);
		return;
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









