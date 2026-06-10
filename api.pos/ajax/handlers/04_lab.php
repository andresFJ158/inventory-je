<?php
if(isset($_POST["editRawMaterial"])){
	$db = LocalConnection::connect();
	try {
		$id_raw_material = intval($_POST['id_raw_material']);
		$name = trim(htmlspecialchars($_POST['name_raw_material']));
		$measure_type = $_POST['measure_type'];
		$unit = trim(htmlspecialchars($_POST['unit_raw_material']));
		$desc = trim(htmlspecialchars($_POST['description_raw_material']));
		
		// Validar duplicado de nombre (excluyendo el actual) en la misma sucursal y misma categoria de insumo
		$stmtGetOffice = $db->prepare("SELECT id_office_raw_material, is_insumo FROM raw_materials WHERE id_raw_material = :id LIMIT 1");
		$stmtGetOffice->execute([':id' => $id_raw_material]);
		$currentMaterial = $stmtGetOffice->fetch(PDO::FETCH_ASSOC);
		$id_office = $currentMaterial ? (int)$currentMaterial['id_office_raw_material'] : 0;
		$is_insumo = $currentMaterial ? (int)$currentMaterial['is_insumo'] : 0;

		$stmtDup = $db->prepare("SELECT id_raw_material FROM raw_materials WHERE name_raw_material = :name AND id_office_raw_material = :office AND is_insumo = :is_insumo AND id_raw_material != :id LIMIT 1");
		$stmtDup->execute([':name' => $name, ':office' => $id_office, ':is_insumo' => $is_insumo, ':id' => $id_raw_material]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe un registro con ese nombre en esta sucursal.";
			exit;
		}

		$stmt = $db->prepare("UPDATE raw_materials SET name_raw_material = :name, measure_type = :measure, unit_raw_material = :unit, description_raw_material = :desc WHERE id_raw_material = :id");
		$stmt->execute([
			':name' => $name,
			':measure' => $measure_type,
			':unit' => $unit,
			':desc' => $desc,
			':id' => $id_raw_material
		]);
		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

/*=============================================
Eliminar Materia Prima
=============================================*/
if(isset($_POST["deleteRawMaterial"])){
	$db = LocalConnection::connect();
	try {
		$id_raw_material = intval($_POST['id_raw_material']);

		// 1. Verificar stock actual
		$stmtStock = $db->prepare("SELECT stock_raw_material, name_raw_material FROM raw_materials WHERE id_raw_material = :id");
		$stmtStock->execute([':id' => $id_raw_material]);
		$mat = $stmtStock->fetch(PDO::FETCH_ASSOC);

		if (!$mat) {
			echo "error|La materia prima no existe.";
			exit;
		}

		if (floatval($mat['stock_raw_material']) > 0) {
			echo "error|No se puede eliminar la materia prima porque a�n tiene stock disponible (" . $mat['stock_raw_material'] . ").";
			exit;
		}

		// 2. Verificar si est� en recetas
		$stmtRecipe = $db->prepare("SELECT COUNT(*) FROM recipe_ingredients WHERE id_raw_material_ingredient = :id");
		$stmtRecipe->execute([':id' => $id_raw_material]);
		if (intval($stmtRecipe->fetchColumn()) > 0) {
			echo "error|No se puede eliminar la materia prima porque est� asociada a una o m�s recetas.";
			exit;
		}

		// 3. Verificar si est� en historial de producciones
		$stmtProd = $db->prepare("SELECT COUNT(*) FROM production_material_costs WHERE id_raw_material_mat_cost = :id");
		$stmtProd->execute([':id' => $id_raw_material]);
		if (intval($stmtProd->fetchColumn()) > 0) {
			echo "error|No se puede eliminar la materia prima porque ya ha sido consumida en producciones pasadas.";
			exit;
		}

		// 4. Verificar si tiene entradas registradas (incluso si stock es 0, puede haber historial)
		$stmtEntry = $db->prepare("SELECT COUNT(*) FROM raw_material_entries WHERE id_raw_material_entry = :id");
		$stmtEntry->execute([':id' => $id_raw_material]);
		if (intval($stmtEntry->fetchColumn()) > 0) {
			echo "error|No se puede eliminar la materia prima porque tiene historial de entradas registradas.";
			exit;
		}

		// Proceder a eliminar
		$stmtDel = $db->prepare("DELETE FROM raw_materials WHERE id_raw_material = :id");
		$stmtDel->execute([':id' => $id_raw_material]);
		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// GET LAB MATERIALS
//=====================================
if(isset($_POST["getLabMaterials"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$is_insumo = isset($_POST["is_insumo"]) ? intval($_POST["is_insumo"]) : null;

	if ($is_insumo !== null) {
		$stmt = $db->prepare("SELECT * FROM raw_materials WHERE id_office_raw_material = :office AND is_insumo = :is_insumo AND status_raw_material = 1 ORDER BY name_raw_material ASC");
		$stmt->execute([':office' => $id_office, ':is_insumo' => $is_insumo]);
	} else {
		$stmt = $db->prepare("SELECT * FROM raw_materials WHERE id_office_raw_material = :office AND status_raw_material = 1 ORDER BY name_raw_material ASC");
		$stmt->execute([':office' => $id_office]);
	}
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

//=====================================
// SAVE LAB MATERIAL
//=====================================
if(isset($_POST["saveLabMaterial"])){
	$db = LocalConnection::connect();
	try {
		$name = trim(htmlspecialchars($_POST['name_raw_material']));
		$measure_type = $_POST['measure_type'];
		$unit = trim(htmlspecialchars($_POST['unit_raw_material']));
		$desc = trim(htmlspecialchars($_POST['description_raw_material']));
		$id_office = intval($_POST['id_office_raw_material']);
		$id_admin = isset($_POST['id_admin_raw_material']) ? intval($_POST['id_admin_raw_material']) : 1;
		$is_insumo = isset($_POST['is_insumo']) ? intval($_POST['is_insumo']) : 0;

		// Validate duplicate name
		$stmtDup = $db->prepare("SELECT id_raw_material FROM raw_materials WHERE name_raw_material = :name AND id_office_raw_material = :office AND is_insumo = :is_insumo LIMIT 1");
		$stmtDup->execute([':name' => $name, ':office' => $id_office, ':is_insumo' => $is_insumo]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe un registro con ese nombre en esta sucursal.";
			exit;
		}

		$stmt = $db->prepare("INSERT INTO raw_materials (name_raw_material, measure_type, unit_raw_material, description_raw_material, id_office_raw_material, id_admin_raw_material, stock_raw_material, is_insumo, date_created_raw_material) VALUES (:name, :measure, :unit, :desc, :office, :id_admin, 0, :is_insumo, CURDATE())");
		$stmt->execute([
			':name' => $name,
			':measure' => $measure_type,
			':unit' => $unit,
			':desc' => $desc,
			':office' => $id_office,
			':id_admin' => $id_admin,
			':is_insumo' => $is_insumo
		]);
		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// SAVE LAB ENTRY
//=====================================
if(isset($_POST["saveLabEntry"])){
	$db = LocalConnection::connect();
	try {
		$id_raw_material = intval($_POST['id_raw_material_entry']);
		$qty = floatval($_POST['qty_entry']);
		$lot_number = trim(htmlspecialchars($_POST['lot_number_entry']));
		$supplier = trim(htmlspecialchars($_POST['supplier_entry']));
		$date = $_POST['date_entry'];
		$id_admin = intval($_POST['id_admin_entry']);

		$stmt = $db->prepare("INSERT INTO raw_material_entries (id_raw_material_entry, qty_entry, lot_number_entry, supplier_entry, date_entry, id_admin_entry, status_entry, type_entry, date_created_entry) VALUES (:id_raw, :qty, :lot, :supplier, :date, :id_admin, 'pendiente', 'ingreso', NOW())");
		$stmt->execute([
			':id_raw' => $id_raw_material,
			':qty' => $qty,
			':lot' => $lot_number,
			':supplier' => $supplier,
			':date' => $date,
			':id_admin' => $id_admin
		]);
		echo json_encode(["status" => 200, "results" => "ok"]);
	} catch (Exception $e) {
		echo json_encode(["status" => 500, "message" => $e->getMessage()]);
	}
	exit;
}

//=====================================
// SAVE LAB ADJUSTMENT (EGRESO / BAJA)
//=====================================
if(isset($_POST["saveLabAdjustment"])){
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();
		
		$id_raw_material = intval($_POST['id_raw_material']);
		$qty = floatval($_POST['qty']);
		$concept = trim(htmlspecialchars($_POST['concept']));
		$notes = trim(htmlspecialchars($_POST['notes']));
		$id_admin = intval($_POST['id_admin']);
		
		// Check current stock
		$stmtCheck = $db->prepare("SELECT stock_raw_material, name_raw_material FROM raw_materials WHERE id_raw_material = :id LIMIT 1");
		$stmtCheck->execute([':id' => $id_raw_material]);
		$material = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$material) {
			throw new Exception("Material o Insumo no encontrado.");
		}
		
		$current_stock = floatval($material['stock_raw_material']);
		if ($current_stock < $qty) {
			throw new Exception("Stock insuficiente de " . $material['name_raw_material'] . " para dar de baja. Stock actual: " . $current_stock);
		}
		
		// Insert egreso entry (immediately approved/aprobado)
		$stmt = $db->prepare("INSERT INTO raw_material_entries 
			(id_raw_material_entry, qty_entry, lot_number_entry, supplier_entry, notes_entry, status_entry, id_admin_entry, id_approved_by_entry, type_entry, concept_entry, date_entry, date_approved_entry, date_created_entry) 
			VALUES (:id_raw, :qty, 'EGRESO', 'AJUSTE INTERNO', :notes, 'aprobado', :id_admin, :id_admin, 'egreso', :concept, CURDATE(), CURDATE(), NOW())");
		
		$stmt->execute([
			':id_raw' => $id_raw_material,
			':qty' => $qty,
			':notes' => $notes,
			':id_admin' => $id_admin,
			':concept' => $concept
		]);
		
		// Decrease stock
		$stmtStock = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
		$stmtStock->execute([':qty' => $qty, ':id' => $id_raw_material]);
		
		$db->commit();
		echo json_encode(["status" => 200, "results" => "ok"]);
	} catch (Exception $e) {
		$db->rollBack();
		echo json_encode(["status" => 500, "message" => $e->getMessage()]);
	}
	exit;
}

//=====================================
// GET LAB ENTRIES
//=====================================
if(isset($_POST["getLabEntries"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT rme.*, rm.name_raw_material, rm.unit_raw_material, rm.is_insumo 
		FROM raw_material_entries rme 
		JOIN raw_materials rm ON rme.id_raw_material_entry = rm.id_raw_material 
		WHERE rm.id_office_raw_material = :office 
		ORDER BY rme.id_entry DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

//=====================================
// GET LAB RECIPES
//=====================================
if(isset($_POST["getLabRecipes"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);

	$stmt = $db->prepare("
		SELECT r.*, p.title_product, p.rte_product
		FROM recipes r
		JOIN products p ON r.id_product_recipe = p.id_product
		WHERE r.id_office_recipe = :office
		ORDER BY r.id_recipe ASC
	");
	$stmt->execute([':office' => $id_office]);
	$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

	foreach ($recipes as &$recipe) {
		// Ingredientes con precio de �ltima entrada aprobada
		$stmtIng = $db->prepare("
			SELECT ri.*, rm.name_raw_material, rm.unit_raw_material,
			       COALESCE((
			           SELECT unit_price_entry
			           FROM raw_material_entries
			           WHERE id_raw_material_entry = ri.id_raw_material_ingredient
			             AND status_entry = 'aprobado'
			           ORDER BY id_entry DESC LIMIT 1
			       ), 0) AS unit_price_mp
			FROM recipe_ingredients ri
			JOIN raw_materials rm ON ri.id_raw_material_ingredient = rm.id_raw_material
			WHERE ri.id_recipe_ingredient = :id_recipe
		");
		$stmtIng->execute([':id_recipe' => $recipe['id_recipe']]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
		$recipe['ingredients'] = $ingredients;

		// Calcular costo estimado por unidad de producci�n basado en ingredientes
		$batch_size = floatval($recipe['batch_size_recipe']) ?: 1;
		$estimated_cost_batch = 0;
		foreach ($ingredients as $ing) {
			$estimated_cost_batch += floatval($ing['qty_ingredient']) * floatval($ing['unit_price_mp']);
		}
		$estimated_unit_cost = $batch_size > 0 ? $estimated_cost_batch / $batch_size : 0;

		// Si ya tiene costo real (rte_product > 0), mostrar ese; si no, mostrar estimado
		$recipe['cost_estimated'] = round($estimated_unit_cost, 4);
		$recipe['cost_real'] = floatval($recipe['rte_product']);
		$recipe['has_real_cost'] = floatval($recipe['rte_product']) > 0;

		// Calcular m�tricas de merma hist�ricas
		$stmtMetrics = $db->prepare("
			SELECT 
				AVG(yield_variance_pct) as avg_variance,
				MIN(yield_variance_pct) as worst_variance,
				MAX(yield_variance_pct) as best_variance
			FROM productions 
			WHERE id_recipe_production = :id_recipe AND yield_variance_pct IS NOT NULL
		");
		$stmtMetrics->execute([':id_recipe' => $recipe['id_recipe']]);
		$metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC);

		$recipe['avg_variance'] = $metrics && $metrics['avg_variance'] !== null ? floatval($metrics['avg_variance']) : 0;
		$recipe['worst_variance'] = $metrics && $metrics['worst_variance'] !== null ? floatval($metrics['worst_variance']) : 0;
		$recipe['best_variance'] = $metrics && $metrics['best_variance'] !== null ? floatval($metrics['best_variance']) : 0;

		// Obtener �ltimos 5 registros de merma
		$stmtHistory = $db->prepare("
			SELECT yield_variance_pct 
			FROM productions 
			WHERE id_recipe_production = :id_recipe AND yield_variance_pct IS NOT NULL
			ORDER BY id_production DESC LIMIT 5
		");
		$stmtHistory->execute([':id_recipe' => $recipe['id_recipe']]);
		$recipe['variance_history'] = $stmtHistory->fetchAll(PDO::FETCH_COLUMN);
	}

	echo json_encode([
		'status' => 200,
		'results' => $recipes
	]);
	exit;
}

//=====================================
// GET LAB PRODUCTIONS
//=====================================
if(isset($_POST["getLabProductions"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT p.*, prod.title_product AS name_product,
		       r.unit_batch_recipe
		FROM productions p 
		JOIN products prod ON p.id_product_production = prod.id_product
		LEFT JOIN recipes r ON p.id_recipe_production = r.id_recipe
		WHERE p.id_office_production = :office 
		ORDER BY p.id_production DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

//=====================================
// GET WASTE PACKAGED
//=====================================
if(isset($_POST["getWastePackaged"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT w.*, p.title_product, p.unit_product, prod.id_recipe_production, r.title_product AS recipe_name, prod.pkg_name_production
		FROM waste_packaged w
		JOIN products p ON w.id_product_waste = p.id_product
		JOIN productions prod ON w.id_production_waste = prod.id_production
		LEFT JOIN recipes r ON prod.id_recipe_production = r.id_recipe
		WHERE w.id_office_waste = :office 
		ORDER BY w.id_waste DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

//=====================================
// FINISH LAB PRODUCTION
//=====================================
if(isset($_POST["finishLabProduction"])){
	$db = LocalConnection::connect();
	try {
		$id_production = intval($_POST['id_production']);
		// Update status to 'pendiente_qc' so it enters the quality control queue
		$stmt = $db->prepare("UPDATE productions SET status_production = 'pendiente_qc', end_date_production = CURDATE() WHERE id_production = :id");
		$stmt->execute([':id' => $id_production]);
		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// CANCEL LAB PRODUCTION (solo si est� en estado 'pendiente')
//=====================================
if(isset($_POST["cancelProduction"])){
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();
		$id_production = intval($_POST['id_production']);

		$stmtCheck = $db->prepare("SELECT status_production FROM productions WHERE id_production = :id");
		$stmtCheck->execute([':id' => $id_production]);
		$status = $stmtCheck->fetchColumn();

		if ($status !== 'pendiente') {
			echo "error|Solo se pueden cancelar producciones en estado pendiente. Estado actual: " . $status;
			$db->rollBack();
			exit;
		}

		$stmtDel = $db->prepare("DELETE FROM productions WHERE id_production = :id");
		$stmtDel->execute([':id' => $id_production]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// GET LAB WAREHOUSE
//=====================================
if(isset($_POST["getLabWarehouse"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT 
			p.id_product AS id_warehouse, 
			p.title_product AS name_product, 
			COALESCE((SELECT stock_inventory FROM product_inventory WHERE id_product_inventory = p.id_product AND id_office_inventory = :office LIMIT 1), 0) AS qty_warehouse, 
			p.rte_product AS cost_warehouse,
			(SELECT cost_purchase FROM purchases WHERE id_product_purchase = p.id_product ORDER BY id_purchase DESC LIMIT 1) AS sale_price_warehouse,
			(SELECT COUNT(*) FROM purchases WHERE id_product_purchase = p.id_product) AS price_defined_warehouse
		FROM products p
		WHERE p.is_compound_product = 1 AND p.id_office_product = :office
		ORDER BY p.id_product DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

//=====================================
// SAVE LAB PRODUCT PRICE
//=====================================
if(isset($_POST["saveLabProductPrice"])){
	$db = LocalConnection::connect();
	try {
		$id_product = intval($_POST['id_product']);
		$price = floatval($_POST['price']);
		$id_office = intval($_POST['id_office']);

		// Check if a purchase record already exists for this product
		$stmtCheck = $db->prepare("SELECT id_purchase FROM purchases WHERE id_product_purchase = :id_prod ORDER BY id_purchase DESC LIMIT 1");
		$stmtCheck->execute([':id_prod' => $id_product]);
		$id_purchase = $stmtCheck->fetchColumn();

		if ($id_purchase) {
			// Update existing selling price
			$stmt = $db->prepare("UPDATE purchases SET cost_purchase = :price, date_updated_purchase = CURRENT_TIMESTAMP() WHERE id_purchase = :id_purch");
			$stmt->execute([':price' => $price, ':id_purch' => $id_purchase]);
		} else {
			// Insert new selling price
			$stmt = $db->prepare("INSERT INTO purchases (id_product_purchase, cost_purchase, id_office_purchase, qty_purchase, date_created_purchase) VALUES (:id_prod, :price, :office, 0, CURDATE())");
			$stmt->execute([':id_prod' => $id_product, ':price' => $price, ':office' => $id_office]);
		}
		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// GET LAB QC TESTS
//=====================================
if(isset($_POST["getLabQCTests"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	
	// Pending QC tests (from productions)
	$stmtPending = $db->prepare("
		SELECT p.id_production AS id, p.id_production AS id_prod, 
			   CONCAT('LOT-', p.id_production) AS batch_code,
			   pr.title_product AS name_product,
			   'Pendiente' AS result_qc,
			   p.date_updated_production AS date_created_qc,
			   'pendiente' AS status_qc,
			   p.total_qty_production
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE p.id_office_production = :office AND p.status_production = 'pendiente_qc'
	");
	$stmtPending->execute([':office' => $id_office]);
	$pending = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

	// Completed QC tests
	$stmtCompleted = $db->prepare("
		SELECT p.id_production AS id, qc.id_qc, p.id_production AS id_prod, 
			   CONCAT('LOT-', p.id_production) AS batch_code,
			   pr.title_product AS name_product,
			   qc.result_qc,
			   qc.date_created_qc,
			   'aprobado' AS status_qc,
			   p.total_qty_production
		FROM quality_checks qc
		JOIN productions p ON qc.id_production_qc = p.id_production
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE qc.id_office_qc = :office
		ORDER BY qc.id_qc DESC
	");
	$stmtCompleted->execute([':office' => $id_office]);
	$completed = $stmtCompleted->fetchAll(PDO::FETCH_ASSOC);

	// Merge them
	$results = array_merge($pending, $completed);
	
	echo json_encode([
		'status' => 200,
		'results' => $results
	]);
	exit;
}

//=====================================
// GET LAB DASHBOARD METRICS
//=====================================
if(isset($_POST["getLabDashboardMetrics"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);

	// Count raw materials
	$stmt1 = $db->prepare("SELECT COUNT(*) FROM raw_materials WHERE id_office_raw_material = :office");
	$stmt1->execute([':office' => $id_office]);
	$totalMaterials = intval($stmt1->fetchColumn());

	// Count in-process productions
	$stmt2 = $db->prepare("SELECT COUNT(*) FROM productions WHERE id_office_production = :office AND status_production IN ('proceso', 'pendiente', 'en_proceso')");
	$stmt2->execute([':office' => $id_office]);
	$totalInProcess = intval($stmt2->fetchColumn());

	// Count quality checks
	$stmt3 = $db->prepare("SELECT COUNT(*) FROM quality_checks WHERE id_office_qc = :office");
	$stmt3->execute([':office' => $id_office]);
	$qualityChecks = intval($stmt3->fetchColumn());

	// Final products stock
	$stmt4 = $db->prepare("SELECT SUM(stock_product) FROM products WHERE is_compound_product = 1 AND origin_office_product = :office");
	$stmt4->execute([':office' => $id_office]);
	$finalProductsStock = floatval($stmt4->fetchColumn());

	// Recent activity
	$stmt5 = $db->prepare("
		SELECT p.id_production, p.status_production, p.total_qty_production,
			   p.date_updated_production, pr.title_product AS name_product,
			   a.name_admin
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		LEFT JOIN admins a ON p.id_admin_production = a.id_admin
		WHERE p.id_office_production = :office
		ORDER BY p.id_production DESC
		LIMIT 5
	");
	$stmt5->execute([':office' => $id_office]);
	$recentActivity = $stmt5->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'status' => 200,
		'results' => [
			'totalMaterials' => $totalMaterials,
			'totalInProcess' => $totalInProcess,
			'qualityChecks' => $qualityChecks,
			'finalProductsStock' => $finalProductsStock,
			'recentActivity' => $recentActivity
		]
	]);
	exit;
}

//=====================================
// GET LOGGED USER
//=====================================
