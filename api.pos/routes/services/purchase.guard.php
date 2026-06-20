<?php

require_once "models/connection.php";
require_once "models/get.model.php";

function purchase_guard_actor(string $tableToken, string $suffix, string $token): array {
	$rows = GetModel::getDataFilter(
		$tableToken,
		"id_admin,rol_admin,permissions_admin",
		"token_".$suffix,
		$token,
		null,
		null,
		null,
		null
	);
	$row = !empty($rows) ? $rows[0] : null;
	return [
		'id_admin' => $row ? (int)($row->id_admin ?? 0) : 0,
		'role' => $row->rol_admin ?? ''
	];
}

function purchase_guard_number($value): float {
	if ($value === null || $value === '') { return 0.0; }
	$normalized = trim((string)$value);
	if (strpos($normalized, ',') !== false) {
		$normalized = str_replace('.', '', $normalized);
		$normalized = str_replace(',', '.', $normalized);
	}
	return (float)$normalized;
}

function purchase_guard_purchasable_product(PDO $db, int $productId): array {
	try {
		$stmt = $db->prepare("
			SELECT p.id_product,
			       p.title_product,
			       COALESCE(p.is_combo_product, 0) AS is_combo_product,
			       EXISTS(SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product) AS has_recipe
			FROM products p
			WHERE p.id_product = :id
		");
		$stmt->execute(['id' => $productId]);
		$product = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$product) {
			return ['ok' => false, 'message' => 'El producto seleccionado no existe.'];
		}

		$isLabMade = (int)$product['has_recipe'] === 1
			|| (int)$product['is_combo_product'] === 1;

		if ($isLabMade) {
			return ['ok' => false, 'status' => 422, 'message' => 'Solo se pueden registrar compras directas para productos externos. Para productos fabricados o combos, utiliza Producción o configuración de combos.'];
		}
	} catch (Exception $e) {
		return ['ok' => false, 'message' => 'Error al validar el producto.'];
	}

	return ['ok' => true, 'message' => 'ok'];
}

function purchase_guard_validate(array $payload, string $role, int $purchaseId = 0): array {
	if (!in_array($role, ['superadmin', 'admin', 'lab_admin'])) {
		return ['ok' => false, 'status' => 403, 'message' => 'Solo administración o laboratorio pueden registrar compras.'];
	}

	$db = Connection::connect();
	if ($purchaseId > 0) {
		$stmt = $db->prepare("SELECT * FROM purchases WHERE id_purchase = :id LIMIT 1");
		$stmt->execute([':id' => $purchaseId]);
		$current = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
		$payload = array_merge($current, $payload);
	}

	$productId   = (int)($payload['id_product_purchase'] ?? 0);
	$supplierId  = (int)($payload['id_supplier_purchase'] ?? 0);
	$warehouseId = (int)($payload['id_office_purchase'] ?? 0);
	$qty         = purchase_guard_number($payload['qty_purchase'] ?? 0);
	$cost        = purchase_guard_number($payload['cost_purchase'] ?? 0);

	if ($supplierId <= 0) {
		return ['ok' => false, 'status' => 400, 'message' => 'Selecciona un proveedor para la compra.'];
	}
	if ($warehouseId < 0) {
		return ['ok' => false, 'status' => 400, 'message' => 'Selecciona el almacén de ingreso de la compra.'];
	}
	if ($productId <= 0) {
		return ['ok' => false, 'status' => 400, 'message' => 'Selecciona un producto para la compra.'];
	}
	if ($qty <= 0 || $cost <= 0) {
		return ['ok' => false, 'status' => 400, 'message' => 'La cantidad y el costo de compra deben ser mayores a cero.'];
	}

	$productCheck = purchase_guard_purchasable_product($db, $productId);
	if (!$productCheck['ok']) {
		return ['ok' => false, 'status' => 422, 'message' => $productCheck['message']];
	}

	return ['ok' => true, 'status' => 200, 'message' => 'ok'];
}
