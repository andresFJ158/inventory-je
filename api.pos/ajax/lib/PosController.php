<?php

class PosController{

	/*=============================================
	Función para cargar productos
	=============================================*/

	public $limit;
	public $startAt;
	public $category;
	public $search;
	public $idOffice;
	public function loadProducts(){
		$role = $_POST["sellerRole"] ?? null;
		$id_admin = $_POST["sellerId"] ?? null;

		$method = "GET";
		$fields = array();

		$db = LocalConnection::connect();
		
		$categoryQuery = "";
		$searchQuery = "";
		$params = [':office' => $this->idOffice];
		
		if ($this->category != "all") {
			$categoryQuery = " AND p.id_category_product = :category";
			$params[':category'] = $this->category;
		}
		
		if ($this->search != "") {
			$searchQuery = " AND (p.title_product LIKE :search_title OR p.sku_product LIKE :search_sku OR p.code_product LIKE :search_code OR p.unit_product LIKE :search_unit)";
			$searchTerm = "%" . $this->search . "%";
			$params[':search_title'] = $searchTerm;
			$params[':search_sku'] = $searchTerm;
			$params[':search_code'] = $searchTerm;
			$params[':search_unit'] = $searchTerm;
		}
		
		$sql = "
			SELECT p.*, COALESCE(c.title_category, '') AS title_category, c.img_category, c.order_category, c.status_category,
				   COALESCE(pi.stock_inventory, 0) as stock_product
			FROM products p
			LEFT JOIN categories c ON p.id_category_product = c.id_category
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1
			  AND COALESCE(pi.stock_inventory, 0) > 0
			$categoryQuery
			$searchQuery
			ORDER BY p.id_product DESC
		";

		$stmtAll = $db->prepare($sql . " LIMIT " . (int)$this->startAt . ", " . (int)$this->limit);
		$stmtAll->execute($params);
		$products = $stmtAll->fetchAll(PDO::FETCH_CLASS);

		$countSql = "SELECT COUNT(*) FROM products p
			LEFT JOIN categories c ON p.id_category_product = c.id_category
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1 AND COALESCE(pi.stock_inventory, 0) > 0 $categoryQuery $searchQuery";
		$stmtCount = $db->prepare($countSql);
		$stmtCount->execute($params);
		$totalProducts = (int)$stmtCount->fetchColumn();
		$totalPageProducts = $this->limit > 0 ? (int)ceil($totalProducts / $this->limit) : 1;

		$htmlProducts = "";

		if (!empty($products)){
		
			foreach ($products as $key => $value){

				$htmlProducts .= '<div class="col-12 col-lg-6 col-xl-4 p-2 btn addProductPos" idProduct="'.$value->id_product.'">' .
					'<div class="card rounded border-0 position-relative">';


						if ($value->discount_product > 0){

							$htmlProducts .= '<div class="position-absolute small bg-red p-1 shadow-sm rounded" style="top:4px; left:4px; font-size:10px">'.$value->discount_product.'% OFF</div>';
							
						}
						
						$imgSrc = TemplateController::fallbackProductImage($value->sku_product ?? '', $value->title_product ?? '', $value->img_product ?? '');
						if (empty($imgSrc) || $imgSrc === 'NULL' || $imgSrc === 'null') {
							$imgSrc = 'views/assets/img/multimedia.png';
						}
						$htmlProducts .= '<div class="position-absolute small bg-white p-1 shadow-sm rounded" style="top:4px; right:4px; font-size:10px">'.$value->sku_product.'</div>' .
							'<img src="'.urldecode($imgSrc).'" class="card-img-top px-5 py-3 mx-auto" style="width:200px !important">' .
							'<div class="card-body">' .
								'<h6 class="font-weight-bold text-gray samll">'.urldecode($value->title_category).'</h6>' .
								'<h6 class="card-title pb-2 font-weight-bold">'.urldecode($value->title_product).'</h6>' .
								'<div class="d-flex justify-content-between">';

								if($value->stock_product < 50){

									$colorStock = "bg-maroon";
								}

								if($value->stock_product >= 50 && $value->stock_product < 100){

									$colorStock = "bg-indigo";
								}

								if($value->stock_product >= 100){

									$colorStock = "bg-teal";
								}

								$htmlProducts .= '<div class="card-text small h6 badge badge-default pb-0 '.$colorStock.'" style="font-size:10px; padding-top:6px">' .
									$value->stock_product .
								'</div>';


									$priceMeta = pos_get_product_price($db, (int)$value->id_product, (int)$this->idOffice);
									$costPurchase = $priceMeta['price'];

								if($costPurchase > 0){

									$price = $costPurchase;

									if($value->discount_product > 0){

										$discount = $price-($price*($value->discount_product/100));
									}

								}else{

									$price = 0;
								}

								if ($value->discount_product > 0){

									$htmlProducts .= '<span class="small ms-auto pe-1 h6 mt-1 text-red font-weight-bold" style="font-size:12px"><s>Bs '.number_format($price,2).'</s></span>' .
					'<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs '.number_format($discount,2).'</strong></div>';

								}else{

									$htmlProducts .= '<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs '.number_format($price,2).'</strong></div>';

								}

							$htmlProducts .= '</div></div></div></div>';
				
			}

		}

		$response = array(
			"htmlProducts" => $htmlProducts,
			"totalPagesProducts" => $totalPageProducts
		);

		echo json_encode($response);

	}

	/*=============================================
	Crear nueva orden
	=============================================*/	

	public $token;
	public $seller;

	public function newOrder(){

		// Verificar si el vendedor es independiente (posee el rol vendedor)
		$db = LocalConnection::connect();
		$stmtRole = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :seller LIMIT 1");
		$stmtRole->execute([':seller' => $this->seller]);
		$sellerRole = $stmtRole->fetchColumn();
		$isIndependent = ($sellerRole === "vendedor");

		if (!$isIndependent) {
			/*=============================================
			Validar primero que exista caja abierta
			=============================================*/
			$dbCheck = LocalConnection::connect();
			$stmtCheck = $dbCheck->prepare("SELECT id_cash FROM cashs WHERE id_office_cash = :office AND status_cash = 1 LIMIT 1");
			$stmtCheck->execute([':office' => $this->idOffice]);
			if(!$stmtCheck->fetch()){
				echo "current cash error";
				return;
			}
		}

		/*=============================================
		Crear número de transacción
		=============================================*/

		$transaction_order = TemplateController::genNumCode(12);

		/*=============================================
		No repetir Número de transacción en BD
		=============================================*/

		$validate = TemplateController::transValidate($transaction_order);

		if($validate){

			/*=============================================
			Crear nueva orden
			=============================================*/

				try {
					$stmtCreate = $db->prepare("
						INSERT INTO orders
							(transaction_order, id_admin_order, id_office_order, status_order, date_created_order)
						VALUES
							(:transaction_order, :id_admin_order, :id_office_order, 'Pendiente', :date_created_order)
					");
					$stmtCreate->execute([
						':transaction_order' => $transaction_order,
						':id_admin_order' => $this->seller,
						':id_office_order' => $this->idOffice,
						':date_created_order' => date("Y-m-d")
					]);

					$response = array(
						"type" => "new",
						"id_order" => $db->lastInsertId(),
						"transaction_order" => $transaction_order
					);

					echo json_encode($response);

				} catch (Exception $e) {

					echo json_encode([
						"status" => 500,
						"message" => "No se pudo crear la orden: ".$e->getMessage()
					]);
				}


		}else{

			/*=============================================
			Repetir proceso
			=============================================*/

			$ajax = new PosController();
			$ajax -> token = $this->token;
			$ajax -> seller = $this->seller;
			$ajax -> idOffice = $this->idOffice;
			$ajax -> newOrder();
			

		}
	}

	/*=============================================
	Actualizar orden
	=============================================*/	

	public $idOrder;
	public $idClient;
	public $subtotalOrder;
	public $discountOrder;
	public $taxOrder;
	public $totalOrder;

	public function updateOrder(){

		$db = LocalConnection::connect();
		$stmt = $db->prepare("
			UPDATE orders SET
				id_client_order  = :client,
				subtotal_order   = :subtotal,
				discount_order   = :discount,
				tax_order        = :tax,
				total_order      = :total
			WHERE id_order = :id
		");
		$ok = $stmt->execute([
			':client'   => intval($this->idClient),
			':subtotal' => round($this->subtotalOrder, 2),
			':discount' => round($this->discountOrder, 2),
			':tax'      => round($this->taxOrder, 2),
			':total'    => round($this->totalOrder, 2),
			':id'       => intval($this->idOrder)
		]);

		echo $ok ? "ok" : "logout";

	}

	/*=============================================
	Agregar nuevo cliente
	=============================================*/	

	public $name_client;
	public $surname_client;
	public $dni_client;
	public $email_client;
	public $phone_client;
	public $address_client;
	
	public function newClient(){

		$db = LocalConnection::connect();
		$stmt = $db->prepare("
			INSERT INTO clients
				(name_client, surname_client, dni_client, email_client,
				 phone_client, address_client, id_office_client, date_created_client)
			VALUES
				(:name, :surname, :dni, :email, :phone, :address, :office, CURDATE())
		");
		$ok = $stmt->execute([
			':name'    => $this->name_client,
			':surname' => $this->surname_client,
			':dni'     => $this->dni_client,
			':email'   => $this->email_client,
			':phone'   => $this->phone_client,
			':address' => $this->address_client,
			':office'  => intval($this->idOffice)
		]);

		echo $ok ? $db->lastInsertId() : "logout";

	}

	/*=============================================
	Agregar producto a la lista de órdenes
	=============================================*/

	public $idProduct;

	public function addProductPos(){

		$db = LocalConnection::connect();

		$stmtP = $db->prepare("SELECT * FROM products WHERE id_product = :id AND status_product = 1 LIMIT 1");
		$stmtP->execute([':id' => $this->idProduct]);
		$product = $stmtP->fetch(PDO::FETCH_OBJ);

		if (!$product) {
			echo "error|Producto no encontrado";
			return;
		}

		$stmtComboCount = $db->prepare("SELECT COUNT(*) FROM combo_items WHERE id_combo_ci = :id");
		$stmtComboCount->execute([':id' => $this->idProduct]);
		$isCombo = intval($product->is_combo_product ?? 0) === 1 || intval($stmtComboCount->fetchColumn()) > 0;
		$comboPrice = null;

		if ($isCombo) {
			// Para combos: verificar stock de cada componente
			$stmtCI = $db->prepare("SELECT id_product_ci, qty_ci, price_ci FROM combo_items WHERE id_combo_ci = :id");
			$stmtCI->execute([':id' => $this->idProduct]);
			$comboItems = $stmtCI->fetchAll(PDO::FETCH_OBJ);

			if (empty($comboItems)) {
				echo "error|El combo no tiene componentes configurados";
				return;
			}

			$minCombos = PHP_INT_MAX;
			foreach ($comboItems as $ci) {
				// Combos siempre usan product_inventory (misma fuente que el trigger after_sale_update)
				$stmtCS = $db->prepare("
					SELECT COALESCE(stock_inventory, 0) FROM product_inventory
					WHERE id_product_inventory = :product AND id_office_inventory = :office LIMIT 1
				");
				$stmtCS->execute([':product' => $ci->id_product_ci, ':office' => $this->idOffice]);
				$compStock = (int)($stmtCS->fetchColumn() ?: 0);
				$possible  = $ci->qty_ci > 0 ? floor($compStock / $ci->qty_ci) : 0;
				if ($possible < $minCombos) $minCombos = $possible;
			}

			if ($minCombos <= 0) {
				echo "error stock";
				return;
			}

			// Precio del combo: suma de price_ci × qty_ci de sus componentes
			$comboPrice = array_sum(array_map(fn($ci) => floatval($ci->price_ci) * floatval($ci->qty_ci), $comboItems));

		} else {
			// Producto normal o fabricado: verificar stock confirmado de la sucursal.
			$stmtStock = $db->prepare("
				SELECT COALESCE(stock_inventory, 0) FROM product_inventory
				WHERE id_product_inventory = :product AND id_office_inventory = :office AND status_inventory = 1 LIMIT 1
			");
			$stmtStock->execute([':product' => $this->idProduct, ':office' => $this->idOffice]);
			$stock = (int)($stmtStock->fetchColumn() ?: 0);

			if ($stock <= 0) {
				echo "error stock";
				return;
			}
		}

		// Verificar si ya existe en la orden
		$stmtExist = $db->prepare("SELECT id_sale FROM sales WHERE id_order_sale = :order AND id_product_sale = :product LIMIT 1");
		$stmtExist->execute([':order' => $this->idOrder, ':product' => $this->idProduct]);
		if ($stmtExist->fetchColumn()) {
			echo "product exist";
			return;
		}

		// Calcular precio
		$isWholesale = isset($_POST["isWholesale"]) && $_POST["isWholesale"] == 1;
		if ($isCombo && $comboPrice !== null) {
			// Precio del combo = suma de price_ci × qty_ci de sus componentes
			$selling_price = $comboPrice;
		} else {
			$priceMeta = pos_get_product_price($db, (int)$this->idProduct, (int)$this->idOffice);
			$selling_price = ($isWholesale && !empty($priceMeta['wholesalePrice']) && ($product->discount_product ?? 0) <= 0)
				? (float)$priceMeta['wholesalePrice']
				: (float)$priceMeta['price'];
		}

		$taxParts = explode("_", (isset($product->tax_product) && !empty($product->tax_product)) ? $product->tax_product : "0_0");
		$taxType = $taxParts[0];
		$taxVal  = $taxParts[1] ?? "0";

		// Insertar venta
		$stmtIns = $db->prepare("
			INSERT INTO sales (id_order_sale, id_product_sale, tax_type_sale, tax_sale, discount_sale,
				qty_sale, subtotal_sale, status_sale, id_admin_sale, id_client_sale, id_office_sale, date_created_sale)
			VALUES (:order, :product, :tax_type, :tax, :discount, 1, :subtotal, 'Pendiente',
				:admin, :client, :office, :date)
		");
		$stmtIns->execute([
			':order'    => $this->idOrder,
			':product'  => $this->idProduct,
			':tax_type' => $taxType,
			':tax'      => $taxVal,
			':discount' => $product->discount_product ?? 0,
			':subtotal' => $selling_price,
			':admin'    => $this->seller,
			':client'   => $this->idClient ?: null,
			':office'   => $this->idOffice,
			':date'     => date("Y-m-d"),
		]);

		$newSaleId = $db->lastInsertId();

		// Verificar permisos para override de precio
		$stmtAdmin = $db->prepare("SELECT permissions_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtAdmin->execute([':id' => $this->seller]);
		$permsRaw = $stmtAdmin->fetchColumn();
		$perms = $permsRaw ? json_decode(urldecode($permsRaw), true) : [];
		$canOverride = isset($perms["todo"]) || isset($perms["pos_override_price"]);

		echo "ok|" . $newSaleId . "|" . ($canOverride ? "1" : "0");
	}


	/*=============================================
	Actualizar Cantidad
	=============================================*/

	public $idSaleUpdate;
	public $qtySale;
	public $subtotalSale;

	public function updateSale(){
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare("UPDATE sales SET qty_sale = :qty, subtotal_sale = :subtotal WHERE id_sale = :id");
			$stmt->execute([
				':qty'      => $this->qtySale,
				':subtotal' => round($this->subtotalSale, 2),
				':id'       => $this->idSaleUpdate,
			]);
			echo "ok";
		} catch (Throwable $e) {
			echo "logout";
		}
	}

	/*=============================================
	Alternar Precio Mayorista en el Carrito
	=============================================*/
	public $isWholesale;

	public function toggleCartWholesale(){
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare("SELECT id_sale, id_product_sale, qty_sale, discount_sale, id_office_sale FROM sales WHERE id_order_sale = :order");
			$stmt->execute([':order' => $this->idOrder]);
			$sales = $stmt->fetchAll(PDO::FETCH_OBJ);

			foreach ($sales as $sale) {
				$priceMeta = pos_get_product_price($db, (int)$sale->id_product_sale, (int)$sale->id_office_sale);
				$selling_price = ($this->isWholesale == 1 && !empty($priceMeta['wholesalePrice']) && $sale->discount_sale <= 0)
					? $priceMeta['wholesalePrice'] : $priceMeta['price'];
				$stmtU = $db->prepare("UPDATE sales SET subtotal_sale = :subtotal WHERE id_sale = :id");
				$stmtU->execute([':subtotal' => round($selling_price * $sale->qty_sale, 2), ':id' => $sale->id_sale]);
			}
			echo "ok";
		} catch (Throwable $e) {
			echo "error";
		}
	}

	/*=============================================
	Remover Venta
	=============================================*/

	public $idSaleDelete;

	public function deleteSale(){
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare("SELECT status_sale FROM sales WHERE id_sale = :id LIMIT 1");
			$stmt->execute([':id' => $this->idSaleDelete]);
			$status = $stmt->fetchColumn();

			if ($status === 'Completada') {
				echo "error";
				return;
			}

			$stmt = $db->prepare("DELETE FROM sales WHERE id_sale = :id");
			$stmt->execute([':id' => $this->idSaleDelete]);
			echo "ok";
		} catch (Throwable $e) {
			echo "logout";
		}
	}

	/*=============================================
	Remover todas las Ventas
	=============================================*/

	public $idOrderSale;

	public function deleteAllSale(){
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare("DELETE FROM sales WHERE id_order_sale = :order AND status_sale = 'Pendiente'");
			$stmt->execute([':order' => $this->idOrderSale]);
			echo "ok";
		} catch (Throwable $e) {
			echo "error";
		}
	}

	/*=============================================
	Remover Órden
	=============================================*/

	public $idOrderDelete;

	public function deleteOrder(){
		try {
			$db = LocalConnection::connect();

			// Verificar que la orden no esté finalizada
			$stmt = $db->prepare("SELECT status_order FROM orders WHERE id_order = :id LIMIT 1");
			$stmt->execute([':id' => $this->idOrderDelete]);
			$order = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$order) {
				echo "error|Orden no encontrada";
				return;
			}

			if ($order['status_order'] === 'Completada') {
				echo "error|No se puede eliminar una orden completada";
				return;
			}

			// Iniciar transacción
			$db->beginTransaction();

			// Eliminar todas las ventas asociadas
			$stmtSales = $db->prepare("DELETE FROM sales WHERE id_order_sale = :id");
			$stmtSales->execute([':id' => $this->idOrderDelete]);

			// Eliminar la orden
			$stmtOrder = $db->prepare("DELETE FROM orders WHERE id_order = :id");
			$stmtOrder->execute([':id' => $this->idOrderDelete]);

			// Confirmar transacción
			$db->commit();
			echo "ok";

		} catch (Exception $e) {
			if (isset($db) && $db->inTransaction()) {
				$db->rollBack();
			}
			echo "error|" . $e->getMessage();
		}
	}


	/*=============================================
	Sobrescribir Precio Manualmente
	=============================================*/

	public $idSaleOverride;
	public $idOrderOverride;
	public $idProductOverride;
	public $originalPriceOverride;
	public $newPriceOverride;
	public $reasonOverride;
	public $qtyOverride;

	public function overridePrice(){

		$db          = LocalConnection::connect();
		$newSubtotal = round($this->newPriceOverride, 2) * $this->qtyOverride;

		// Actualizar venta con nuevo precio
		$stmtSale = $db->prepare("
			UPDATE sales SET
				subtotal_sale         = :sub,
				applied_price_type    = 'manual',
				original_price_sale   = :orig
			WHERE id_sale = :id
		");
		$ok = $stmtSale->execute([
			':sub'  => round($newSubtotal, 2),
			':orig' => round($this->originalPriceOverride, 2),
			':id'   => intval($this->idSaleOverride)
		]);

		if ($ok) {
			// Registrar en auditoría
			$stmtAudit = $db->prepare("
				INSERT INTO price_overrides
					(id_sale_override, id_order_override, id_product_override,
					 id_admin_override, original_price, override_price, reason_override)
				VALUES
					(:sale, :order, :product, :admin, :orig, :new, :reason)
			");
			$stmtAudit->execute([
				':sale'    => intval($this->idSaleOverride),
				':order'   => intval($this->idOrderOverride),
				':product' => intval($this->idProductOverride),
				':admin'   => intval($this->seller),
				':orig'    => round($this->originalPriceOverride, 2),
				':new'     => round($this->newPriceOverride, 2),
				':reason'  => $this->reasonOverride
			]);
			echo "ok";
		} else {
			echo "logout";
		}

	}

}
