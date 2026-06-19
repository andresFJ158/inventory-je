<?php

/** Respuesta JSON de éxito y termina la request. */
function pos_ok($payload = [], $message = null){
	$out = array_merge(["status" => 200], is_array($payload) ? $payload : ["data" => $payload]);
	if ($message !== null) { $out["message"] = $message; }
	echo json_encode($out);
	exit;
}

/** Respuesta JSON de error y termina la request. */
function pos_fail($status, $message){
	http_response_code($status);
	echo json_encode(["status" => $status, "message" => $message]);
	exit;
}

/** Entero validado desde input (con default). */
function pos_int($key, $default = 0){
	return isset($_POST[$key]) ? intval($_POST[$key]) : $default;
}

/** Monto monetario validado: float no negativo redondeado a 2 decimales. */
function pos_money($key, $default = 0.0){
	$v = isset($_POST[$key]) ? floatval($_POST[$key]) : $default;
	return $v < 0 ? 0.0 : round($v, 2);
}

/**
 * Ejecuta $fn dentro de una transacción atómica.
 * Hace commit si todo va bien; rollBack y relanza si hay excepción.
 * Reutiliza la transacción externa si ya hay una abierta (evita anidamiento).
 */
function pos_transaction(PDO $db, callable $fn){
	$owns = !$db->inTransaction();
	if ($owns) { $db->beginTransaction(); }
	try {
		$result = $fn($db);
		if ($owns) { $db->commit(); }
		return $result;
	} catch (Throwable $e) {
		if ($owns && $db->inTransaction()) { $db->rollBack(); }
		throw $e;
	}
}

/** Recalcula el stock global denormalizado de products desde product_inventory. */
function pos_refresh_product_stock(PDO $db, int $productId): void {
	if ($productId <= 0) { return; }
	$stmt = $db->prepare("
		UPDATE products
		SET stock_product = (
			SELECT COALESCE(SUM(stock_inventory), 0)
			FROM product_inventory
			WHERE id_product_inventory = :prod_sum AND status_inventory = 1
		)
		WHERE id_product = :prod_where
	");
	$stmt->execute([
		':prod_sum' => $productId,
		':prod_where' => $productId
	]);
}

/** Inserta o actualiza stock de una ubicación y mantiene el total global. */
function pos_adjust_product_inventory(PDO $db, int $productId, int $officeId, float $qtyDelta, string $movementType = 'ajuste', int $adminId = 0, ?int $transferId = null, string $notes = ''): void {
	if ($productId <= 0 || $officeId <= 0 || abs($qtyDelta) <= 0) { return; }

	if ($qtyDelta < 0) {
		$stmt = $db->prepare("
			SELECT COALESCE(stock_inventory, 0)
			FROM product_inventory
			WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
			LIMIT 1 FOR UPDATE
		");
		$stmt->execute([':prod' => $productId, ':office' => $officeId]);
		$current = (float)($stmt->fetchColumn() ?: 0);
		if ($current + $qtyDelta < -0.0001) {
			throw new RuntimeException('Stock insuficiente en la ubicación de origen.');
		}
	}

	$stmt = $db->prepare("
		INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
		VALUES (:prod, :office, :qty_insert, 1, CURDATE())
		ON DUPLICATE KEY UPDATE
			stock_inventory = stock_inventory + :qty_update,
			status_inventory = 1
	");
	$stmt->execute([
		':prod' => $productId,
		':office' => $officeId,
		':qty_insert' => $qtyDelta,
		':qty_update' => $qtyDelta
	]);
	pos_refresh_product_stock($db, $productId);
	pos_record_stock_movement($db, $productId, $officeId, $qtyDelta, $movementType, $adminId, $transferId, $notes);
}

/** Crea un movimiento de stock si la tabla auditora existe. */
function pos_record_stock_movement(PDO $db, int $productId, int $officeId, float $qty, string $type, int $adminId = 0, ?int $transferId = null, string $notes = ''): void {
	try {
		$stmt = $db->prepare("
			INSERT INTO stock_movements
				(id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, id_transfer_movement, notes_movement, date_created_movement)
			VALUES
				(:prod, :office, :qty, :type, :admin, :transfer, :notes, NOW())
		");
		$stmt->execute([
			':prod' => $productId,
			':office' => $officeId,
			':qty' => $qty,
			':type' => $type,
			':admin' => $adminId,
			':transfer' => $transferId,
			':notes' => $notes
		]);
	} catch (Throwable $e) {
		// La auditoría no debe tumbar operaciones históricas si el schema aún no fue migrado.
		error_log('[STOCK_MOVEMENT] ' . $e->getMessage());
	}
}

/** Precio vigente para POS: primero product_prices, luego purchases como compatibilidad. */
function pos_get_product_price(PDO $db, int $productId, int $officeId = 0): array {
	$price = [
		'price' => 0.0,
		'wholesalePrice' => 0.0,
		'wholesaleQty' => 0,
		'costReference' => 0.0,
		'source' => 'none'
	];
	if ($productId <= 0) { return $price; }

	try {
		$stmt = $db->prepare("
			SELECT price_sale, price_wholesale, wholesale_qty, cost_reference, source_price
			FROM product_prices
			WHERE id_product_price = :prod
			  AND status_price = 1
			  AND (id_office_price = :office_filter OR id_office_price = 0 OR id_office_price IS NULL)
			ORDER BY CASE WHEN id_office_price = :office_sort THEN 0 ELSE 1 END, id_price DESC
			LIMIT 1
		");
		$stmt->execute([
			':prod' => $productId,
			':office_filter' => $officeId,
			':office_sort' => $officeId
		]);
		if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			return [
				'price' => (float)$row['price_sale'],
				'wholesalePrice' => (float)$row['price_wholesale'],
				'wholesaleQty' => (int)$row['wholesale_qty'],
				'costReference' => (float)$row['cost_reference'],
				'source' => $row['source_price'] ?: 'product_prices'
			];
		}
	} catch (Throwable $e) {}

	$stmt = $db->prepare("
		SELECT cost_purchase, price_purchase, may_product, wholesale_quantity
		FROM purchases
		WHERE id_product_purchase = :prod
		  AND (COALESCE(price_purchase, 0) > 0 OR COALESCE(cost_purchase, 0) > 0)
		ORDER BY date_created_purchase DESC, id_purchase DESC
		LIMIT 1
	");
	$stmt->execute([':prod' => $productId]);
	if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$price['price'] = (float)($row['price_purchase'] ?: $row['cost_purchase']);
		$price['wholesalePrice'] = (float)$row['may_product'];
		$price['wholesaleQty'] = (int)$row['wholesale_quantity'];
		$price['costReference'] = (float)$row['cost_purchase'];
		$price['source'] = 'purchases_legacy';
	}

	// Final fallback: products table (which the frontend catalog uses directly)
	if ($price['price'] <= 0 || ($price['source'] === 'purchases_legacy' && (float)($row['price_purchase'] ?? 0) <= 0)) {
		$stmtProd = $db->prepare("
			SELECT price_product, wholesale_price_product, wholesale_qty_product
			FROM products
			WHERE id_product = :prod
		");
		$stmtProd->execute([':prod' => $productId]);
		if ($prod = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
			$price['price'] = (float)$prod['price_product'];
			$price['wholesalePrice'] = (float)$prod['wholesale_price_product'];
			$price['wholesaleQty'] = (int)$prod['wholesale_qty_product'];
			if ($price['source'] === 'none') {
				$price['source'] = 'products_table';
			}
		}
	}

	return $price;
}

/** Asegura un subalmacén compartido por sucursal para historial operativo. */
function pos_ensure_office_subwarehouse(PDO $db, int $officeId): int {
	if ($officeId <= 0) { return 0; }
	$stmt = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
	$stmt->execute([':office' => $officeId]);
	$id = intval($stmt->fetchColumn());
	if ($id > 0) { return $id; }

	$stmt = $db->prepare("
		INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse)
		VALUES (0, :office, 'Sub-Almacén de la Sucursal', 1, CURDATE())
	");
	$stmt->execute([':office' => $officeId]);
	return intval($db->lastInsertId());
}

/** Crea una reposición en tránsito: baja origen ahora y espera confirmación del destino. */
function pos_create_stock_transfer(PDO $db, int $productId, int $sourceOfficeId, int $destOfficeId, float $qty, int $adminId, string $notes = '', ?int $requestId = null): int {
	if ($productId <= 0 || $sourceOfficeId <= 0 || $destOfficeId <= 0 || $qty <= 0) {
		throw new InvalidArgumentException('Datos de transferencia inválidos.');
	}

	$stmt = $db->prepare("
		INSERT INTO stock_transfers
			(id_origin_office, id_dest_office, id_product_transfer, qty_transfer, id_admin_transfer,
			 notes_transfer, status_transfer, date_created_transfer)
		VALUES
			(:origin, :dest, :prod, :qty, :admin,
			 :notes, 'en_transito', CURDATE())
	");
	$stmt->execute([
		':origin' => $sourceOfficeId,
		':dest' => $destOfficeId,
		':prod' => $productId,
		':qty' => $qty,
		':admin' => $adminId,
		':notes' => $notes
	]);
	$transferId = intval($db->lastInsertId());

	pos_adjust_product_inventory($db, $productId, $sourceOfficeId, -$qty, 'transfer_salida', $adminId, $transferId, $notes);

	$subId = pos_ensure_office_subwarehouse($db, $destOfficeId);
	if ($subId > 0) {
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments
				(id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, id_request_assignment, type_assignment, notes_assignment, date_created_assignment)
			VALUES
				(:sub, :prod, :qty, :admin, :request_id, 'despacho_pendiente', :notes, NOW())
		");
		$stmtAssign->execute([
			':sub' => $subId,
			':prod' => $productId,
			':qty' => $qty,
			':admin' => $adminId,
			':request_id' => $requestId,
			':notes' => $notes
		]);
	}

	return $transferId;
}

/** Confirma una reposición entrante y recién entonces sube stock en destino. */
function pos_confirm_stock_transfer(PDO $db, int $transferId, int $destOfficeId, int $adminId): void {
	$stmt = $db->prepare("
		SELECT *
		FROM stock_transfers
		WHERE id_transfer = :id
		LIMIT 1 FOR UPDATE
	");
	$stmt->execute([':id' => $transferId]);
	$transfer = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$transfer) {
		throw new RuntimeException('Transferencia no encontrada.');
	}
	if ($destOfficeId > 0 && intval($transfer['id_dest_office']) !== $destOfficeId) {
		throw new RuntimeException('La transferencia no corresponde a esta sucursal.');
	}
	if ($transfer['status_transfer'] !== 'en_transito') {
		throw new RuntimeException('La transferencia no está pendiente de recepción.');
	}

	$productId = intval($transfer['id_product_transfer']);
	$qty = (float)$transfer['qty_transfer'];
	$officeId = intval($transfer['id_dest_office']);
	$notes = $transfer['notes_transfer'] ?: 'Recepción confirmada';

	pos_adjust_product_inventory($db, $productId, $officeId, $qty, 'transfer_ingreso', $adminId, $transferId, $notes);

	$stmtUpd = $db->prepare("
		UPDATE stock_transfers
		SET status_transfer = 'recibido'
		WHERE id_transfer = :id
	");
	$stmtUpd->execute([':id' => $transferId]);

	$subId = pos_ensure_office_subwarehouse($db, $officeId);
	if ($subId > 0) {
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments
				(id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, id_request_assignment, type_assignment, notes_assignment, date_created_assignment)
			VALUES
				(:sub, :prod, :qty, :admin, NULL, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':sub' => $subId,
			':prod' => $productId,
			':qty' => $qty,
			':admin' => $adminId,
			':notes' => 'Recepción confirmada: ' . $notes
		]);
	}
}

/** Rechaza una reposición entrante y retorna el stock al origen. */
function pos_reject_stock_transfer(PDO $db, int $transferId, int $destOfficeId, int $adminId, string $reason): void {
	$stmt = $db->prepare("SELECT * FROM stock_transfers WHERE id_transfer = :id LIMIT 1 FOR UPDATE");
	$stmt->execute([':id' => $transferId]);
	$transfer = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$transfer) {
		throw new RuntimeException('Transferencia no encontrada.');
	}
	if ($destOfficeId > 0 && intval($transfer['id_dest_office']) !== $destOfficeId) {
		throw new RuntimeException('La transferencia no corresponde a esta sucursal.');
	}
	if ($transfer['status_transfer'] !== 'en_transito') {
		throw new RuntimeException('La transferencia no está pendiente de recepción.');
	}

	$productId = intval($transfer['id_product_transfer']);
	$originOffice = intval($transfer['id_origin_office']);
	$qty = (float)$transfer['qty_transfer'];
	pos_adjust_product_inventory($db, $productId, $originOffice, $qty, 'transfer_rechazo_retorno', $adminId, $transferId, $reason);

	$stmtUpd = $db->prepare("
		UPDATE stock_transfers
		SET status_transfer = 'rechazado',
			notes_transfer = CONCAT(COALESCE(notes_transfer,''), ' | Rechazado: ', :reason)
		WHERE id_transfer = :id
	");
	$stmtUpd->execute([':reason' => $reason, ':id' => $transferId]);
}
