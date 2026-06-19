<?php
/** Migraciones de esquema — ejecutar vía bin/migrate.php, no en cada request HTTP. */
class SchemaMigrator {
	public static function isApplied(PDO $link): bool {
		try {
			$link->query("SELECT 1 FROM schema_migrations LIMIT 1");
			$row = $link->query("SELECT COUNT(*) FROM schema_migrations WHERE version >= 4")->fetchColumn();
			return (int)$row > 0;
		} catch (Throwable $e) { return false; }
	}
	public static function run(PDO $link): void {
		$link->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
			id INT AUTO_INCREMENT PRIMARY KEY,
			version INT NOT NULL,
			name VARCHAR(255) NOT NULL,
			applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

		$queries = array(
			"CREATE TABLE IF NOT EXISTS product_inventory (
				id_inventory INT(11) NOT NULL AUTO_INCREMENT,
				id_product_inventory INT(11) NOT NULL,
				id_office_inventory INT(11) NOT NULL,
				stock_inventory DOUBLE DEFAULT 0,
				status_inventory INT(11) DEFAULT 1,
				date_created_inventory DATE DEFAULT NULL,
				date_updated_inventory TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_inventory),
				UNIQUE KEY uq_product_office (id_product_inventory, id_office_inventory)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS product_prices (
				id_price INT(11) NOT NULL AUTO_INCREMENT,
				id_product_price INT(11) NOT NULL,
				id_office_price INT(11) DEFAULT 0,
				id_warehouse_price INT(11) DEFAULT 0,
				price_sale DOUBLE DEFAULT 0,
				price_wholesale DOUBLE DEFAULT 0,
				wholesale_qty DOUBLE DEFAULT 0,
				cost_reference DOUBLE DEFAULT 0,
				source_price VARCHAR(40) DEFAULT 'manual',
				status_price INT(11) DEFAULT 1,
				id_admin_price INT(11) DEFAULT 0,
				date_created_price TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				date_updated_price TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_price),
				KEY idx_price_product_scope (id_product_price, id_office_price, status_price)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS stock_movements (
				id_movement INT(11) NOT NULL AUTO_INCREMENT,
				id_product_movement INT(11) NOT NULL,
				id_office_movement INT(11) NOT NULL,
				qty_movement DOUBLE NOT NULL,
				type_movement VARCHAR(40) NOT NULL,
				id_admin_movement INT(11) DEFAULT 0,
				id_transfer_movement INT(11) DEFAULT NULL,
				notes_movement TEXT DEFAULT NULL,
				date_created_movement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id_movement),
				KEY idx_stock_mov_product_office (id_product_movement, id_office_movement),
				KEY idx_stock_mov_transfer (id_transfer_movement)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS stock_transfers (
				id_transfer INT(11) NOT NULL AUTO_INCREMENT,
				id_origin_office INT(11) NOT NULL,
				id_dest_office INT(11) NOT NULL,
				id_product_transfer INT(11) NOT NULL,
				qty_transfer DOUBLE NOT NULL,
				id_admin_transfer INT(11) NOT NULL,
				id_request_transfer INT(11) DEFAULT NULL,
				notes_transfer TEXT DEFAULT NULL,
				status_transfer VARCHAR(50) DEFAULT 'pendiente',
				unit_cost_transfer DOUBLE DEFAULT 0,
				unit_price_transfer DOUBLE DEFAULT 0,
				wholesale_price_transfer DOUBLE DEFAULT 0,
				wholesale_qty_transfer DOUBLE DEFAULT 0,
				id_confirmed_by_transfer INT(11) DEFAULT NULL,
				received_date_transfer DATETIME DEFAULT NULL,
				reject_reason_transfer TEXT DEFAULT NULL,
				date_created_transfer DATE DEFAULT NULL,
				date_updated_transfer TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_transfer),
				KEY idx_transfer_dest_status (id_dest_office, status_transfer),
				KEY idx_transfer_product (id_product_transfer)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS quality_checks (
				id_qc INT(11) NOT NULL AUTO_INCREMENT,
				id_production_qc INT(11) NOT NULL,
				id_admin_qc INT(11) NOT NULL,
				id_office_qc INT(11) NOT NULL,
				result_qc VARCHAR(30) DEFAULT NULL,
				qty_approved_qc DOUBLE DEFAULT 0,
				qty_rejected_qc DOUBLE DEFAULT 0,
				notes_qc TEXT DEFAULT NULL,
				date_created_qc DATE DEFAULT NULL,
				date_updated_qc TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_qc)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS warehouses (
				id_warehouse INT(11) NOT NULL AUTO_INCREMENT,
				title_warehouse TEXT DEFAULT NULL,
				address_warehouse TEXT DEFAULT NULL,
				phone_warehouse TEXT DEFAULT NULL,
				id_office_warehouse INT(11) DEFAULT NULL,
				date_created_warehouse DATE DEFAULT NULL,
				PRIMARY KEY (id_warehouse)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS sub_warehouses (
				id_sub_warehouse INT(11) NOT NULL AUTO_INCREMENT,
				id_admin_sub_warehouse INT(11) NOT NULL,
				id_office_sub_warehouse INT(11) NOT NULL,
				name_sub_warehouse TEXT DEFAULT NULL,
				status_sub_warehouse INT(11) DEFAULT 1,
				date_created_sub_warehouse DATE DEFAULT NULL,
				date_updated_sub_warehouse TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_sub_warehouse)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS warehouse_assignments (
				id_assignment INT(11) NOT NULL AUTO_INCREMENT,
				id_sub_warehouse_assignment INT(11) NOT NULL,
				id_product_assignment INT(11) NOT NULL,
				qty_assignment DOUBLE NOT NULL,
				id_dispatched_by INT(11) NOT NULL,
				id_request_assignment INT(11) DEFAULT NULL,
				type_assignment TEXT DEFAULT 'despacho',
				notes_assignment TEXT DEFAULT NULL,
				date_created_assignment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id_assignment)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS inventory_requests (
				id_request INT(11) NOT NULL AUTO_INCREMENT,
				id_admin_request INT(11) NOT NULL,
				id_office_request INT(11) NOT NULL,
				id_product_request INT(11) NOT NULL,
				qty_request DOUBLE NOT NULL,
				status_request TEXT DEFAULT 'pendiente',
				id_dispatched_by_request INT(11) DEFAULT NULL,
				qty_dispatched_request DOUBLE DEFAULT NULL,
				notes_request TEXT DEFAULT NULL,
				notes_dispatcher_request TEXT DEFAULT NULL,
				id_warehouse_request INT(11) DEFAULT NULL,
				date_created_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				date_updated_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_request)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS raw_materials (
				id_raw_material INT(11) NOT NULL AUTO_INCREMENT,
				name_raw_material TEXT NOT NULL,
				unit_raw_material TEXT NOT NULL,
				measure_type ENUM('weight','volume','unit') DEFAULT 'unit',
				description_raw_material TEXT DEFAULT NULL,
				stock_raw_material DOUBLE DEFAULT 0,
				id_office_raw_material INT(11) NOT NULL,
				id_admin_raw_material INT(11) NOT NULL,
				status_raw_material INT(11) DEFAULT 1,
				is_insumo TINYINT(1) DEFAULT 0,
				date_created_raw_material DATE DEFAULT NULL,
				date_updated_raw_material TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_raw_material)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS raw_material_entries (
				id_entry INT(11) NOT NULL AUTO_INCREMENT,
				id_raw_material_entry INT(11) NOT NULL,
				qty_entry DOUBLE NOT NULL,
				unit_price_entry DOUBLE DEFAULT 0,
				total_cost_entry DOUBLE DEFAULT 0,
				lot_number_entry TEXT DEFAULT NULL,
				supplier_entry TEXT DEFAULT NULL,
				notes_entry TEXT DEFAULT NULL,
				status_entry TEXT DEFAULT 'pendiente',
				id_admin_entry INT(11) NOT NULL,
				id_approved_by_entry INT(11) DEFAULT NULL,
				date_entry DATE DEFAULT NULL,
				date_approved_entry DATE DEFAULT NULL,
				date_created_entry DATE DEFAULT NULL,
				date_updated_entry TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_entry)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipes (
				id_recipe INT(11) NOT NULL AUTO_INCREMENT,
				id_product_recipe INT(11) NOT NULL,
				batch_size_recipe DOUBLE NOT NULL,
				unit_batch_recipe TEXT DEFAULT NULL,
				notes_recipe TEXT DEFAULT NULL,
				id_office_recipe INT(11) NOT NULL,
				id_admin_recipe INT(11) NOT NULL,
				date_created_recipe DATE DEFAULT NULL,
				date_updated_recipe TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_recipe),
				UNIQUE KEY uq_product_recipe (id_product_recipe)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_ingredients (
				id_ingredient INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_ingredient INT(11) NOT NULL,
				id_raw_material_ingredient INT(11) NOT NULL,
				qty_ingredient DOUBLE NOT NULL,
				notes_ingredient TEXT DEFAULT NULL,
				date_created_ingredient DATE DEFAULT NULL,
				date_updated_ingredient TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_ingredient)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_labor (
				id_labor INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_labor INT(11) NOT NULL,
				description_labor TEXT NOT NULL,
				type_labor TEXT DEFAULT 'fixed',
				hours_labor DOUBLE DEFAULT 0,
				cost_per_hour_labor DOUBLE DEFAULT 0,
				fixed_cost_labor DOUBLE DEFAULT 0,
				total_cost_labor DOUBLE DEFAULT 0,
				date_created_labor DATE DEFAULT NULL,
				date_updated_labor TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_labor)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS recipe_indirect_costs (
				id_recipe_indirect INT(11) NOT NULL AUTO_INCREMENT,
				id_recipe_indirect_recipe INT(11) NOT NULL,
				id_type_indirect INT(11) NOT NULL,
				amount_per_batch_indirect DOUBLE NOT NULL,
				date_created_indirect DATE DEFAULT NULL,
				date_updated_indirect TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_recipe_indirect)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS sale_payments (
				id_sale_payment INT(11) NOT NULL AUTO_INCREMENT,
				id_order_payment INT(11) NOT NULL,
				method_payment VARCHAR(30) DEFAULT NULL,
				reference_payment VARCHAR(255) DEFAULT NULL,
				file_payment VARCHAR(255) DEFAULT NULL,
				amount_payment DOUBLE DEFAULT 0,
				id_admin_payment INT(11) DEFAULT 0,
				date_created_payment TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id_sale_payment),
				KEY idx_order_payment (id_order_payment)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS packaging_catalog (
				id_packaging INT(11) NOT NULL AUTO_INCREMENT,
				name_packaging VARCHAR(255) NOT NULL,
				price_packaging DOUBLE DEFAULT 0,
				unit_packaging VARCHAR(50) DEFAULT 'unidades',
				status_packaging INT(11) DEFAULT 1,
				date_created_packaging DATE DEFAULT NULL,
				date_updated_packaging TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_packaging)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS order_expenses (
				id_expense INT(11) NOT NULL AUTO_INCREMENT,
				id_order_expense INT(11) NOT NULL,
				concept_expense VARCHAR(255) DEFAULT NULL,
				amount_expense DOUBLE DEFAULT 0,
				id_admin_expense INT(11) DEFAULT 0,
				date_created_expense DATE DEFAULT NULL,
				PRIMARY KEY (id_expense),
				KEY idx_order_expense (id_order_expense)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS incomes (
				id_income INT(11) NOT NULL AUTO_INCREMENT,
				concept_income TEXT DEFAULT NULL,
				amount_income DOUBLE DEFAULT 0,
				date_income TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				id_cash_income INT(11) DEFAULT 0,
				id_admin_income INT(11) DEFAULT 0,
				id_office_income INT(11) DEFAULT 0,
				date_created_income DATE DEFAULT NULL,
				date_updated_income TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id_income)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
		);

		foreach($queries as $query){
			try{
				$link->exec($query);
			}catch(Exception $e){
				error_log("[RUNTIME_SCHEMA] ".$e->getMessage());
			}
		}

		$alterQueries = array(
			"ALTER TABLE admins ADD COLUMN id_warehouse_admin INT(11) NULL DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN id_packaged_product INT DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN pkg_labor_cost DOUBLE DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN pkg_indirect_cost DOUBLE DEFAULT 0",
			"ALTER TABLE productions ADD COLUMN real_bulk_qty DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN yield_variance DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN yield_variance_pct DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_packaged_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_approved_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN qty_rejected_production DOUBLE DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN result_qc_production VARCHAR(30) DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN notes_qc_production TEXT DEFAULT NULL",
			"ALTER TABLE productions ADD COLUMN pkg_name_production TEXT DEFAULT NULL",
			"ALTER TABLE products ADD COLUMN is_manufactured_product TINYINT(1) DEFAULT 0",
			"ALTER TABLE products ADD COLUMN is_combo_product TINYINT(1) DEFAULT 0",
			"ALTER TABLE products ADD COLUMN source_type_product VARCHAR(30) DEFAULT 'externo'",
			"ALTER TABLE products ADD COLUMN origin_office_product INT(11) DEFAULT 0",
			"ALTER TABLE products ADD COLUMN initial_stock_product DOUBLE DEFAULT 0",
			"ALTER TABLE purchases ADD COLUMN may_product DOUBLE DEFAULT 0",
			"ALTER TABLE purchases ADD COLUMN wholesale_quantity INT(11) DEFAULT 0",
			"ALTER TABLE stock_transfers ADD COLUMN unit_cost_transfer DOUBLE DEFAULT 0",
			"ALTER TABLE stock_transfers ADD COLUMN id_request_transfer INT(11) DEFAULT NULL",
			"ALTER TABLE stock_transfers ADD COLUMN unit_price_transfer DOUBLE DEFAULT 0",
			"ALTER TABLE stock_transfers ADD COLUMN wholesale_price_transfer DOUBLE DEFAULT 0",
			"ALTER TABLE stock_transfers ADD COLUMN wholesale_qty_transfer DOUBLE DEFAULT 0",
			"ALTER TABLE stock_transfers ADD COLUMN id_confirmed_by_transfer INT(11) DEFAULT NULL",
			"ALTER TABLE stock_transfers ADD COLUMN received_date_transfer DATETIME DEFAULT NULL",
			"ALTER TABLE stock_transfers ADD COLUMN reject_reason_transfer TEXT DEFAULT NULL",
			// Comprobante de respaldo en abonos de crédito y liquidación de consignación
			"ALTER TABLE credit_payments ADD COLUMN file_payment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE consignments ADD COLUMN file_consignment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE consignments ADD COLUMN reference_consignment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE raw_materials ADD COLUMN is_insumo TINYINT(1) DEFAULT 0 AFTER status_raw_material",
			"ALTER TABLE bills ADD COLUMN id_cash_bill INT(11) DEFAULT 0",
			"ALTER TABLE orders ADD COLUMN invoice_order INT(11) DEFAULT 0",
			"ALTER TABLE production_material_costs ADD COLUMN id_supply_mat_cost INT(11) NULL DEFAULT 0"
		);

		foreach($alterQueries as $query){
			try{
				$link->exec($query);
			}catch(PDOException $e){
				// 42S21 = columna duplicada, 42S02 = tabla inexistente (créditos/consignaciones se crean fuera del runtime)
				if($e->getCode() != "42S21" && $e->getCode() != "42S02"){
					error_log("[RUNTIME_SCHEMA] ".$e->getMessage());
				}
			}
		}

		self::ensureProductInventoryUniqueIndex($link);

		// Índices para escalar los JOIN/WHERE calientes (claves foráneas y filtros frecuentes).
		// CREATE INDEX no soporta IF NOT EXISTS en MariaDB; ignoramos duplicados (1061/42000).
		$indexQueries = array(
			"CREATE INDEX idx_sales_order      ON sales(id_order_sale)",
			"CREATE INDEX idx_sales_office     ON sales(id_office_sale)",
			"CREATE INDEX idx_orders_office_st ON orders(id_office_order, status_order)",
			"CREATE INDEX idx_salepay_order    ON sale_payments(id_order_payment)",
			"CREATE INDEX idx_creditpay_credit ON credit_payments(id_credit_payment)",
			"CREATE INDEX idx_credits_client   ON credits(id_client_credit)",
			"CREATE INDEX idx_consign_office   ON consignments(id_office_consignment)",
			"CREATE INDEX idx_consignit_cons   ON consignment_items(id_consignment)",
			"CREATE INDEX idx_invoices_order   ON invoices(id_order_invoice)",
			"CREATE INDEX idx_prodinv_prod_off ON product_inventory(id_product_inventory, id_office_inventory)",
			"CREATE INDEX idx_price_prod_scope ON product_prices(id_product_price, id_office_price, status_price)",
			"CREATE INDEX idx_transfer_dest_st ON stock_transfers(id_dest_office, status_transfer)"
		);
		foreach($indexQueries as $query){
			try{
				$link->exec($query);
			}catch(PDOException $e){
				// 1061 = índice duplicado, 42S02/42S22 = tabla o columna inexistente
				$code = $e->getCode();
				if($code != "42000" && $code != "42S02" && $code != "42S22" && strpos($e->getMessage(), "Duplicate key name") === false){
					error_log("[RUNTIME_SCHEMA][INDEX] ".$e->getMessage());
				}
			}
		}

		try{
			$count = (int) $link->query("SELECT COUNT(*) FROM product_inventory")->fetchColumn();
			if($count === 0){
				$link->exec("INSERT INTO product_inventory
					(id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
					SELECT id_product, id_office_product,
						CAST(COALESCE(NULLIF(stock_product, ''), '0') AS DECIMAL(10,2)),
						status_product, date_created_product
					FROM products
					WHERE id_office_product > 0");
			}
		}catch(Exception $e){
			error_log("[RUNTIME_SCHEMA] ".$e->getMessage());
		}

		try {
			// Separar combos reales de productos fabricados por laboratorio.
			$link->exec("UPDATE products p
				SET is_combo_product = 1
				WHERE EXISTS (SELECT 1 FROM combo_items ci WHERE ci.id_combo_ci = p.id_product)");
			$link->exec("UPDATE products p
				SET is_manufactured_product = 1,
				    source_type_product = 'laboratorio',
				    origin_office_product = COALESCE(NULLIF(origin_office_product, 0), (
				    	SELECT r.id_office_recipe
				    	FROM recipes r
				    	WHERE r.id_product_recipe = p.id_product
				    	ORDER BY r.id_recipe DESC LIMIT 1
				    ), id_office_product)
				WHERE EXISTS (SELECT 1 FROM recipes r WHERE r.id_product_recipe = p.id_product)");
			$link->exec("UPDATE products p
				SET is_manufactured_product = 1,
				    source_type_product = 'laboratorio',
				    origin_office_product = COALESCE(NULLIF(origin_office_product, 0), (
				    	SELECT pr.id_office_production
				    	FROM productions pr
				    	WHERE pr.id_packaged_product = p.id_product
				    	ORDER BY pr.id_production DESC LIMIT 1
				    ))
				WHERE EXISTS (SELECT 1 FROM productions pr2 WHERE pr2.id_packaged_product = p.id_product)");
			$link->exec("UPDATE products p
				SET source_type_product = 'externo'
				WHERE COALESCE(is_manufactured_product, 0) = 0 AND COALESCE(source_type_product, '') = ''");
		} catch (Exception $e) {
			error_log("[RUNTIME_SCHEMA_FLAGS] ".$e->getMessage());
		}

		try {
			$countPrices = (int) $link->query("SELECT COUNT(*) FROM product_prices")->fetchColumn();
			if ($countPrices === 0) {
				$link->exec("INSERT INTO product_prices
					(id_product_price, id_office_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, date_created_price)
					SELECT p.id_product_purchase, 0,
					       COALESCE(NULLIF(p.price_purchase, 0), p.cost_purchase, 0),
					       COALESCE(p.may_product, 0),
					       COALESCE(p.wholesale_quantity, 0),
					       COALESCE(p.cost_purchase, 0),
					       'purchases_migration',
					       1,
					       NOW()
					FROM purchases p
					INNER JOIN (
						SELECT id_product_purchase, MAX(id_purchase) AS max_purchase
						FROM purchases
						WHERE COALESCE(cost_purchase, 0) > 0
						GROUP BY id_product_purchase
					) lastp ON lastp.max_purchase = p.id_purchase");
			}
		} catch (Exception $e) {
			error_log("[RUNTIME_SCHEMA_PRICES] ".$e->getMessage());
		}

		try {
			// Add incomes module if not exists
			$stmtMod = $link->query("SELECT id_module FROM modules WHERE title_module = 'incomes'");
			if (!($rowMod = $stmtMod->fetch())) {
				$link->exec("INSERT INTO modules (type_module, title_module, suffix_module, editable_module, date_created_module) VALUES ('tables', 'incomes', 'income', 1, CURDATE())");
				$id_module = $link->lastInsertId();
				
				// Add columns for incomes
				$cols = [
					['concept_income', 'Concepto', 'text', ''],
					['amount_income', 'Monto', 'money', ''],
					['date_income', 'Fecha', 'timestamp', ''],
					['id_cash_income', 'Caja', 'text', ''],
					['id_admin_income', 'Administrador', 'relations', 'admins'],
					['id_office_income', 'Sucursal', 'relations', 'offices']
				];
				foreach ($cols as $c) {
					$ins = $link->prepare("INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (?, ?, ?, ?, ?, CURDATE())");
					$ins->execute([$id_module, $c[0], $c[1], $c[2], $c[3]]);
				}
			}

			// Add id_cash_bill to bills columns if not exists
			$stmtBill = $link->query("SELECT id_module FROM modules WHERE title_module = 'bills'");
			if ($rowBill = $stmtBill->fetch()) {
				$id_module_bill = $rowBill['id_module'];
				$checkCol = $link->prepare("SELECT id_column FROM columns WHERE title_column = 'id_cash_bill' AND id_module_column = ?");
				$checkCol->execute([$id_module_bill]);
				if (!$checkCol->fetch()) {
					$ins = $link->prepare("INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (?, 'id_cash_bill', 'Caja', 'text', '', CURDATE())");
					$ins->execute([$id_module_bill]);
				}
			}

			$stmtPurchase = $link->query("SELECT id_module FROM modules WHERE title_module = 'purchases'");
			if ($rowPurchase = $stmtPurchase->fetch()) {
				$id_module_purchase = $rowPurchase['id_module'];
				$purchaseCols = [
					['may_product', 'Precio mayorista', 'money', ''],
					['wholesale_quantity', 'Cant. mayorista', 'int', '']
				];
				foreach ($purchaseCols as $c) {
					$checkPurchaseCol = $link->prepare("SELECT id_column FROM columns WHERE title_column = ? AND id_module_column = ?");
					$checkPurchaseCol->execute([$c[0], $id_module_purchase]);
					if (!$checkPurchaseCol->fetch()) {
						$ins = $link->prepare("INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, visible_column, date_created_column) VALUES (?, ?, ?, ?, ?, 1, CURDATE())");
						$ins->execute([$id_module_purchase, $c[0], $c[1], $c[2], $c[3]]);
					}
				}

				$updSale = $link->prepare("UPDATE columns SET alias_column = 'Precio venta POS' WHERE id_module_column = ? AND title_column = 'price_purchase'");
				$updSale->execute([$id_module_purchase]);
			}
		}catch(Exception $e){
			error_log("[RUNTIME_SCHEMA_SEED] ".$e->getMessage());
		}

		self::installProfessionalTriggers($link);

		try {
			$link->exec("INSERT INTO schema_migrations (version, name) VALUES (3, 'runtime_lab_admin_catalog_purchases')");
		} catch (Throwable $e) {}
		try {
			$link->exec("INSERT INTO schema_migrations (version, name) VALUES (4, 'add_id_supply_mat_cost_to_production_material_costs')");
		} catch (Throwable $e) {}
	}

	private static function ensureProductInventoryUniqueIndex(PDO $link): void {
		try {
			$link->exec("DROP TEMPORARY TABLE IF EXISTS tmp_product_inventory_rollup");
			$link->exec("
				CREATE TEMPORARY TABLE tmp_product_inventory_rollup AS
				SELECT
					MIN(id_inventory) AS keep_id,
					id_product_inventory,
					id_office_inventory,
					SUM(COALESCE(stock_inventory, 0)) AS stock_inventory,
					MAX(COALESCE(status_inventory, 1)) AS status_inventory,
					MIN(date_created_inventory) AS date_created_inventory
				FROM product_inventory
				GROUP BY id_product_inventory, id_office_inventory
				HAVING COUNT(*) > 1
			");
			$link->exec("
				UPDATE product_inventory pi
				JOIN tmp_product_inventory_rollup r ON r.keep_id = pi.id_inventory
				SET pi.stock_inventory = r.stock_inventory,
					pi.status_inventory = r.status_inventory,
					pi.date_created_inventory = COALESCE(pi.date_created_inventory, r.date_created_inventory)
			");
			$link->exec("
				DELETE pi
				FROM product_inventory pi
				JOIN tmp_product_inventory_rollup r
					ON r.id_product_inventory = pi.id_product_inventory
					AND r.id_office_inventory = pi.id_office_inventory
				WHERE pi.id_inventory <> r.keep_id
			");
			$link->exec("DROP TEMPORARY TABLE IF EXISTS tmp_product_inventory_rollup");

			$stmt = $link->query("
				SELECT COUNT(1)
				FROM information_schema.statistics
				WHERE table_schema = DATABASE()
				  AND table_name = 'product_inventory'
				  AND index_name = 'uq_product_office'
				  AND non_unique = 0
			");
			if ((int)$stmt->fetchColumn() === 0) {
				$link->exec("ALTER TABLE product_inventory ADD UNIQUE KEY uq_product_office (id_product_inventory, id_office_inventory)");
			}
		} catch (Throwable $e) {
			try { $link->exec("DROP TEMPORARY TABLE IF EXISTS tmp_product_inventory_rollup"); } catch (Throwable $dropError) {}
			error_log("[RUNTIME_SCHEMA][PRODUCT_INVENTORY_UQ] ".$e->getMessage());
		}
	}

	private static function installProfessionalTriggers(PDO $link): void {
		try {
			$link->exec("DROP TRIGGER IF EXISTS after_purchase_insert");
			$link->exec("DROP TRIGGER IF EXISTS after_purchase_update");
			$link->exec("DROP TRIGGER IF EXISTS after_sale_update");

			$link->exec("
				CREATE TRIGGER after_purchase_insert
				AFTER INSERT ON purchases
				FOR EACH ROW
				BEGIN
					DECLARE v_office_id INT DEFAULT 0;
					SET v_office_id = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase, 0);

					IF COALESCE(NEW.price_purchase, 0) > 0 THEN
						INSERT INTO product_prices
							(id_product_price, id_office_price, id_warehouse_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, id_admin_price, date_created_price)
						VALUES
							(NEW.id_product_purchase, v_office_id, NEW.id_office_purchase, COALESCE(NULLIF(NEW.price_purchase, 0), NEW.cost_purchase, 0), COALESCE(NEW.may_product, 0), COALESCE(NEW.wholesale_quantity, 0), COALESCE(NEW.cost_purchase, 0), 'compra_laboratorio', 1, COALESCE(NEW.received_by_purchase, 0), NOW());
					END IF;

					IF NEW.qty_purchase > 0 AND NEW.status_purchase IN ('completado', 'recibido', 'aprobado') THEN
						INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
						VALUES (NEW.id_product_purchase, v_office_id, NEW.qty_purchase, 1, CURDATE())
						ON DUPLICATE KEY UPDATE stock_inventory = COALESCE(stock_inventory, 0) + NEW.qty_purchase, status_inventory = 1;
						UPDATE products
						SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = NEW.id_product_purchase AND status_inventory = 1),
						    source_type_product = CASE WHEN COALESCE(source_type_product, '') = '' THEN 'externo' ELSE source_type_product END
						WHERE id_product = NEW.id_product_purchase;
						INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
						VALUES (NEW.id_product_purchase, v_office_id, NEW.qty_purchase, 'compra_recibida', COALESCE(NEW.received_by_purchase, 0), CONCAT('Compra #', NEW.id_purchase), NOW());
					END IF;
				END
			");

			$link->exec("
				CREATE TRIGGER after_purchase_update
				AFTER UPDATE ON purchases
				FOR EACH ROW
				BEGIN
					DECLARE v_old_office INT DEFAULT 0;
					DECLARE v_new_office INT DEFAULT 0;
					DECLARE v_old_qty DOUBLE DEFAULT 0;
					DECLARE v_new_qty DOUBLE DEFAULT 0;

					SET v_new_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase, 0);

					IF COALESCE(OLD.price_purchase, 0) <> COALESCE(NEW.price_purchase, 0)
					   OR COALESCE(OLD.cost_purchase, 0) <> COALESCE(NEW.cost_purchase, 0)
					   OR COALESCE(OLD.may_product, 0) <> COALESCE(NEW.may_product, 0)
					   OR COALESCE(OLD.wholesale_quantity, 0) <> COALESCE(NEW.wholesale_quantity, 0)
					   OR COALESCE(OLD.id_product_purchase, 0) <> COALESCE(NEW.id_product_purchase, 0)
					   OR COALESCE(OLD.id_office_purchase, 0) <> COALESCE(NEW.id_office_purchase, 0) THEN
						IF COALESCE(NEW.price_purchase, 0) > 0 THEN
							INSERT INTO product_prices
								(id_product_price, id_office_price, id_warehouse_price, price_sale, price_wholesale, wholesale_qty, cost_reference, source_price, status_price, id_admin_price, date_created_price)
							VALUES
								(NEW.id_product_purchase, v_new_office, NEW.id_office_purchase, COALESCE(NULLIF(NEW.price_purchase, 0), NEW.cost_purchase, 0), COALESCE(NEW.may_product, 0), COALESCE(NEW.wholesale_quantity, 0), COALESCE(NEW.cost_purchase, 0), 'compra_laboratorio', 1, COALESCE(NEW.received_by_purchase, 0), NOW());
						END IF;
					END IF;

					IF COALESCE(OLD.status_purchase, '') <> COALESCE(NEW.status_purchase, '')
					   OR COALESCE(OLD.qty_purchase, 0) <> COALESCE(NEW.qty_purchase, 0)
					   OR COALESCE(OLD.id_product_purchase, 0) <> COALESCE(NEW.id_product_purchase, 0)
					   OR COALESCE(OLD.id_office_purchase, 0) <> COALESCE(NEW.id_office_purchase, 0) THEN

						SET v_old_qty = CASE WHEN OLD.status_purchase IN ('completado', 'recibido', 'aprobado') THEN COALESCE(OLD.qty_purchase, 0) ELSE 0 END;
						SET v_new_qty = CASE WHEN NEW.status_purchase IN ('completado', 'recibido', 'aprobado') THEN COALESCE(NEW.qty_purchase, 0) ELSE 0 END;
						SET v_old_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = OLD.id_office_purchase LIMIT 1), OLD.id_office_purchase);
						SET v_new_office = COALESCE((SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = NEW.id_office_purchase LIMIT 1), NEW.id_office_purchase);

						IF v_old_qty > 0 THEN
							UPDATE product_inventory
							SET stock_inventory = COALESCE(stock_inventory, 0) - v_old_qty
							WHERE id_product_inventory = OLD.id_product_purchase AND id_office_inventory = v_old_office AND status_inventory = 1;
						END IF;

						IF v_new_qty > 0 THEN
							INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
							VALUES (NEW.id_product_purchase, v_new_office, v_new_qty, 1, CURDATE())
							ON DUPLICATE KEY UPDATE stock_inventory = COALESCE(stock_inventory, 0) + v_new_qty, status_inventory = 1;
							INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
							VALUES (NEW.id_product_purchase, v_new_office, v_new_qty, 'compra_recibida', COALESCE(NEW.received_by_purchase, 0), CONCAT('Compra #', NEW.id_purchase), NOW());
						END IF;

						UPDATE products
						SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = NEW.id_product_purchase AND status_inventory = 1)
						WHERE id_product = NEW.id_product_purchase;
						IF OLD.id_product_purchase <> NEW.id_product_purchase THEN
							UPDATE products
							SET stock_product = (SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = OLD.id_product_purchase AND status_inventory = 1)
							WHERE id_product = OLD.id_product_purchase;
						END IF;
					END IF;
				END
			");

			$link->exec("
				CREATE TRIGGER after_sale_update
				AFTER UPDATE ON sales
				FOR EACH ROW
				BEGIN
					DECLARE v_is_combo INT DEFAULT 0;

					IF NEW.status_sale = 'Completada' AND OLD.status_sale != 'Completada' THEN
						SELECT CASE
							WHEN COALESCE(is_combo_product, 0) = 1
							  OR EXISTS (SELECT 1 FROM combo_items WHERE id_combo_ci = NEW.id_product_sale)
							THEN 1 ELSE 0 END
						INTO v_is_combo
						FROM products
						WHERE id_product = NEW.id_product_sale;

						IF v_is_combo = 1 THEN
							UPDATE product_inventory pi
							INNER JOIN combo_items ci ON pi.id_product_inventory = ci.id_product_ci
							SET pi.stock_inventory = COALESCE(pi.stock_inventory, 0) - (ci.qty_ci * NEW.qty_sale)
							WHERE ci.id_combo_ci = NEW.id_product_sale AND pi.id_office_inventory = NEW.id_office_sale;

							UPDATE products p
							INNER JOIN combo_items ci ON p.id_product = ci.id_product_ci
							SET p.stock_product = (
								SELECT COALESCE(SUM(pi2.stock_inventory), 0)
								FROM product_inventory pi2
								WHERE pi2.id_product_inventory = p.id_product AND pi2.status_inventory = 1
							)
							WHERE ci.id_combo_ci = NEW.id_product_sale;
						ELSE
							UPDATE product_inventory
							SET stock_inventory = COALESCE(stock_inventory, 0) - NEW.qty_sale
							WHERE id_product_inventory = NEW.id_product_sale
							  AND id_office_inventory = NEW.id_office_sale
							  AND status_inventory = 1;

							UPDATE products
							SET stock_product = (
								SELECT COALESCE(SUM(stock_inventory), 0)
								FROM product_inventory
								WHERE id_product_inventory = NEW.id_product_sale AND status_inventory = 1
							)
							WHERE id_product = NEW.id_product_sale;
						END IF;

						INSERT INTO stock_movements (id_product_movement, id_office_movement, qty_movement, type_movement, id_admin_movement, notes_movement, date_created_movement)
						VALUES (NEW.id_product_sale, NEW.id_office_sale, -NEW.qty_sale, 'venta_pos', NEW.id_admin_sale, CONCAT('Venta POS #', NEW.id_order_sale), NOW());
					END IF;
				END
			");
		} catch (Exception $e) {
			error_log("[RUNTIME_SCHEMA_TRIGGERS] ".$e->getMessage());
		}
	}
}
