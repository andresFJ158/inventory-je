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
			$searchQuery = " AND (p.title_product LIKE :search OR p.sku_product LIKE :search OR p.code_product LIKE :search OR p.unit_product LIKE :search)";
			$params[':search'] = "%" . $this->search . "%";
		}
		
		$hasSubWarehouse = false;
		if ($id_admin) {
			$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
			$stmtHasSub->execute([':office' => $this->idOffice]);
			$hasSubWarehouse = (bool)$stmtHasSub->fetch(PDO::FETCH_ASSOC);
		}

		if ($hasSubWarehouse) {
			$sql = "
				SELECT p.*, c.title_category, c.img_category, c.order_category, c.status_category,
					   sub.stock as stock_product
				FROM products p
				INNER JOIN categories c ON p.id_category_product = c.id_category
				INNER JOIN (
					SELECT wa.id_product_assignment,
						   (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
							COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
					FROM warehouse_assignments wa
					JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
					WHERE sw.id_office_sub_warehouse = :office
					GROUP BY wa.id_product_assignment
					HAVING stock > 0
				) sub ON p.id_product = sub.id_product_assignment
				WHERE p.status_product = 1
				$categoryQuery
				$searchQuery
				ORDER BY p.id_product DESC
			";
		} else {
			$sql = "
				SELECT p.*, c.title_category, c.img_category, c.order_category, c.status_category,
					   COALESCE(pi.stock_inventory, 0) as stock_product
				FROM products p
				INNER JOIN categories c ON p.id_category_product = c.id_category
				INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
				WHERE p.status_product = 1
				$categoryQuery
				$searchQuery
				ORDER BY p.id_product DESC
			";
		}
		$warehouseIds = [];
		try {
			$stmtWH = $db->prepare("SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office");
			$stmtWH->execute([':office' => $this->idOffice]);
			$warehouseIds = $stmtWH->fetchAll(PDO::FETCH_COLUMN) ?: [];
		} catch (Exception $e) {}

		$stmtAll = $db->prepare($sql . " LIMIT " . (int)$this->startAt . ", " . (int)$this->limit);
		$stmtAll->execute($params);
		$products = $stmtAll->fetchAll(PDO::FETCH_CLASS);

		$countSql = "SELECT COUNT(*) FROM products p
			LEFT JOIN categories c ON p.id_category_product = c.id_category
			LEFT JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office
			WHERE p.status_product = 1 $categoryQuery $searchQuery";
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


								$url = "purchases?linkTo=id_product_purchase&equalTo=".$value->id_product."&select=cost_purchase,id_office_purchase&orderBy=date_created_purchase&orderMode=DESC";

								$price = CurlController::request($url,$method,$fields);

								$costPurchase = 0;
								if($price->status == 200 && !empty($price->results)){
									foreach ($price->results as $pRow) {
										if (in_array((int)$pRow->id_office_purchase, $warehouseIds)) {
											$costPurchase = $pRow->cost_purchase;
											break;
										}
									}
									if ($costPurchase == 0) {
										$costPurchase = $price->results[0]->cost_purchase;
									}
								}

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

		$url = "orders?id=".$this->idOrder."&nameId=id_order&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = array(
			"id_client_order" => $this->idClient,
			"subtotal_order" => round($this->subtotalOrder),
			"discount_order" => round($this->discountOrder),
			"tax_order" => round($this->taxOrder),
			"total_order" => round($this->totalOrder)
		);

		$fields = http_build_query($fields);

		$updateOrder = CurlController::request($url,$method,$fields);

		if($updateOrder->status == 200){

			echo "ok";
		
		}else{

			echo "logout";
		}	

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

		$url = "clients?token=".$this->token."&table=admins&suffix=admin";
		$method = "POST";
		$fields = array(
			"name_client" => $this->name_client,
			"surname_client" => $this->surname_client,
			"dni_client" => $this->dni_client,
			"email_client" => $this->email_client,
			"phone_client" => $this->phone_client,
			"address_client" => $this->address_client,
			"id_office_client" => $this->idOffice,
			"date_created_client" => date("Y-m-d")
		);

		$addClient = CurlController::request($url,$method,$fields);

		if($addClient->status == 200){

			echo $addClient->results->lastId;
		
		}else{

			echo "logout";
		}


	}

	/*=============================================
	Agregar producto a la lista de órdenes
	=============================================*/

	public $idProduct;

	public function addProductPos(){

		// Fetch warehouse IDs for this office
		$db = LocalConnection::connect();
		$stmtWH = $db->prepare("SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office");
		$stmtWH->execute([':office' => $this->idOffice]);
		$warehouseIds = $stmtWH->fetchAll(PDO::FETCH_COLUMN) ?: [];

		$url = "relations?rel=purchases,products&type=purchase,product&linkTo=id_product&equalTo=".$this->idProduct."&orderBy=date_created_purchase&orderMode=DESC";
		$method = "GET";
		$fields = array();

		$getProduct = CurlController::request($url,$method,$fields);

		$matchedProduct = null;
		if($getProduct->status == 200 && !empty($getProduct->results)){
			foreach ($getProduct->results as $pRow) {
				if (in_array((int)$pRow->id_office_purchase, $warehouseIds)) {
					$matchedProduct = $pRow;
					break;
				}
			}
			if (!$matchedProduct) {
				$matchedProduct = $getProduct->results[0];
			}
		} else {
			$urlFallback = "products?linkTo=id_product&equalTo=".$this->idProduct;
			$getProductFallback = CurlController::request($urlFallback, "GET", array());
			if($getProductFallback->status == 200 && !empty($getProductFallback->results)){
				$matchedProduct = $getProductFallback->results[0];
				$matchedProduct->cost_purchase = 0;
				$matchedProduct->may_product = 0;
			}
		}

		if($matchedProduct !== null){

			$product = $matchedProduct;

			// Obtener si el vendedor tiene sub-almacén asignado y calcular el stock correspondiente (por oficina)
			$db = LocalConnection::connect();
			$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
			$stmtHasSub->execute([':office' => $this->idOffice]);
			$hasSubWarehouse = (bool)$stmtHasSub->fetch(PDO::FETCH_ASSOC);

			$stock = 0;
			if ($hasSubWarehouse) {
				// Consultar stock en sub-almacén de la oficina
				$stmtStock = $db->prepare("
					SELECT (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
							COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
					FROM warehouse_assignments wa
					JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
					WHERE sw.id_office_sub_warehouse = :office AND wa.id_product_assignment = :product
				");
				$stmtStock->execute([
					':office' => $this->idOffice,
					':product' => $this->idProduct
				]);
				$stock = (int)($stmtStock->fetchColumn() ?: 0);
			} else {
				// Consultar stock en product_inventory para la oficina actual
				$stmtStock = $db->prepare("
					SELECT COALESCE(stock_inventory, 0) as stock
					FROM product_inventory
					WHERE id_product_inventory = :product AND id_office_inventory = :office LIMIT 1
				");
				$stmtStock->execute([
					':product' => $this->idProduct,
					':office' => $this->idOffice
				]);
				$stock = (int)($stmtStock->fetchColumn() ?: 0);
			}

			if($stock <= 0){

				echo "error stock";

				return;
			
			}else{

				/*=============================================
				Validar que el producto no exista en esa orden
				=============================================*/

				$url = "sales?linkTo=id_order_sale,id_product_sale&equalTo=".$this->idOrder.",".$this->idProduct."&select=id_sale";
				$method = "GET";
				$fields = array();

				$getSale = CurlController::request($url,$method,$fields);

				if($getSale->status == 200){

					echo "product exist";
					return;
				}

				/*=============================================
				Subir a ventas
				=============================================*/

				$selling_price = (isset($_POST["isWholesale"]) && $_POST["isWholesale"] == 1 && !empty($product->may_product) && $product->discount_product <= 0) ? $product->may_product : $product->cost_purchase;

				if($product->discount_product > 0){

					$price_purchase = round($selling_price-($selling_price*($product->discount_product/100)));
				}else{

					$price_purchase = round($selling_price);

				}

				$url = "sales?token=".$this->token."&table=admins&suffix=admin";
				$method = "POST";
				$fields = array(
					"id_order_sale" => $this->idOrder,
					"id_product_sale" => $this->idProduct,
					"tax_type_sale" => explode("_", (isset($product->tax_product) && !empty($product->tax_product)) ? $product->tax_product : "0_0")[0],
					"tax_sale" => explode("_", (isset($product->tax_product) && !empty($product->tax_product)) ? $product->tax_product : "0_0")[1] ?? "0",
					"discount_sale" => $product->discount_product,
					"qty_sale" => 1,
					"subtotal_sale" => $selling_price,
					"status_sale" => "Pendiente",
					"id_admin_sale" => $this->seller,
					"id_client_sale" => $this->idClient,
					"id_office_sale" => $this->idOffice,
					"date_created_sale" => date("Y-m-d")
				);

				$createSale = CurlController::request($url,$method,$fields);
				
				if($createSale->status == 200){

					/*=============================================
					Devolver HTML
					=============================================*/

					$imgSrcCart = TemplateController::fallbackProductImage($product->sku_product ?? '', $product->title_product ?? '', $product->img_product ?? '');
					if (empty($imgSrcCart) || $imgSrcCart === 'NULL' || $imgSrcCart === 'null') {
						$imgSrcCart = 'views/assets/img/multimedia.png';
					}

					$html = '<tr>
				
								<td>
									<div>
										<img src="'.urldecode($imgSrcCart).'" class="me-auto rounded mt-2 float-start"style="width:60px !important; height:60px !important">

										<div class="ms-2 float-start">
											
											<span class="badge badge-default backColor rounded" style="font-size:10px">'.urldecode($product->sku_product).'</span>';

											if($product->discount_product > 0){

												$html .= '<span class="badge badge-default bg-red rounded ms-1" style="font-size:10px">'.$product->discount_product.'%</span>

												<h6 class="font-weight-bold  mb-0 text-muted"><strong>'.urldecode($product->title_product).'</strong></h6>
												<small>Bs '.number_format($price_purchase,2).' <span class="ms-1 text-red" style="font-size:12px"><s>Bs '.number_format($selling_price,2).' </s></span></small>';

											}else{

												$html .= '<h6 class="font-weight-bold  mb-0 text-muted"><strong>'.urldecode($product->title_product).'</strong></h6>
												<small>Bs '.number_format($selling_price,2).'</small>';
											}

										$html .= '</div>
									</div>
								</td>

								<td class="text-center">

									<div class="d-flex justify-content-center">
										
										<div class="input-group mb-3 mt-2" style="width:160px">
											
											<span class="input-group-text rounded-start bg-light btnQty" type="btnMin" style="cursor:pointer" key="'.$product->id_product.'" stock="'.$stock.'">
												<i class="bi bi-dash-lg"></i>
											</span>

											<input type="number" class="form-control text-center showQuantity showQuantity_'.$product->id_product.'" value="1" key="'.$product->id_product.'" style="font-size:12px" stock="'.$stock.'">

											<span class="input-group-text rounded-end bg-light btnQty" type="btnMax" style="cursor:pointer" key="'.$product->id_product.'" stock="'.$stock.'">
												<i class="bi bi-plus-lg"></i>
											</span>

										</div>
									</div>
									
								</td>

								<td>
									<h6 class="text-center my-3 pricePurchase pricePurchase_'.$product->id_product.'" 
									pricePurchase="'.$selling_price.'" 
									originalPricePurchase="'.$selling_price.'"
									basePrice="'.$product->cost_purchase.'"
									wholesalePrice="'.(empty($product->may_product) ? 0 : $product->may_product).'"
									wholesaleQty="'.(empty($product->wholesale_quantity) ? 0 : $product->wholesale_quantity).'"
									appliedPriceType="base"
									>Bs '.number_format($selling_price,2).'</h6>
								</td>

								<td class="text-center">
									<div class="d-flex justify-content-center">';

										$urlAdmin = "admins?linkTo=id_admin&equalTo=".$this->seller."&select=permissions_admin";
										$adminReq = CurlController::request($urlAdmin, "GET", array());
										$canOverride = false;
										if (isset($adminReq->status) && $adminReq->status == 200 && !empty($adminReq->results)) {
											$perms = json_decode(urldecode($adminReq->results[0]->permissions_admin), true);
											$canOverride = isset($perms["todo"]) || isset($perms["pos_override_price"]) ? true : false;
										}

										if($canOverride){
											$html .= '<button type="button" class="btn btn-sm rounded mt-2 py-2 px-3 btn-info editPriceSale text-white" idSale="'.$createSale->results->lastId.'" idProduct="'.$product->id_product.'" currentPrice="'.$price_purchase.'">
												<i class="bi bi-pencil"></i>
											</button>';
										}

										$html .= '<button type="button" class="btn btn-sm rounded ms-1 mt-2 py-2 px-3 bg-red deleteSale deleteSale_'.$product->id_product.'" idSale="'.$createSale->results->lastId.'" taxSale="'.(explode("_", (isset($product->tax_product) && !empty($product->tax_product)) ? $product->tax_product : "0_0")[1] ?? "0").'" discountSale="'.$product->discount_product.'">
											<i class="bi bi-trash"></i>
										</button>
									</div>
								</td>
							</tr>';

						echo $html;
				}else{
					echo json_encode($createSale);
				}

			}

		}

	}

	/*=============================================
	Actualizar Cantidad
	=============================================*/

	public $idSaleUpdate;
	public $qtySale;
	public $subtotalSale;

	public function updateSale(){

		$url = "sales?id=".$this->idSaleUpdate."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = array(
			"qty_sale" => $this->qtySale,
			"subtotal_sale" => round($this->subtotalSale,2)
		);

		$fields = http_build_query($fields);

		$updateSale = CurlController::request($url,$method,$fields);

		if($updateSale->status == 200){

			echo "ok";
		
		}else{

			echo "logout";
		}

	}

	/*=============================================
	Alternar Precio Mayorista en el Carrito
	=============================================*/
	public $isWholesale;

	public function toggleCartWholesale(){

		$url = "sales?linkTo=id_order_sale&equalTo=".$this->idOrder."&select=id_sale,id_product_sale,qty_sale,discount_sale,id_office_sale";
		$method = "GET";
		$fields = array();

		$getSales = CurlController::request($url,$method,$fields);

		if(isset($getSales->status) && $getSales->status == 200){

			foreach ($getSales->results as $key => $sale) {
				
				$urlProduct = "purchases?linkTo=id_product_purchase,id_office_purchase&equalTo=".$sale->id_product_sale.",".$sale->id_office_sale."&select=cost_purchase,may_product&orderBy=date_created_purchase&orderMode=DESC";
				$getProduct = CurlController::request($urlProduct,$method,$fields);

				if($getProduct->status != 200){
					$urlFallback = "purchases?linkTo=id_product_purchase&equalTo=".$sale->id_product_sale."&select=cost_purchase,may_product&orderBy=date_created_purchase&orderMode=DESC";
					$getProduct = CurlController::request($urlFallback,$method,$fields);
				}

				if(isset($getProduct->status) && $getProduct->status == 200){
					
					$product = $getProduct->results[0];
					$selling_price = ($this->isWholesale == 1 && !empty($product->may_product) && $sale->discount_sale <= 0) ? $product->may_product : $product->cost_purchase;

					$urlUpdate = "sales?id=".$sale->id_sale."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
					$methodUpdate = "PUT";
					$fieldsUpdate = array(
						"subtotal_sale" => round($selling_price * $sale->qty_sale, 2)
					);
					$fieldsUpdate = http_build_query($fieldsUpdate);

					CurlController::request($urlUpdate, $methodUpdate, $fieldsUpdate);
				}
			}

			echo "ok";

		}else{
			echo "error";
		}
	}

	/*=============================================
	Remover Venta
	=============================================*/

	public $idSaleDelete;

	public function deleteSale(){

		/*=============================================
		Validar que la venta no esté finalizada
		=============================================*/

		$url = "sales?linkTo=id_sale,status_sale&equalTo=".$this->idSaleDelete.",Completada";
		$method = "GET";
		$fields = array();

		$getSale = CurlController::request($url,$method,$fields);

		if($getSale->status == 200){

			echo "error";

			return;

		}else{

			/*=============================================
			Eliminar venta
			=============================================*/
		
			$url = "sales?id=".$this->idSaleDelete."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
			$method = "DELETE";
			$fields = array();

			$deleteSale = CurlController::request($url,$method,$fields);

			if($deleteSale->status == 200){

				echo "ok";	
			
			}else{

				echo "logout";
			}

		}

	}

	/*=============================================
	Remover todas las Ventas
	=============================================*/

	public $idOrderSale;

	public function deleteAllSale(){

		/*=============================================
		Validar que la venta no esté finalizada
		=============================================*/

		$url = "sales?linkTo=id_order_sale,status_sale&equalTo=".$this->idOrderSale.",Pendiente";
		$method = "GET";
		$fields = array();

		$getSale = CurlController::request($url,$method,$fields);

		if($getSale->status == 200){

			$countDeleteSale = 0;

			foreach ($getSale->results as $key => $value) {


				/*=============================================
				Eliminar venta
				=============================================*/

				$url = "sales?id=".$value->id_sale."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
				$method = "DELETE";
				$fields = array();

				$deleteSale = CurlController::request($url,$method,$fields);

				if($deleteSale->status == 200){

					$countDeleteSale++;

					if($countDeleteSale == count($getSale->results)){

						echo "ok";
					}
				}
			}

		}else{

			echo "error";
		}
	}

	/*=============================================
	Remover Órden
	=============================================*/

	public $idOrderDelete;

	public function deleteOrder(){

		/*=============================================
		Validar que la órden no esté finalizada
		=============================================*/

		$url = "orders?linkTo=id_order,status_order&equalTo=".$this->idOrderDelete.",Completada";
		$method = "GET";
		$fields = array();

		$getOrder = CurlController::request($url,$method,$fields);

		if($getOrder->status == 200){

			echo "error";
		
		}else{

			/*=============================================
			Eliminar orden
			=============================================*/

			$url = "orders?id=".$this->idOrderDelete."&nameId=id_order&token=".$this->token."&table=admins&suffix=admin";
			$method = "DELETE";
			$fields = array();

			$deleteOrder = CurlController::request($url,$method,$fields);

			if($deleteOrder->status == 200){

				$url = "sales?linkTo=id_order_sale&equalTo=".$this->idOrderDelete;
				$method = "GET";
				$fields = array();

				$getSales = CurlController::request($url,$method,$fields);

				if($getSales->status == 200){

					$countDeleteSales = 0;

					foreach ($getSales->results as $key => $value) {

						/*=============================================
						Eliminar venta
						=============================================*/

						$url = "sales?id=".$value->id_sale."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
						$method = "DELETE";
						$fields = array();

						$deleteSale = CurlController::request($url,$method,$fields);

						if($deleteSale->status == 200){

							$countDeleteSales++;

							if($countDeleteSales == count($getSales->results)){

								echo "ok";
							}
						}
					}

				}

			}

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

		/*=============================================
		Actualizar Venta con nuevo precio
		=============================================*/
		$newSubtotal = round($this->newPriceOverride, 2) * $this->qtyOverride;

		$url = "sales?id=".$this->idSaleOverride."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = array(
			"subtotal_sale" => round($newSubtotal, 2),
			"applied_price_type" => "manual",
			"original_price_sale" => round($this->originalPriceOverride, 2)
		);

		$fields = http_build_query($fields);

		$updateSale = CurlController::request($url,$method,$fields);

		if($updateSale->status == 200){

			/*=============================================
			Registrar en Auditoría (price_overrides)
			=============================================*/
			
			$urlAudit = "price_overrides?token=".$this->token."&table=admins&suffix=admin";
			$methodAudit = "POST";
			$fieldsAudit = array(
				"id_sale_override" => $this->idSaleOverride,
				"id_order_override" => $this->idOrderOverride,
				"id_product_override" => $this->idProductOverride,
				"id_admin_override" => $this->seller,
				"original_price" => round($this->originalPriceOverride, 2),
				"override_price" => round($this->newPriceOverride, 2),
				"reason_override" => $this->reasonOverride
			);

			CurlController::request($urlAudit, $methodAudit, $fieldsAudit);

			echo "ok";
		
		}else{

			echo "logout";
		}

	}

}
