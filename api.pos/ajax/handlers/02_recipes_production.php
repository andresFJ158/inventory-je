<?php
if(isset($_POST["saveRecipe"])){
	// require_once removed
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();

		$name_product = trim($_POST['name_product']);
		$batch_size = (float)$_POST['batch_size'];
		$unit_batch = trim($_POST['unit_batch']);
		$id_office = 0; // Recipes and production always belong to Lab (ID 0)
		$id_admin = (int)$_POST['id_admin'];
		$existing_product_id = isset($_POST['existing_product_id']) ? (int)$_POST['existing_product_id'] : 0;

		if ($existing_product_id > 0) {
			// Vincular receta a producto existente del catálogo
			$stmtCheck = $db->prepare("SELECT id_product FROM products WHERE id_product = :id AND status_product = 1 LIMIT 1");
			$stmtCheck->execute([':id' => $existing_product_id]);
			if (!$stmtCheck->fetch()) {
				echo "error|El producto seleccionado no existe o está inactivo.";
				$db->rollBack();
				exit;
			}
			// Verificar que no tenga receta ya asignada
			$stmtRecCheck = $db->prepare("SELECT id_recipe FROM recipes WHERE id_product_recipe = :id LIMIT 1");
			$stmtRecCheck->execute([':id' => $existing_product_id]);
			if ($stmtRecCheck->fetch()) {
				echo "error|Este producto ya tiene una receta asignada.";
				$db->rollBack();
				exit;
			}
			$id_product = $existing_product_id;
			$stmtUpdate = $db->prepare("UPDATE products SET is_compound_product = 1, origin_office_product = :office WHERE id_product = :id");
			$stmtUpdate->execute([':office' => $id_office, ':id' => $id_product]);
		} else {
			// Catálogo global: un producto no debe duplicarse por sucursal.
			$stmtDup = $db->prepare("SELECT id_product FROM products WHERE title_product = :name AND status_product = 1 LIMIT 1");
			$stmtDup->execute([':name' => $name_product]);
			if($stmtDup->fetch()) {
				echo "error|Ya existe un producto con ese nombre en el catálogo global.";
				$db->rollBack();
				exit;
			}

			$stmtProd = $db->prepare("
				INSERT INTO products
					(title_product, unit_product, id_office_product, origin_office_product, is_compound_product, status_product, stock_product, rte_product)
				VALUES
					(:name, :unit, 0, :office, 1, 1, '0', '0')
			");
			$stmtProd->execute([
				':name' => $name_product,
				':unit' => $unit_batch,
				':office' => $id_office
			]);
			$id_product = $db->lastInsertId();
		}

		// Crear registro en product_inventory para esta oficina
		$stmtInv = $db->prepare("INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory) VALUES (:product, :office, 0, 1, NOW()) ON DUPLICATE KEY UPDATE status_inventory = 1");
		$stmtInv->execute([
			':product' => $id_product,
			':office' => $id_office
		]);

		// 2. Insertar Receta
		$stmtRec = $db->prepare("INSERT INTO recipes (id_product_recipe, batch_size_recipe, unit_batch_recipe, id_office_recipe, id_admin_recipe, date_created_recipe) VALUES (:id_prod, :batch, :unit, :office, :admin, NOW())");
		$stmtRec->execute([
			':id_prod' => $id_product,
			':batch' => $batch_size,
			':unit' => $unit_batch,
			':office' => $id_office,
			':admin' => $id_admin
		]);
		$id_recipe = $db->lastInsertId();

		// 3. Insertar Ingredientes
		$ingredients = json_decode($_POST['ingredients'], true);
		if (!empty($ingredients)) {
			$stmtIng = $db->prepare("INSERT INTO recipe_ingredients (id_recipe_ingredient, id_raw_material_ingredient, qty_ingredient, date_created_ingredient) VALUES (:id_rec, :id_raw, :qty, NOW())");
			foreach($ingredients as $ing) {
				$stmtIng->execute([
					':id_rec' => $id_recipe,
					':id_raw' => $ing['id_raw'],
					':qty' => $ing['qty']
				]);
			}
		}

		// 4. Insertar Mano de Obra
		$labors = json_decode($_POST['labor'], true);
		if (!empty($labors)) {
			$stmtLab = $db->prepare("INSERT INTO recipe_labor (id_recipe_labor, description_labor, type_labor, date_created_labor) VALUES (:id_rec, :desc, :type, NOW())");
			foreach($labors as $lab) {
				$stmtLab->execute([
					':id_rec' => $id_recipe,
					':desc' => $lab['desc'],
					':type' => $lab['type']
				]);
			}
		}

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("recipes_production error: " . $e->getMessage()); echo "error|Error al procesar la operación.";
	}
	exit;
}

/*=============================================
Completar Producción (Laboratorio)
=============================================*/
if(isset($_POST["completeProduction"])){
	// require_once removed
	$db = LocalConnection::connect();
	
	$id_production = $_POST['id_production'];
	$id_recipe = $_POST['id_recipe'];
	$batches = (float)$_POST['batches'];
	$id_product = $_POST['id_product'];

	$extra_mats = json_decode($_POST['extra_mats'] ?? '[]', true);
	$extra_mo = (float)($_POST['extra_mo'] ?? 0);
	$extra_cif = (float)($_POST['extra_cif'] ?? 0);
	
	$pkg_final_qty = (float)($_POST['pkg_final_qty'] ?? 0);
	$pkg_final_name = trim($_POST['pkg_final_name'] ?? '');
    $pkg_envase_type = trim($_POST['pkg_envase_type'] ?? 'und');
	$id_office = 0; // Lab production always dumps to Lab warehouse (ID 0)
	
	$real_bulk_qty = isset($_POST['real_bulk_qty']) && $_POST['real_bulk_qty'] !== '' 
		? (float)$_POST['real_bulk_qty'] 
		: null;
	$original_bulk_qty = isset($_POST['original_bulk_qty']) && $_POST['original_bulk_qty'] !== '' 
		? (float)$_POST['original_bulk_qty'] 
		: null;

	$yield_variance = null;
	$yield_variance_pct = null;

	if ($real_bulk_qty !== null && $original_bulk_qty !== null) {
		$yield_variance = $real_bulk_qty - $original_bulk_qty;
		$yield_variance_pct = ($original_bulk_qty > 0) ? ($yield_variance / $original_bulk_qty * 100) : 0;
	}
	
	$waste_packaged_qty = (float)($_POST['waste_packaged_qty'] ?? 0);
	$waste_loss_qty = (float)($_POST['waste_loss_qty'] ?? 0);
	$waste_qty_production = $waste_packaged_qty + $waste_loss_qty;
	$id_admin = $_POST['id_admin'] ?? 1;
	
	try {
		$db->beginTransaction();

		// SEC-02: Check idempotency
		$stmtCheckStatus = $db->prepare("SELECT status_production FROM productions WHERE id_production = :id");
		$stmtCheckStatus->execute([':id' => $id_production]);
		$status = $stmtCheckStatus->fetchColumn();
		if($status === 'completado') {
			echo "error|La producción ya fue completada anteriormente.";
			$db->rollBack();
			exit;
		}

		// 1. Obtener y validar stock de ingredientes
		$stmtIng = $db->prepare("SELECT id_raw_material_ingredient, qty_ingredient FROM recipe_ingredients WHERE id_recipe_ingredient = :id_recipe");
		$stmtIng->execute([':id_recipe' => $id_recipe]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
		
		$total_mp_cost = 0;
		$costs_snapshot = [];

		foreach($ingredients as $ing) {
			$id_raw = $ing['id_raw_material_ingredient'];
			$qty_needed = $ing['qty_ingredient'] * $batches;

			// BUG-02 Fix: Stock ya fue descontado en startProduction. 
			// Solo obtenemos el precio actual de la última entrada aprobada.

			// Obtener precio actual (precio fijo si es no_stock, si no última entrada)
			$stmtPrice = $db->prepare("
				SELECT 
					rm.no_stock_raw_material, 
					rm.price_raw_material,
					(SELECT unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = rm.id_raw_material AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1) as last_price,
					(SELECT id_entry FROM raw_material_entries WHERE id_raw_material_entry = rm.id_raw_material AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1) as last_entry_id
				FROM raw_materials rm 
				WHERE rm.id_raw_material = :id
			");
			$stmtPrice->execute([':id' => $id_raw]);
			$price_info = $stmtPrice->fetch(PDO::FETCH_ASSOC);
			
			if ($price_info && intval($price_info['no_stock_raw_material'] ?? 0) === 1) {
				$unit_price = (float)$price_info['price_raw_material'];
				$id_entry = 0;
			} else {
				$unit_price = $price_info ? (float)$price_info['last_price'] : 0;
				$id_entry = $price_info ? (int)$price_info['last_entry_id'] : 0;
			}

			$subtotal = $unit_price * $qty_needed;
			$total_mp_cost += $subtotal;

			// Guardar snapshot para luego
			$costs_snapshot[] = [
				'id_raw' => $id_raw,
				'id_supply' => 0,
				'id_entry' => $id_entry,
				'qty' => $qty_needed,
				'price' => $unit_price,
				'subtotal' => $subtotal
			];
		}

		// 1.5. Procesar Materiales Extra de Envasado
		foreach($extra_mats as $ext) {
			$id_raw_val = $ext['id_raw'];
			$qty_needed = (float)$ext['qty'];

			if (strpos($id_raw_val, 'ls_') === 0) {
				// Es un insumo de lab_supplies
				$id_supply = (int)str_replace('ls_', '', $id_raw_val);
				
				$stmtCheck = $db->prepare("SELECT name_supply as name_raw_material, stock_supply as stock_raw_material, price_supply FROM lab_supplies WHERE id_supply = :id");
				$stmtCheck->execute([':id' => $id_supply]);
				$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

				if($mp_info && $mp_info['stock_raw_material'] < $qty_needed) {
					echo "stock_insuficiente_envasado|" . $mp_info['name_raw_material'];
					$db->rollBack();
					exit;
				}

				$unit_price = $mp_info ? (float)$mp_info['price_supply'] : 0;
				$id_entry = 0;
				
				$subtotal = $unit_price * $qty_needed;
				$total_mp_cost += $subtotal;

				$costs_snapshot[] = [
					'id_raw' => 0,
					'id_supply' => $id_supply,
					'id_entry' => $id_entry,
					'qty' => $qty_needed,
					'price' => $unit_price,
					'subtotal' => $subtotal
				];

				// Descontar stock de lab_supplies e insertar movimiento
				$stmtUpdMP = $db->prepare("UPDATE lab_supplies SET stock_supply = stock_supply - :qty WHERE id_supply = :id");
				$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_supply]);

				$stmtLog = $db->prepare("INSERT INTO lab_supply_entries (id_supply_entry, qty_entry, type_entry, concept_entry, notes_entry, status_entry, id_admin_entry, date_entry) VALUES (:id, :qty, 'egreso', 'envasado', 'Uso en producción de lote', 'aprobado', :admin, CURDATE())");
				$stmtLog->execute([':id' => $id_supply, ':qty' => $qty_needed, ':admin' => $id_admin]);

			} else {
				// Es un insumo de raw_materials
				$id_raw = (int)$id_raw_val;
				$stmtCheck = $db->prepare("SELECT name_raw_material, stock_raw_material, no_stock_raw_material, price_raw_material FROM raw_materials WHERE id_raw_material = :id");
				$stmtCheck->execute([':id' => $id_raw]);
				$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

				if($mp_info && !intval($mp_info['no_stock_raw_material']) && $mp_info['stock_raw_material'] < $qty_needed) {
					echo "stock_insuficiente_envasado|" . $mp_info['name_raw_material'];
					$db->rollBack();
					exit;
				}

				if($mp_info && intval($mp_info['no_stock_raw_material'] ?? 0) === 1) {
					$unit_price = (float)$mp_info['price_raw_material'];
					$id_entry = 0;
				} else {
					$stmtPrice = $db->prepare("SELECT id_entry, unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
					$stmtPrice->execute([':id' => $id_raw]);
					$price_info = $stmtPrice->fetch(PDO::FETCH_ASSOC);
					$unit_price = $price_info ? (float)$price_info['unit_price_entry'] : 0;
					$id_entry = $price_info ? (int)$price_info['id_entry'] : 0;
				}

				$subtotal = $unit_price * $qty_needed;
				$total_mp_cost += $subtotal;

				$costs_snapshot[] = [
					'id_raw' => $id_raw,
					'id_supply' => 0,
					'id_entry' => $id_entry,
					'qty' => $qty_needed,
					'price' => $unit_price,
					'subtotal' => $subtotal
				];

				if(!intval($mp_info['no_stock_raw_material'] ?? 0)) {
					$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
					$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
				}
			}
		}

		// 2. Calcular otros costos (Mano de obra y CIF)
		// Obtener costos iniciales guardados
		$stmtProdInfo = $db->prepare("SELECT proj_labor_cost, proj_indirect_cost FROM productions WHERE id_production = :id");
		$stmtProdInfo->execute([':id' => $id_production]);
		$prod_info = $stmtProdInfo->fetch(PDO::FETCH_ASSOC);
		
		$total_mo_cost = ($prod_info ? (float)$prod_info['proj_labor_cost'] : 0) + $extra_mo;
		$total_cif_cost = ($prod_info ? (float)$prod_info['proj_indirect_cost'] : 0) + $extra_cif;

		$total_production_cost = $total_mp_cost + $total_mo_cost + $total_cif_cost;

		// 3. Registrar snapshot en production_material_costs
		$stmtSnap = $db->prepare("INSERT INTO production_material_costs (id_production_mat_cost, id_raw_material_mat_cost, id_supply_mat_cost, id_entry_used_mat_cost, qty_used_mat_cost, unit_price_at_production, total_cost_mat_cost) VALUES (:id_prod, :id_raw, :id_sup, :id_ent, :qty, :price, :sub)");
		foreach($costs_snapshot as $snap) {
			$stmtSnap->execute([
				':id_prod' => $id_production,
				':id_raw' => $snap['id_raw'] ?? 0,
				':id_sup' => $snap['id_supply'] ?? 0,
				':id_ent' => $snap['id_entry'],
				':qty' => $snap['qty'],
				':price' => $snap['price'],
				':sub' => $snap['subtotal']
			]);
		}

		// 4. Actualizar Producción (Estado y Costo)
		$unit_cost_final = $pkg_final_qty > 0 ? ($total_production_cost / $pkg_final_qty) : 0;
		
		$real_mo = $prod_info ? (float)$prod_info['proj_labor_cost'] : 0;
		$real_cif = $prod_info ? (float)$prod_info['proj_indirect_cost'] : 0;

		$updateProdData = [
			':cost' => $total_production_cost, 
			':unit_cost' => $unit_cost_final,
			':real_mo' => $real_mo,
			':real_cif' => $real_cif,
			':pkg_mo' => $extra_mo,
			':pkg_cif' => $extra_cif,
			':real_bulk_qty' => $real_bulk_qty,
			':yield_variance' => $yield_variance,
			':yield_variance_pct' => $yield_variance_pct,
			':waste_qty' => $waste_qty_production,
			':waste_pkg' => $waste_packaged_qty,
			':waste_loss' => $waste_loss_qty,
			':qty_packaged' => $pkg_final_qty,
			':id' => $id_production
		];
		$id_packaged_product = 0;

		// 5. Inventario de Productos Finales (is_compound_product = 1)
		if($id_product > 0 && $pkg_final_qty > 0) {
			// Solo actualizamos la unidad, el stock se mantiene hasta pasar QC
			$stmtUpdProd = $db->prepare("
				UPDATE products
				SET unit_product = :unit,
					is_compound_product = 1,
					origin_office_product = CASE WHEN COALESCE(origin_office_product, 0) = 0 THEN :office ELSE origin_office_product END
				WHERE id_product = :id
			");
			$stmtUpdProd->execute([':unit' => $pkg_envase_type, ':office' => $id_office, ':id' => $id_product]);
			$id_packaged_product = $id_product;
		}

		// BUG-04, BUG-05 fix: Se elimina el segundo UPDATE del producto base
		
		$stmtUpdateProd = $db->prepare("UPDATE productions SET 
			status_production = 'pendiente_qc', 
			real_total_cost = :cost, 
			real_unit_cost = :unit_cost, 
			id_packaged_product = :id_pkg, 
			real_labor_cost = :real_mo, 
			real_indirect_cost = :real_cif, 
			pkg_labor_cost = :pkg_mo, 
			pkg_indirect_cost = :pkg_cif, 
			real_bulk_qty = :real_bulk_qty,
			yield_variance = :yield_variance,
			yield_variance_pct = :yield_variance_pct,
			waste_qty_production = :waste_qty,
			waste_packaged_qty = :waste_pkg,
			waste_loss_qty = :waste_loss,
			qty_packaged_production = :qty_packaged,
			date_updated_production = NOW() 
		WHERE id_production = :id");
		$updateProdData[':id_pkg'] = $id_packaged_product;
		$stmtUpdateProd->execute($updateProdData);

		if ($waste_packaged_qty > 0 && $id_packaged_product > 0) {
			$waste_supply_raw = $_POST['waste_packaged_id_raw'] ?? '';
			
			if (strpos($waste_supply_raw, 'ls_') === 0) {
				$waste_supply_id = (int)str_replace('ls_', '', $waste_supply_raw);
				if ($waste_supply_id > 0) {
					$stmtSup = $db->prepare("UPDATE lab_supplies SET stock_supply = stock_supply - :qty WHERE id_supply = :id");
					$stmtSup->execute([':qty' => $waste_packaged_qty, ':id' => $waste_supply_id]);
					
					// Registrar en lab_supply_entries
					$stmtLog = $db->prepare("INSERT INTO lab_supply_entries (id_supply_entry, qty_entry, type_entry, concept_entry, notes_entry, status_entry, id_admin_entry, date_entry) VALUES (:id, :qty, 'egreso', 'merma', 'Merma por envasado', 'aprobado', :admin, CURDATE())");
					$stmtLog->execute([':id' => $waste_supply_id, ':qty' => $waste_packaged_qty, ':admin' => $id_admin]);
				}
			} else {
				$waste_supply_id = (int)$waste_supply_raw;
				if ($waste_supply_id > 0) {
					$stmtSup = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
					$stmtSup->execute([':qty' => $waste_packaged_qty, ':id' => $waste_supply_id]);
				}
			}
			
			$stmtWaste = $db->prepare("INSERT INTO waste_packaged (id_production_waste, id_product_waste, qty_waste, id_office_waste, status_waste, id_admin_waste, date_created_waste) VALUES (:prod, :product, :qty, :office, 'en_almacen', :admin, NOW())");
			$stmtWaste->execute([
				':prod' => $id_production,
				':product' => $id_packaged_product,
				':qty' => $waste_packaged_qty,
				':office' => $id_office,
				':admin' => $id_admin
			]);
		}

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		error_log("recipes_production error: " . $e->getMessage()); echo "error|Error al procesar la operación.";
	}
}
/*=============================================
Sobrescribir Precio Manualmente (Tu rama actual)
=============================================*/
if(isset($_POST['overridePriceCart'])){
    $ajax = new PosController();
    $ajax -> idSaleOverride = $_POST['idSaleOverride'];
    $ajax -> idOrderOverride = $_POST['idOrderOverride'];
    $ajax -> idProductOverride = $_POST['idProductOverride'];
    $ajax -> originalPriceOverride = $_POST['originalPriceOverride'];
    $ajax -> newPriceOverride = $_POST['newPriceOverride'];
    $ajax -> reasonOverride = $_POST['reasonOverride'];
    $ajax -> qtyOverride = isset($_POST['qtyOverride']) ? (int)$_POST['qtyOverride'] : 1;
    $ajax -> token = $_POST['token'];
    $ajax -> seller = $_POST['seller'];
    $ajax -> overridePrice();
}

/*=============================================
Obtener Mano de Obra de Receta (Rama shiwasmi)
=============================================*/
if(isset($_POST["getRecipeLabor"])){
    // require_once removed
    $id_recipe = $_POST["id_recipe"];
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT id_labor, description_labor, type_labor FROM recipe_labor WHERE id_recipe_labor = :id");
    $stmt->execute([':id' => $id_recipe]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
    exit;
}

/*=============================================
Obtener Ingredientes de Receta
=============================================*/
if(isset($_POST["getRecipeIngredients"])){
    $id_recipe = $_POST["id_recipe"];
    $db = LocalConnection::connect();
    $stmt = $db->prepare("SELECT ri.id_raw_material_ingredient as id_raw, ri.qty_ingredient as qty, rm.name_raw_material as name, rm.unit_raw_material as unit FROM recipe_ingredients ri JOIN raw_materials rm ON ri.id_raw_material_ingredient = rm.id_raw_material WHERE ri.id_recipe_ingredient = :id");
    $stmt->execute([':id' => $id_recipe]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);
    exit;
}

/*=============================================
Iniciar Producción (En Proceso)
=============================================*/
if(isset($_POST["startProduction"])){
	$db = LocalConnection::connect();
	$id = $_POST['id_production'];

	try {
		$db->beginTransaction();

		$stmtCheckStatus = $db->prepare("SELECT status_production, id_recipe_production, batches_production FROM productions WHERE id_production = :id");
		$stmtCheckStatus->execute([':id' => $id]);
		$prod = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);
		if(!$prod || $prod['status_production'] !== 'pendiente') {
			echo "error|La producción no está pendiente.";
			$db->rollBack();
			exit;
		}

		$id_recipe = $prod['id_recipe_production'];
		$batches = (float)$prod['batches_production'];

		// Verificar y descontar stock de ingredientes al iniciar
		$stmtIng = $db->prepare("SELECT id_raw_material_ingredient, qty_ingredient FROM recipe_ingredients WHERE id_recipe_ingredient = :id_recipe");
		$stmtIng->execute([':id_recipe' => $id_recipe]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

		foreach($ingredients as $ing) {
			$id_raw = $ing['id_raw_material_ingredient'];
			$qty_needed = $ing['qty_ingredient'] * $batches;

			$stmtCheck = $db->prepare("SELECT name_raw_material, stock_raw_material, no_stock_raw_material FROM raw_materials WHERE id_raw_material = :id");
			$stmtCheck->execute([':id' => $id_raw]);
			$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

			if($mp_info && !intval($mp_info['no_stock_raw_material']) && $mp_info['stock_raw_material'] < $qty_needed) {
				echo "stock_insuficiente|" . $mp_info['name_raw_material'];
				$db->rollBack();
				exit;
			}

			// Descontar stock solo si la materia prima lleva control de stock
			if(!intval($mp_info['no_stock_raw_material'] ?? 0)) {
				$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
				$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
			}
		}

		$stmt = $db->prepare("UPDATE productions SET status_production = 'en_proceso', start_date_production = NOW() WHERE id_production = :id");
		if($stmt->execute([':id' => $id])){
			$db->commit();
			echo "ok";
		}else{
			$db->rollBack();
			echo "error|Error al actualizar el estado.";
		}
	} catch (Exception $e) {
		$db->rollBack();
		error_log("recipes_production error: " . $e->getMessage()); echo "error|Error al procesar la operación.";
	}
	exit;
}

/*=============================================
Obtener Detalles de Producción
=============================================*/
if(isset($_POST["getProductionDetails"])){
	$id_production = $_POST["id_production"];
	$db = LocalConnection::connect();
	
	// Datos generales
	$stmtProd = $db->prepare("SELECT p.*, prod.title_product, prod.unit_product,
			   qc.qty_approved_qc, qc.qty_rejected_qc, qc.result_qc, qc.notes_qc AS qc_notes,
			   qc.date_created_qc, a.name_admin AS qc_inspector_name
		FROM productions p 
		JOIN products prod ON p.id_product_production = prod.id_product 
		LEFT JOIN quality_checks qc ON qc.id_production_qc = p.id_production
		LEFT JOIN admins a ON qc.id_admin_qc = a.id_admin
		WHERE p.id_production = :id");
	$stmtProd->execute([':id' => $id_production]);
	$production = $stmtProd->fetch(PDO::FETCH_ASSOC);

    // Insumos
    $stmtMat = $db->prepare("SELECT pm.*, 
               COALESCE(rm.name_raw_material, ls.name_supply) AS name_raw_material, 
               COALESCE(rm.unit_raw_material, ls.unit_supply) AS unit_raw_material 
        FROM production_material_costs pm 
        LEFT JOIN raw_materials rm ON pm.id_raw_material_mat_cost = rm.id_raw_material 
        LEFT JOIN lab_supplies ls ON pm.id_supply_mat_cost = ls.id_supply
        WHERE pm.id_production_mat_cost = :id");
    $stmtMat->execute([':id' => $id_production]);
    $materials = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'production' => $production,
        'materials' => $materials
    ]);
    exit;
}

/*=============================================
Obtener Datos de Receta para Edición
=============================================*/
if(isset($_POST["getRecipeDataForEdit"])){
    $id_recipe = $_POST["id_recipe"];
    $db = LocalConnection::connect();
    
    $stmtRec = $db->prepare("SELECT r.*, p.title_product 
        FROM recipes r 
        JOIN products p ON r.id_product_recipe = p.id_product 
        WHERE id_recipe = :id");
    $stmtRec->execute([':id' => $id_recipe]);
    $recipe = $stmtRec->fetch(PDO::FETCH_ASSOC);

    $stmtIng = $db->prepare("SELECT * FROM recipe_ingredients WHERE id_recipe_ingredient = :id");
    $stmtIng->execute([':id' => $id_recipe]);
    $ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

    $stmtLab = $db->prepare("SELECT * FROM recipe_labor WHERE id_recipe_labor = :id");
    $stmtLab->execute([':id' => $id_recipe]);
    $labor = $stmtLab->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'recipe' => $recipe,
        'ingredients' => $ingredients,
        'labor' => $labor
    ]);
    exit;
}

/*=============================================
Editar Receta
=============================================*/
if(isset($_POST["editRecipe"])){
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();

        $id_recipe = $_POST['id_recipe'];
        $name_product = $_POST['name_product'];
        $batch_size = (float)$_POST['batch_size'];
        $unit_batch = $_POST['unit_batch'];
        
        $ingredients = json_decode($_POST['ingredients'], true);
        $labor = json_decode($_POST['labor'], true);

		// Obtener ID del producto asociado y sucursal
		$stmtGetProd = $db->prepare("SELECT id_product_recipe, id_office_recipe FROM recipes WHERE id_recipe = :id");
		$stmtGetProd->execute([':id' => $id_recipe]);
		$recipeData = $stmtGetProd->fetch(PDO::FETCH_ASSOC);

		if (!$recipeData) {
			echo "error|Receta no encontrada.";
			$db->rollBack();
			exit;
		}

		$id_product = $recipeData['id_product_recipe'];
		$id_office = $recipeData['id_office_recipe'];

		// Validar duplicado global (excluyendo el actual)
		$stmtDup = $db->prepare("SELECT id_product FROM products WHERE title_product = :name AND id_product != :id_prod AND status_product = 1 LIMIT 1");
		$stmtDup->execute([
			':name' => $name_product,
			':id_prod' => $id_product
		]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe un producto con ese nombre en el catálogo global.";
			$db->rollBack();
			exit;
		}

        // Actualizar producto
        $stmtProd = $db->prepare("UPDATE products SET title_product = :name, unit_product = :unit WHERE id_product = :id_prod");
        $stmtProd->execute([
            ':name' => $name_product,
            ':unit' => $unit_batch,
            ':id_prod' => $id_product
        ]);

        // Actualizar receta
        $stmtRec = $db->prepare("UPDATE recipes SET batch_size_recipe = :batch, unit_batch_recipe = :unit WHERE id_recipe = :id");
        $stmtRec->execute([
            ':batch' => $batch_size,
            ':unit' => $unit_batch,
            ':id' => $id_recipe
        ]);

        // Reemplazar Insumos
        $stmtDelIng = $db->prepare("DELETE FROM recipe_ingredients WHERE id_recipe_ingredient = :id");
        $stmtDelIng->execute([':id' => $id_recipe]);

		if(!empty($ingredients)) {
			$stmtIng = $db->prepare("INSERT INTO recipe_ingredients (id_recipe_ingredient, id_raw_material_ingredient, qty_ingredient) VALUES (:id_rec, :id_raw, :qty)");
			foreach($ingredients as $ing) {
				$stmtIng->execute([
					':id_rec' => $id_recipe,
					':id_raw' => $ing['id_raw'],
					':qty' => $ing['qty']
				]);
			}
		}

        // Reemplazar Mano de Obra
        $stmtDelLab = $db->prepare("DELETE FROM recipe_labor WHERE id_recipe_labor = :id");
        $stmtDelLab->execute([':id' => $id_recipe]);

        if(!empty($labor)) {
            $stmtLab = $db->prepare("INSERT INTO recipe_labor (id_recipe_labor, description_labor, type_labor) VALUES (:id_rec, :desc, :type)");
            foreach($labor as $lab) {
                $stmtLab->execute([
                    ':id_rec' => $id_recipe,
                    ':desc' => $lab['desc'],
                    ':type' => $lab['type']
                ]);
            }
        }

        $db->commit();
        echo "ok";
    } catch (Exception $e) {
        $db->rollBack();
        error_log("recipes_production error: " . $e->getMessage()); echo "error|Error al procesar la operación.";
    }
    exit;
}

/*=============================================
Eliminar Receta
=============================================*/
if(isset($_POST["deleteRecipe"])){
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();
        
        $id_recipe = $_POST['id_recipe'];

		// Obtener id_product antes de eliminar
		$stmtProd = $db->prepare("SELECT id_product_recipe FROM recipes WHERE id_recipe = :id");
		$stmtProd->execute([':id' => $id_recipe]);
		$id_product = $stmtProd->fetchColumn();

		$stmtDelIng = $db->prepare("DELETE FROM recipe_ingredients WHERE id_recipe_ingredient = :id");
		$stmtDelIng->execute([':id' => $id_recipe]);

        $stmtDelLab = $db->prepare("DELETE FROM recipe_labor WHERE id_recipe_labor = :id");
        $stmtDelLab->execute([':id' => $id_recipe]);

        $stmtDelRec = $db->prepare("DELETE FROM recipes WHERE id_recipe = :id");
        $stmtDelRec->execute([':id' => $id_recipe]);

		// Eliminamos solo la receta y sus ingredientes/mano de obra, pero MANTENEMOS el producto.
		// Opcional: podemos actualizar el producto para indicar que ya no tiene receta,
		// pero con la nueva lógica (has_recipe) es suficiente eliminar de la tabla recipes.
		
		/* 
		if ($id_product) {
			$stmtDelProduct = $db->prepare("DELETE FROM products WHERE id_product = :id");
			$stmtDelProduct->execute([':id' => $id_product]);
		}
		*/

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error";
	}
	exit;
}

//=====================================
// GET PRODUCTION LOTS
//=====================================
if(isset($_POST["getProductionLots"]) && $_POST["getProductionLots"] == "ok") {
	$db = LocalConnection::connect();
	$id_packaged_product = $_POST["id_packaged_product"];
	$stmt = $db->prepare("SELECT id_production, total_qty_production, real_unit_cost, real_total_cost, date_updated_production FROM productions WHERE id_packaged_product = :id AND status_production IN ('completado','pendiente_qc') ORDER BY date_updated_production DESC");
	$stmt->execute([':id' => $id_packaged_product]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PRODUCTS WITHOUT RECIPE
//=====================================
if(isset($_POST["getProductsWithoutRecipe"]) && $_POST["getProductsWithoutRecipe"] == "ok") {
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT id_product, title_product, unit_product
		FROM products
		WHERE status_product = 1
		  AND id_product NOT IN (SELECT id_product_recipe FROM recipes)
		ORDER BY title_product ASC
	");
	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(['status' => 200, 'results' => $rows]);
	exit;
}

//=====================================
// GET SUB WAREHOUSE STOCK
//=====================================
