<?php
/** Migraciones de esquema — ejecutar vía bin/migrate.php, no en cada request HTTP. */
class SchemaMigrator {
	public static function isApplied(PDO $link): bool {
		try {
			$link->query("SELECT 1 FROM schema_migrations LIMIT 1");
			$row = $link->query("SELECT COUNT(*) FROM schema_migrations WHERE version >= 1")->fetchColumn();
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
			// Comprobante de respaldo en abonos de crédito y liquidación de consignación
			"ALTER TABLE credit_payments ADD COLUMN file_payment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE consignments ADD COLUMN file_consignment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE consignments ADD COLUMN reference_consignment VARCHAR(255) DEFAULT NULL",
			"ALTER TABLE raw_materials ADD COLUMN is_insumo TINYINT(1) DEFAULT 0 AFTER status_raw_material",
			"ALTER TABLE bills ADD COLUMN id_cash_bill INT(11) DEFAULT 0",
			"ALTER TABLE orders ADD COLUMN invoice_order INT(11) DEFAULT 0"
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
			"CREATE INDEX idx_prodinv_prod_off ON product_inventory(id_product_inventory, id_office_inventory)"
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
		}catch(Exception $e){
			error_log("[RUNTIME_SCHEMA_SEED] ".$e->getMessage());
		}

		try {
			$link->exec("INSERT INTO schema_migrations (version, name) VALUES (1, 'runtime_bootstrap')");
		} catch (Throwable $e) {}
	}
}
