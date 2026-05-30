<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

require_once "../controllers/curl.controller.php";
require_once "../controllers/template.controller.php";

date_default_timezone_set("America/La_Paz");

class LocalConnection {
	static public function connect(){
		$host = getenv("DB_HOST") ?: "127.0.0.1";
		$db = getenv("DB_NAME") ?: "u228744577_pos";
		$user = getenv("DB_USER") ?: "root";
		$pass = getenv("DB_PASS") ?: "";
		$port = getenv("DB_PORT") ?: "3306";
		$link = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
		$link->exec("set names utf8");
		$link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $link;
	}
}

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

		$stmtAll = $db->prepare($sql);
		$stmtAll->execute($params);
		$allProducts = $stmtAll->fetchAll(PDO::FETCH_CLASS);
		
		$totalPageProducts = ceil(count($allProducts) / $this->limit);
		
		// Apply limit and offset
		$sqlLimit = $sql . " LIMIT " . (int)$this->startAt . ", " . (int)$this->limit;
		$stmtLimit = $db->prepare($sqlLimit);
		$stmtLimit->execute($params);
		$products = $stmtLimit->fetchAll(PDO::FETCH_CLASS);

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
			"totalPagesProducts" => $totalPageProducts,
			"debug_isWholesale" => isset($_POST["isWholesale"]) ? $_POST["isWholesale"] : "NOT_SET"
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
			Validar primero que exista caja del día abierta
			=============================================*/

			$url = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".date("Y-m-d").",1,".$this->idOffice."&select=status_cash";
			$method = "GET";
			$fields = array();

			$cash = CurlController::request($url,$method,$fields);
			
			if($cash->status == 404){

				echo "current cash error";
				return;
			
			}else{

				/*=============================================
				Validar que la caja del día anterior haya sido cerrada
				=============================================*/

				$yesterday = date("Y-m-d", strtotime(date("Y-m-d")."- 1 days"));
				
				$url = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".$yesterday.",1,".$this->idOffice."&select=status_cash"; 
				$method = "GET";
				$fields = array();

				$cash = CurlController::request($url,$method,$fields);

				if($cash->status == 200){

					echo "yesterday cash error";
					return;

				}

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

			$url = "orders?&token=".$this->token."&table=admins&suffix=admin";
			$method = "POST";
			$fields = array(
				"transaction_order" => $transaction_order,
				"id_admin_order" => $this->seller,
				"id_office_order" => $this->idOffice,
				"status_order" => "Pendiente",
				"date_created_order" => date("Y-m-d")
			);

			$createOrder = CurlController::request($url,$method,$fields);

			if($createOrder->status == 200){

				$response = array(
					"type" => "new",
					"id_order" => $createOrder->results->lastId,
					"transaction_order" => $transaction_order
				);

				echo json_encode($response);

			}else{

				echo "logout";
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

					echo "logout";
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

/*=============================================
Función para cargar productos
=============================================*/

if(isset($_POST["limit"])){

	$ajax = new PosController();
	$ajax -> limit = $_POST["limit"];
	$ajax -> startAt = $_POST["startAt"];
	$ajax -> category = $_POST["category"];
	$ajax -> search = $_POST["search"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> loadProducts();

}

/*=============================================
Crear nueva orden
=============================================*/

if(isset($_POST["order"])){

	$ajax = new PosController();
	$ajax -> token = $_POST["token"];
	$ajax -> seller = $_POST["seller"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> newOrder();
}

/*=============================================
Actualizar orden
=============================================*/	

if(isset($_POST["idOrderUpdate"])){

	$ajax = new PosController();
	$ajax -> token = $_POST["token"];
	$ajax -> idOrder = $_POST["idOrderUpdate"];
	$ajax -> idClient = $_POST["idClient"];
	$ajax -> subtotalOrder = $_POST["subtotalOrder"];
	$ajax -> discountOrder = $_POST["discountOrder"];
	$ajax -> taxOrder = $_POST["taxOrder"];
	$ajax -> totalOrder = $_POST["totalOrder"];
	$ajax -> updateOrder();
}

/*=============================================
Agregar nuevo cliente
=============================================*/	

if(isset($_POST["name_client"])){

	$ajax = new PosController();
	$ajax -> name_client = $_POST["name_client"];
	$ajax -> surname_client = $_POST["surname_client"];
	$ajax -> dni_client = $_POST["dni_client"];
	$ajax -> email_client = $_POST["email_client"];
	$ajax -> phone_client = $_POST["phone_client"];
	$ajax -> address_client = $_POST["address_client"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> token = $_POST["token"];
	$ajax -> newClient();
}

/*=============================================
Agregar producto a la lista de órdenes
=============================================*/

if(isset($_POST["idProduct"])){

	$ajax = new PosController();
	$ajax -> idProduct = $_POST["idProduct"];
	$ajax -> idOrder = $_POST["idOrder"];
	$ajax -> idClient = $_POST["idClient"];
	$ajax -> seller = $_POST["seller"];
	$ajax -> idOffice = $_POST["idOffice"];
	$ajax -> token = $_POST["token"];
	$ajax -> addProductPos();

}


/*=============================================
Actualizar Cantidad
=============================================*/

if(isset($_POST["idSaleUpdate"])){

	$ajax = new PosController();
	$ajax -> idSaleUpdate = $_POST["idSaleUpdate"];
	$ajax -> qtySale = $_POST["qtySale"];
	$ajax -> subtotalSale = $_POST["subtotalSale"];
	$ajax -> token = $_POST["token"];
	$ajax -> updateSale();

}


/*=============================================
Remover Venta
=============================================*/

if(isset($_POST["idSaleDelete"])){

	$ajax = new PosController();
	$ajax -> idSaleDelete = $_POST["idSaleDelete"];
	$ajax -> token = $_POST["token"];
	$ajax -> deleteSale();

}

/*=============================================
Remover todas las Ventas
=============================================*/

if(isset($_POST["idOrderSale"])){
	$ajax = new PosController();
	$ajax -> idOrderSale = $_POST["idOrderSale"];
	$ajax -> token = $_POST["token"];
	$ajax -> deleteAllSale();

}

/*=============================================
Remover Órden
=============================================*/

if(isset($_POST["idOrderDelete"])){

	$ajax = new PosController();
	$ajax -> idOrderDelete = $_POST["idOrderDelete"];
	$ajax -> token = $_POST["token"];
	$ajax -> deleteOrder();

}

/*=============================================
Aprobar Entrada de Materia Prima
=============================================*/
if(isset($_POST["approveRawMaterialEntry"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (!isset($_SESSION["admin"]) || !in_array($_SESSION["admin"]->rol_admin, ["superadmin", "admin", "lab_admin"])) {
		echo "error|No tiene permisos para aprobar o costear entradas.";
		exit;
	}
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();
		
		$id_entry = $_POST['id_entry'];
		$id_raw_material = $_POST['id_raw_material'];
		$qty = (float)$_POST['qty'];
		$price = (float)$_POST['price'];
		$total = (float)$_POST['total'];
		$id_admin = $_POST['id_admin'];

		// Check status first to prevent double-approval
		$stmtCheck = $db->prepare("SELECT status_entry FROM raw_material_entries WHERE id_entry = :id");
		$stmtCheck->execute([':id' => $id_entry]);
		if($stmtCheck->fetchColumn() === 'aprobado') {
			echo "error|La entrada ya fue aprobada.";
			$db->rollBack();
			exit;
		}

		// Update entry
		$stmtEntry = $db->prepare("UPDATE raw_material_entries SET unit_price_entry = :price, total_cost_entry = :total, status_entry = 'aprobado', id_approved_by_entry = :admin, date_approved_entry = CURRENT_DATE() WHERE id_entry = :id");
		$stmtEntry->execute([':price' => $price, ':total' => $total, ':admin' => $id_admin, ':id' => $id_entry]);

		// Update stock
		$stmtStock = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty WHERE id_raw_material = :id_raw");
		$stmtStock->execute([':qty' => $qty, ':id_raw' => $id_raw_material]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error|" . $e->getMessage();
	}
	exit;
}

/*=============================================
Alternar Precio Mayorista en el Carrito
=============================================*/

if(isset($_POST["toggleWholesaleCart"])){

	$ajax = new PosController();
	$ajax -> idOrder = $_POST["idOrder"];
	$ajax -> isWholesale = $_POST["isWholesale"];
	$ajax -> token = $_POST["token"];
	$ajax -> toggleCartWholesale();

}

/*=============================================
Proxy API Genérico
=============================================*/
if(isset($_POST["apiProxy"])){
	$url = $_POST["url"];
	$method = $_POST["method"];
	$fields = json_decode($_POST["fields"], true);
	
	// SEC-01: Whitelist de endpoints
	$allowed_endpoints = ['raw_materials', 'raw_material_entries', 'recipes', 'productions', 'warehouse', 'quality_checks', 'qc_checks', 'raw_material_purchases'];
	$endpoint = explode('?', $url)[0];
	if(!in_array($endpoint, $allowed_endpoints)) {
		echo json_encode(["status" => 403, "results" => "Endpoint no permitido: " . $endpoint]);
		exit;
	}
	
	// Si fields es un array válido, lo convertimos a query string
	// para que cURL lo envíe como application/x-www-form-urlencoded
	// en lugar de multipart/form-data
	if (is_array($fields)) {
		$fields = http_build_query($fields);
	} else if (!empty($_POST["fields"])) {
		$fields = $_POST["fields"];
	}
	
	$res = CurlController::request($url, $method, $fields);
	echo json_encode($res);
	exit; // Importante para evitar que se imprima basura al final
}

/*=============================================
Guardar Receta
=============================================*/
if(isset($_POST["saveRecipe"])){
	// require_once removed
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();

		$name_product = trim(htmlspecialchars($_POST['name_product']));
		$batch_size = (float)$_POST['batch_size'];
		$unit_batch = trim(htmlspecialchars($_POST['unit_batch']));
		$id_office = (int)$_POST['id_office'];
		$id_admin = (int)$_POST['id_admin'];

		// INC-05: Validar duplicados de nombre en la sucursal
		$stmtDup = $db->prepare("SELECT id_product FROM products WHERE title_product = :name AND id_office_product = :office LIMIT 1");
		$stmtDup->execute([':name' => $name_product, ':office' => $id_office]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe un producto con ese nombre en esta sucursal.";
			$db->rollBack();
			exit;
		}

		// 1. Crear producto (a granel, is_compound_product=1)
		$stmtProd = $db->prepare("INSERT INTO products (title_product, unit_product, id_office_product, is_compound_product, status_product, stock_product, rte_product) VALUES (:name, :unit, 0, 1, 1, '0', '0')");
		$stmtProd->execute([
			':name' => $name_product,
			':unit' => $unit_batch
		]);
		$id_product = $db->lastInsertId();

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
		echo "error: " . $e->getMessage();
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
	$pkg_final_name = trim(htmlspecialchars($_POST['pkg_final_name'] ?? ''));
    $pkg_envase_type = trim(htmlspecialchars($_POST['pkg_envase_type'] ?? 'und'));
	$id_office = $_POST['id_office'] ?? 1; // Default or taken from session
	
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

			// Obtener precio actual de la última entrada aprobada
			$stmtPrice = $db->prepare("SELECT id_entry, unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
			$stmtPrice->execute([':id' => $id_raw]);
			$price_info = $stmtPrice->fetch(PDO::FETCH_ASSOC);
			$unit_price = $price_info ? (float)$price_info['unit_price_entry'] : 0;
			$id_entry = $price_info ? (int)$price_info['id_entry'] : 0;

			$subtotal = $unit_price * $qty_needed;
			$total_mp_cost += $subtotal;

			// Guardar snapshot para luego
			$costs_snapshot[] = [
				'id_raw' => $id_raw,
				'id_entry' => $id_entry,
				'qty' => $qty_needed,
				'price' => $unit_price,
				'subtotal' => $subtotal
			];
		}

		// 1.5. Procesar Materiales Extra de Envasado
		foreach($extra_mats as $ext) {
			$id_raw = $ext['id_raw'];
			$qty_needed = (float)$ext['qty'];

			$stmtCheck = $db->prepare("SELECT name_raw_material, stock_raw_material FROM raw_materials WHERE id_raw_material = :id");
			$stmtCheck->execute([':id' => $id_raw]);
			$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

			if($mp_info['stock_raw_material'] < $qty_needed) {
				echo "stock_insuficiente_envasado|" . $mp_info['name_raw_material'];
				$db->rollBack();
				exit;
			}

			$stmtPrice = $db->prepare("SELECT id_entry, unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
			$stmtPrice->execute([':id' => $id_raw]);
			$price_info = $stmtPrice->fetch(PDO::FETCH_ASSOC);
			$unit_price = $price_info ? (float)$price_info['unit_price_entry'] : 0;
			$id_entry = $price_info ? (int)$price_info['id_entry'] : 0;

			$subtotal = $unit_price * $qty_needed;
			$total_mp_cost += $subtotal;

			$costs_snapshot[] = [
				'id_raw' => $id_raw,
				'id_entry' => $id_entry,
				'qty' => $qty_needed,
				'price' => $unit_price,
				'subtotal' => $subtotal
			];

			$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
			$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
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
		$stmtSnap = $db->prepare("INSERT INTO production_material_costs (id_production_mat_cost, id_raw_material_mat_cost, id_entry_used_mat_cost, qty_used_mat_cost, unit_price_at_production, total_cost_mat_cost) VALUES (:id_prod, :id_raw, :id_ent, :qty, :price, :sub)");
		foreach($costs_snapshot as $snap) {
			$stmtSnap->execute([
				':id_prod' => $id_production,
				':id_raw' => $snap['id_raw'],
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
			':qty_packaged' => $pkg_final_qty,
			':id' => $id_production
		];
		$id_packaged_product = 0;

		// 5. Inventario de Productos Finales (is_compound_product = 1)
		if($pkg_final_name && $pkg_final_qty > 0) {
			// Buscar si existe el producto por nombre en catálogo global
			$stmtFind = $db->prepare("SELECT id_product, rte_product FROM products WHERE title_product = :name LIMIT 1");
			$stmtFind->execute([':name' => $pkg_final_name]);
			$existing_product = $stmtFind->fetch(PDO::FETCH_ASSOC);

			if($existing_product) {
				// Solo actualizamos la unidad, el stock se mantiene hasta pasar QC
				$stmtUpdProd = $db->prepare("UPDATE products SET unit_product = :unit WHERE id_product = :id");
				$stmtUpdProd->execute([':unit' => $pkg_envase_type, ':id' => $existing_product['id_product']]);
				$id_packaged_product = $existing_product['id_product'];
			} else {
				// Insertar nuevo producto final con stock 0 temporalmente
				$stmtInsProd = $db->prepare("INSERT INTO products (title_product, unit_product, stock_product, rte_product, is_compound_product, id_office_product, status_product) VALUES (:name, 'und', 0, 0, 1, :office, 1)");
				$stmtInsProd->execute([
					':name' => $pkg_final_name,
					':office' => $id_office
				]);
				$id_packaged_product = $db->lastInsertId();
			}
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
			qty_packaged_production = :qty_packaged,
			date_updated_production = NOW() 
		WHERE id_production = :id");
		$updateProdData[':id_pkg'] = $id_packaged_product;
		$stmtUpdateProd->execute($updateProdData);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error|" . $e->getMessage();
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
Guardar Producción (Pendiente)
=============================================*/
if(isset($_POST["saveProduction"])){
    $db = LocalConnection::connect();
    try {
        $db->beginTransaction();
        
        $id_recipe = $_POST['id_recipe'];
        $id_product = $_POST['id_product'];
        $batches = (float)$_POST['batches'];
        $total_qty = (float)$_POST['total_qty'];
        $cif = (float)$_POST['cif'];
        $mo = (float)$_POST['mo'];
        $id_office = $_POST['id_office'];
        $id_admin = $_POST['id_admin'];

        $stmt = $db->prepare("INSERT INTO productions (id_recipe_production, id_product_production, batches_production, total_qty_production, proj_indirect_cost, proj_labor_cost, status_production, id_office_production, id_admin_production, date_created_production) VALUES (:rec, :prod, :bat, :qty, :cif, :mo, 'pendiente', :off, :adm, NOW())");
        $stmt->execute([
            ':rec' => $id_recipe,
            ':prod' => $id_product,
            ':bat' => $batches,
            ':qty' => $total_qty,
            ':cif' => $cif,
            ':mo' => $mo,
            ':off' => $id_office,
            ':adm' => $id_admin
        ]);

        $db->commit();
        echo "ok";
    } catch (Exception $e) {
        $db->rollBack();
        echo "error|" . $e->getMessage();
    }
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

			$stmtCheck = $db->prepare("SELECT name_raw_material, stock_raw_material FROM raw_materials WHERE id_raw_material = :id");
			$stmtCheck->execute([':id' => $id_raw]);
			$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

			if($mp_info && $mp_info['stock_raw_material'] < $qty_needed) {
				echo "stock_insuficiente|" . $mp_info['name_raw_material'];
				$db->rollBack();
				exit;
			}
			
			// Descontar stock
			$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
			$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
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
		echo "error|" . $e->getMessage();
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
    $stmtMat = $db->prepare("SELECT pm.*, rm.name_raw_material, rm.unit_raw_material 
        FROM production_material_costs pm 
        JOIN raw_materials rm ON pm.id_raw_material_mat_cost = rm.id_raw_material 
        WHERE id_production_mat_cost = :id");
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

		// Validar duplicado de nombre en la sucursal (excluyendo el actual)
		$stmtDup = $db->prepare("SELECT id_product FROM products WHERE title_product = :name AND id_office_product = :office AND id_product != :id_prod LIMIT 1");
		$stmtDup->execute([
			':name' => $name_product,
			':office' => $id_office,
			':id_prod' => $id_product
		]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe un producto con ese nombre en esta sucursal.";
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
        echo "error|" . $e->getMessage();
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

		if ($id_product) {
			$stmtDelProduct = $db->prepare("DELETE FROM products WHERE id_product = :id");
			$stmtDelProduct->execute([':id' => $id_product]);
		}

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
// GET SUB WAREHOUSE STOCK
//=====================================
if (isset($_POST["getSubWarehouseStock"])) {
	$id_admin = $_POST["id_admin"];
	$id_office = $_POST["id_office"];
	$role = $_POST["role"];
	$db = LocalConnection::connect();

	if ($role == 'despachador' || $role == 'admin' || $role == 'superadmin') {
		// Despachador/admin: muestra inventario disponible del almacén
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   pi.stock_inventory as stock
			FROM products p
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1
		");
		$stmt->execute([':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		// Para cajero, vendedor u otro rol: verificar si tiene sub-almacén (por oficina)
		$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtHasSub->execute([':office' => $id_office]);
		$subRow = $stmtHasSub->fetch(PDO::FETCH_ASSOC);

		if ($subRow) {
			// Tiene sub-almacén — mostrar stock de la sucursal (suma de todos los sub-almacenes de esa sucursal)
			$stmt = $db->prepare("
				SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				       (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
				        COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
				FROM warehouse_assignments wa
				JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
				JOIN products p ON wa.id_product_assignment = p.id_product
				WHERE sw.id_office_sub_warehouse = :office
				GROUP BY wa.id_product_assignment
				HAVING stock > 0
			");
			$stmt->execute([':office' => $id_office]);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} else {
			// Sin sub-almacén: mostrar inventario principal de la sucursal
			$stmt = $db->prepare("
				SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				       pi.stock_inventory as stock
				FROM products p
				INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
				WHERE p.status_product = 1
			");
			$stmt->execute([':office' => $id_office]);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}
	echo json_encode($results);
	exit;
}

//=====================================
// GET MY WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getMyWarehouseMovements"])) {
	$id_admin = $_POST["id_admin"];
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT wa.date_created_assignment, wa.type_assignment, p.title_product, wa.qty_assignment, wa.notes_assignment
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		JOIN products p ON wa.id_product_assignment = p.id_product
		WHERE sw.id_office_sub_warehouse = :office
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET ASSIGNED BY OFFICE
//=====================================
if (isset($_POST["getAssignedByOffice"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$db = LocalConnection::connect();

	// Resolver la sucursal real del almacén (Didier tiene id_office=0 pero id_warehouse_admin=1 -> sucursal 8)
	$officeFilter = $id_office;
	if ($id_disp > 0) {
		$stmtDisp = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtDisp->execute([':id' => $id_disp]);
		$dispRow = $stmtDisp->fetch(PDO::FETCH_ASSOC);
		if ($dispRow && (int)$dispRow['id_office_admin'] > 0) {
			$officeFilter = (int)$dispRow['id_office_admin'];
		} elseif ($dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWH->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$officeFilter = (int)$stmtWH->fetchColumn();
		}
	}

	$stmt = $db->prepare("
		SELECT wa.id_product_assignment as id_product,
			   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment
						WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment
						ELSE 0 END) as total_assigned
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		WHERE sw.id_office_sub_warehouse = :office
		GROUP BY wa.id_product_assignment
	");
	$stmt->execute([':office' => $officeFilter]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PENDING QC
//=====================================
if(isset($_POST["getPendingQC"]) && $_POST["getPendingQC"] == "ok") {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT p.id_production, p.total_qty_production, p.date_updated_production,
			   p.real_total_cost, p.real_unit_cost, p.id_packaged_product,
			   p.qty_packaged_production,
			   pr.title_product AS name_recipe, pkg.title_product, pkg.unit_product
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		LEFT JOIN products pkg ON p.id_packaged_product = pkg.id_product
		WHERE p.id_office_production = :office AND p.status_production = 'pendiente_qc'
		ORDER BY p.date_updated_production ASC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
if (isset($_POST["getSubWarehousesDetail"])) {
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	
	$stmtSw = $db->prepare("
		SELECT sw.id_sub_warehouse, sw.name_sub_warehouse, COALESCE(a.name_admin, 'Compartido') as name_admin, o.title_office
		FROM sub_warehouses sw
		LEFT JOIN admins a ON sw.id_admin_sub_warehouse = a.id_admin
		LEFT JOIN offices o ON sw.id_office_sub_warehouse = o.id_office
	");
	$stmtSw->execute();
	$subs = $stmtSw->fetchAll(PDO::FETCH_ASSOC);
	
	$results = [];
	foreach ($subs as $s) {
		$stmtProd = $db->prepare("
			SELECT p.title_product,
				   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment 
							WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment 
							ELSE 0 END) as stock
			FROM warehouse_assignments wa
			JOIN products p ON wa.id_product_assignment = p.id_product
			WHERE wa.id_sub_warehouse_assignment = :id_sub
			GROUP BY wa.id_product_assignment
			HAVING stock > 0
		");
		$stmtProd->execute([':id_sub' => $s['id_sub_warehouse']]);
		$s['products'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
		$results[] = $s;
	}
	echo json_encode($results);
	exit;
}

//=====================================
// GET WAREHOUSE MOVEMENTS
//=====================================
if (isset($_POST["getWarehouseMovements"])) {
	$id_office = (int)$_POST["id_office"];
	$id_disp = isset($_POST["id_dispatcher"]) ? (int)$_POST["id_dispatcher"] : 0;
	$db = LocalConnection::connect();

	// Obtener la sucursal real del almacén del despachador (para Didier que tiene id_office=0)
	$officeFilter = $id_office;
	if ($id_disp > 0) {
		$stmtDisp = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :id LIMIT 1");
		$stmtDisp->execute([':id' => $id_disp]);
		$dispRow = $stmtDisp->fetch(PDO::FETCH_ASSOC);
		if ($dispRow && (int)$dispRow['id_office_admin'] > 0) {
			$officeFilter = (int)$dispRow['id_office_admin'];
		} elseif ($dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			$stmtWH = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWH->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$officeFilter = (int)$stmtWH->fetchColumn();
		}
	}

	$stmt = $db->prepare("
		SELECT wa.date_created_assignment, wa.type_assignment, p.title_product, wa.qty_assignment, wa.notes_assignment,
			   COALESCE(req_a.name_admin, sale_a.name_admin, 'Sucursal') as name_admin,
			   disp_a.name_admin as dispatcher_name,
			   o.title_office as office_name
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		JOIN products p ON wa.id_product_assignment = p.id_product
		LEFT JOIN inventory_requests ir ON wa.id_request_assignment = ir.id_request
		LEFT JOIN admins req_a ON ir.id_admin_request = req_a.id_admin
		LEFT JOIN admins sale_a ON (wa.type_assignment = 'venta' AND wa.id_dispatched_by = sale_a.id_admin)
		LEFT JOIN admins disp_a ON (wa.type_assignment = 'despacho' AND wa.id_dispatched_by = disp_a.id_admin)
		LEFT JOIN offices o ON sw.id_office_sub_warehouse = o.id_office
		WHERE sw.id_office_sub_warehouse = :office
		   OR (wa.type_assignment = 'despacho' AND (
		         disp_a.id_office_admin = :office 
		         OR (disp_a.id_office_admin = 0 AND disp_a.id_warehouse_admin IN (SELECT id_warehouse FROM warehouses WHERE id_office_warehouse = :office))
		      ))
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':office' => $officeFilter]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// ASSIGN TO SUB WAREHOUSE
//=====================================
if (isset($_POST["assignToSubWarehouse"])) {
	$id_product = $_POST["id_product"];
	$id_admin_dest = $_POST["id_admin_dest"];
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_office = $_POST["id_office"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();
		
		// Find destination admin's real office ID
		$stmtAdmin = $db->prepare("SELECT id_office_admin, name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
		$stmtAdmin->execute([':admin' => $id_admin_dest]);
		$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		$dest_office_id = $adminRow ? (int)$adminRow['id_office_admin'] : $id_office;
		
		// Find or create sub-warehouse for dest admin using destination office ID (compartido por oficina)
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':office' => $dest_office_id]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$subName = "Sub-Almacén de la Sucursal";
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (0, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':office' => $dest_office_id, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}
		
		// Insert warehouse_assignment record
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, type_assignment, notes_assignment, date_created_assignment)
			VALUES (:id_sub, :id_prod, :qty, :disp, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':id_sub' => $id_sub,
			':id_prod' => $id_product,
			':qty' => $qty,
			':disp' => $id_dispatched_by,
			':notes' => $notes
		]);

		// Descontar del inventario principal de la sucursal del despachador
		$stmtDispOffice = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :disp LIMIT 1");
		$stmtDispOffice->execute([':disp' => $id_dispatched_by]);
		$dispRow = $stmtDispOffice->fetch(PDO::FETCH_ASSOC);
		$dispOffice = $dispRow ? (int)$dispRow['id_office_admin'] : (int)$id_office;
		if ($dispOffice <= 0 && $dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			// Despachador con warehouse asignado — obtener la sucursal del warehouse
			$stmtWHOff = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWHOff->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$dispOffice = (int)$stmtWHOff->fetchColumn();
		}
		if ($dispOffice > 0) {
			$stmtDecrease = $db->prepare("
				UPDATE product_inventory
				SET stock_inventory = GREATEST(0, stock_inventory - :qty)
				WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
			");
			$stmtDecrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $dispOffice]);
		}

		// Incrementar en el inventario principal de la sucursal de destino
		if ($dest_office_id > 0) {
			$stmtIncrease = $db->prepare("
				INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
				VALUES (:prod, :office, :qty, 1, CURDATE())
				ON DUPLICATE KEY UPDATE
					stock_inventory = stock_inventory + :qty
			");
			$stmtIncrease->execute([':qty' => $qty, ':prod' => $id_product, ':office' => $dest_office_id]);
		}

		// Update products.stock_product
		$stmtUpdProd = $db->prepare("
			UPDATE products SET stock_product = (
				SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = :prod
			) WHERE id_product = :prod
		");
		$stmtUpdProd->execute([':prod' => $id_product]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
	}
	exit;
}

//=====================================
// SUBMIT QUALITY CHECK
//=====================================
if(isset($_POST["submitQualityCheck"]) && $_POST["submitQualityCheck"] == "ok") {
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (!isset($_SESSION["admin"]) || !in_array($_SESSION["admin"]->rol_admin, ["superadmin", "admin", "lab_admin", "qc_inspector"])) {
		echo "error|No tiene permisos para registrar controles de calidad.";
		exit;
	}
	$db = LocalConnection::connect();
	try {
		$db->beginTransaction();

		$id_production  = intval($_POST['id_production']);
		$id_admin       = intval($_POST['id_admin']);
		$id_office      = intval($_POST['id_office']);
		$result         = $_POST['result_qc']; // aprobado | rechazado | aprobado_con_obs
		$qty_approved   = floatval($_POST['qty_approved']);
		$qty_rejected   = floatval($_POST['qty_rejected']);
		$notes          = trim($_POST['notes_qc']);

		// Validar que la producción existe y está pendiente de QC
		$stmtCheck = $db->prepare("SELECT id_production, id_packaged_product, status_production, real_unit_cost FROM productions WHERE id_production = :id AND id_office_production = :office");
		$stmtCheck->execute([':id' => $id_production, ':office' => $id_office]);
		$prod = $stmtCheck->fetch(PDO::FETCH_ASSOC);

		if (!$prod || $prod['status_production'] !== 'pendiente_qc') {
			echo 'error|La producción no está en estado pendiente de QC.';
			exit;
		}

		// Insertar registro de QC
		$stmtInsert = $db->prepare("
			INSERT INTO quality_checks
				(id_production_qc, id_admin_qc, id_office_qc, result_qc, qty_approved_qc, qty_rejected_qc, notes_qc, date_created_qc)
			VALUES (:id_prod, :id_admin, :id_office, :result, :approved, :rejected, :notes, CURDATE())
		");
		$stmtInsert->execute([
			':id_prod'   => $id_production,
			':id_admin'  => $id_admin,
			':id_office' => $id_office,
			':result'    => $result,
			':approved'  => $qty_approved,
			':rejected'  => $qty_rejected,
			':notes'     => $notes
		]);

		$new_status = ($result === 'rechazado') ? 'rechazado' : 'completado';
		$stmtProd = $db->prepare("UPDATE productions SET 
			status_production = :status,
			qty_approved_production = :approved,
			qty_rejected_production = :rejected,
			result_qc_production = :result_qc,
			notes_qc_production = :notes
		WHERE id_production = :id");
		$stmtProd->execute([
			':status' => $new_status, 
			':approved' => $qty_approved,
			':rejected' => $qty_rejected,
			':result_qc' => $result,
			':notes' => $notes,
			':id' => $id_production
		]);

		// Solo ingresamos inventario de venta SI pasa el QC (aprobado o aprobado_con_obs)
		if ($qty_approved > 0 && $prod['id_packaged_product'] && $new_status === 'completado') {
			$stmtFind = $db->prepare("SELECT stock_product, rte_product FROM products WHERE id_product = :id");
            $stmtFind->execute([':id' => $prod['id_packaged_product']]);
            $pData = $stmtFind->fetch(PDO::FETCH_ASSOC);

            $old_stock = (float)$pData['stock_product'];
            $old_rte = (float)$pData['rte_product'];
            $unit_cost = (float)$prod['real_unit_cost'];
            
            // Recalculate unit cost based on approved quantity to account for rejected units (merma)
            $stmtCost = $db->prepare("SELECT real_total_cost FROM productions WHERE id_production = :id");
            $stmtCost->execute([':id' => $id_production]);
            $real_total_cost = (float)$stmtCost->fetchColumn();
            
            if ($qty_approved > 0) {
                $unit_cost = $real_total_cost / $qty_approved;
                // Update production with the real unit cost after QC
                $stmtUpdCost = $db->prepare("UPDATE productions SET real_unit_cost = :uc WHERE id_production = :id");
                $stmtUpdCost->execute([':uc' => $unit_cost, ':id' => $id_production]);
            }

            $new_stock = $old_stock + $qty_approved;
            $new_rte = (($old_stock * $old_rte) + ($qty_approved * $unit_cost)) / $new_stock;

			$stmtStock = $db->prepare("UPDATE products SET stock_product = :stock, rte_product = :rte WHERE id_product = :id_product");
			$stmtStock->execute([':stock' => $new_stock, ':rte' => $new_rte, ':id_product' => $prod['id_packaged_product']]);
		}

		$db->commit();
		echo json_encode(['status' => 'ok', 'result' => $new_status]);
	} catch (Exception $e) {
		$db->rollBack();
		echo 'error|' . $e->getMessage();
	}
	exit;
}

//=====================================
// GET PRODUCTS FOR WAREHOUSE
//=====================================
if (isset($_POST["getWarehouseProducts"])) {
	$id_warehouse = $_POST["id_warehouse"];
	$db = LocalConnection::connect();

	// Get the office ID of the warehouse
	$stmtWh = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
	$stmtWh->execute([':wh' => $id_warehouse]);
	$id_office = $stmtWh->fetchColumn();

	if ($id_office) {
		// Query stock in that warehouse (pi.stock_inventory - assigned stock)
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   pi.stock_inventory as stock
			FROM products p
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			WHERE p.status_product = 1
			HAVING stock > 0
			ORDER BY p.id_product DESC
		");
		$stmt->execute([':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		echo json_encode($results);
	} else {
		echo json_encode([]);
	}
	exit;
}

//=====================================
// CREATE INVENTORY REQUEST
//=====================================
if (isset($_POST["createInventoryRequest"])) {
	$id_product = $_POST["id_product"];
	$qty = $_POST["qty"];
	$notes = $_POST["notes"];
	$id_admin = $_POST["id_admin"];
	$id_office = $_POST["id_office"];
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$stmt = $db->prepare("
		INSERT INTO inventory_requests (id_admin_request, id_office_request, id_product_request, qty_request, status_request, notes_request, id_warehouse_request, date_created_request)
		VALUES (:admin, :office, :id_prod, :qty, 'pendiente', :notes, :id_wh, NOW())
	");
	if ($stmt->execute([
		':admin' => $id_admin,
		':office' => $id_office,
		':id_prod' => $id_product,
		':qty' => $qty,
		':notes' => $notes,
		':id_wh' => $id_warehouse
	])) {
		echo "ok";
	} else {
		echo "error";
	}
	exit;
}

//=====================================
// GET MY REQUESTS
//=====================================
if (isset($_POST["getMyRequests"])) {
	$id_admin = $_POST["id_admin"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT ir.date_created_request, p.title_product, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request,
			   COALESCE(w.title_warehouse, '-') as title_warehouse
		FROM inventory_requests ir
		JOIN products p ON ir.id_product_request = p.id_product
		LEFT JOIN warehouses w ON ir.id_warehouse_request = w.id_warehouse
		WHERE ir.id_admin_request = :admin
		ORDER BY ir.id_request DESC
	");
	$stmt->execute([':admin' => $id_admin]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET PENDING REQUESTS
//=====================================
if (isset($_POST["getPendingRequests"])) {
	$id_office = $_POST["id_office"];
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$whFilter = "";
	$params = [':office' => $id_office];
	if ($id_warehouse && $id_warehouse > 0) {
		$whFilter = " AND ir.id_warehouse_request = :wh ";
		$params[':wh'] = $id_warehouse;
	}

	$stmt = $db->prepare("
		SELECT ir.id_request, ir.date_created_request, ir.qty_request, ir.notes_request,
			   a.name_admin, p.title_product,
			   pi.stock_inventory as available_stock
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
		WHERE ir.status_request = 'pendiente' $whFilter
		ORDER BY ir.id_request DESC
	");
	$stmt->execute($params);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET QC HISTORY
//=====================================
if(isset($_POST["getQCHistory"]) && $_POST["getQCHistory"] == "ok") {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT qc.id_qc, qc.id_production_qc, qc.result_qc,
			   qc.qty_approved_qc, qc.qty_rejected_qc, qc.notes_qc,
			   qc.date_created_qc,
			   a.name_admin AS inspector_name,
			   pr.title_product, pr.unit_product,
			   p.qty_packaged_production, p.total_qty_production
		FROM quality_checks qc
		JOIN admins a ON qc.id_admin_qc = a.id_admin
		JOIN productions p ON qc.id_production_qc = p.id_production
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE qc.id_office_qc = :office
		ORDER BY qc.date_created_qc ASC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

// GET REQUEST HISTORY
//=====================================
if (isset($_POST["getRequestHistory"])) {
	$id_office = $_POST["id_office"];
	$id_warehouse = $_POST["id_warehouse"] ?? null;
	$db = LocalConnection::connect();

	$whFilter = "";
	$params = [];
	if ($id_warehouse && $id_warehouse > 0) {
		$whFilter = " AND ir.id_warehouse_request = :wh ";
		$params[':wh'] = $id_warehouse;
	}

	$stmt = $db->prepare("
		SELECT ir.date_created_request, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request,
			   a.name_admin, p.title_product
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		WHERE ir.status_request != 'pendiente' $whFilter
		ORDER BY ir.id_request DESC
	");
	$stmt->execute($params);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// REJECT REQUEST
//=====================================
if (isset($_POST["rejectRequest"])) {
	$id_request = $_POST["id_request"];
	$notes_dispatcher = $_POST["notes_dispatcher"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$db = LocalConnection::connect();

	$stmt = $db->prepare("
		UPDATE inventory_requests
		SET status_request = 'rechazada',
			notes_dispatcher_request = :notes,
			id_dispatched_by_request = :dispatcher
		WHERE id_request = :id
	");
	if ($stmt->execute([
		':notes' => $notes_dispatcher,
		':dispatcher' => $id_dispatched_by,
		':id' => $id_request
	])) {
		echo "ok";
	} else {
		echo "error";
	}
	exit;
}

//=====================================
// DISPATCH REQUEST
//=====================================
if (isset($_POST["dispatchRequest"])) {
	$id_request = $_POST["id_request"];
	$qty_dispatch = $_POST["qty_dispatch"];
	$notes_dispatcher = $_POST["notes_dispatcher"];
	$id_dispatched_by = $_POST["id_dispatched_by"];
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();

	try {
		$db->beginTransaction();
		
		// Get request details
		$stmtReq = $db->prepare("SELECT id_admin_request, id_office_request, id_product_request FROM inventory_requests WHERE id_request = :id LIMIT 1");
		$stmtReq->execute([':id' => $id_request]);
		$req = $stmtReq->fetch(PDO::FETCH_ASSOC);
		if (!$req) {
			throw new Exception("Solicitud no encontrada");
		}
		
		$id_admin_dest = $req['id_admin_request'];
		$id_product = $req['id_product_request'];
		
		// Find destination admin's real office ID
		$stmtAdmin = $db->prepare("SELECT id_office_admin, name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
		$stmtAdmin->execute([':admin' => $id_admin_dest]);
		$adminRow = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
		$dest_office_id = $adminRow ? (int)$adminRow['id_office_admin'] : $id_office;
		
		// Find or create sub-warehouse for dest admin using destination office ID (compartido por oficina)
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':office' => $dest_office_id]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$subName = "Sub-Almacén de la Sucursal";
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (0, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':office' => $dest_office_id, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}
		
		// Update request status
		$stmtUpd = $db->prepare("
			UPDATE inventory_requests
			SET status_request = 'despachada',
				qty_dispatched_request = :qty,
				notes_dispatcher_request = :notes,
				id_dispatched_by_request = :dispatcher
			WHERE id_request = :id
		");
		$stmtUpd->execute([
			':qty' => $qty_dispatch,
			':notes' => $notes_dispatcher,
			':dispatcher' => $id_dispatched_by,
			':id' => $id_request
		]);
		
		// Insert assignment
		$stmtAssign = $db->prepare("
			INSERT INTO warehouse_assignments (id_sub_warehouse_assignment, id_product_assignment, qty_assignment, id_dispatched_by, id_request_assignment, type_assignment, notes_assignment, date_created_assignment)
			VALUES (:id_sub, :id_prod, :qty, :disp, :id_req, 'despacho', :notes, NOW())
		");
		$stmtAssign->execute([
			':id_sub' => $id_sub,
			':id_prod' => $id_product,
			':qty' => $qty_dispatch,
			':disp' => $id_dispatched_by,
			':id_req' => $id_request,
			':notes' => $notes_dispatcher
		]);

		// Descontar del inventario principal de la sucursal del despachador
		$stmtDispOffice = $db->prepare("SELECT id_office_admin, id_warehouse_admin FROM admins WHERE id_admin = :disp LIMIT 1");
		$stmtDispOffice->execute([':disp' => $id_dispatched_by]);
		$dispRow = $stmtDispOffice->fetch(PDO::FETCH_ASSOC);
		$dispOffice = $dispRow ? (int)$dispRow['id_office_admin'] : (int)$id_office;
		if ($dispOffice <= 0 && $dispRow && (int)$dispRow['id_warehouse_admin'] > 0) {
			// Despachador con warehouse asignado — obtener la sucursal del warehouse
			$stmtWHOff = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
			$stmtWHOff->execute([':wh' => $dispRow['id_warehouse_admin']]);
			$dispOffice = (int)$stmtWHOff->fetchColumn();
		}
		if ($dispOffice > 0) {
			$stmtDecrease = $db->prepare("
				UPDATE product_inventory
				SET stock_inventory = GREATEST(0, stock_inventory - :qty)
				WHERE id_product_inventory = :prod AND id_office_inventory = :office AND status_inventory = 1
			");
			$stmtDecrease->execute([':qty' => $qty_dispatch, ':prod' => $id_product, ':office' => $dispOffice]);
		}

		// Incrementar en el inventario principal de la sucursal de destino
		if ($dest_office_id > 0) {
			$stmtIncrease = $db->prepare("
				INSERT INTO product_inventory (id_product_inventory, id_office_inventory, stock_inventory, status_inventory, date_created_inventory)
				VALUES (:prod, :office, :qty, 1, CURDATE())
				ON DUPLICATE KEY UPDATE
					stock_inventory = stock_inventory + :qty
			");
			$stmtIncrease->execute([':qty' => $qty_dispatch, ':prod' => $id_product, ':office' => $dest_office_id]);
		}

		// Update products.stock_product
		$stmtUpdProd = $db->prepare("
			UPDATE products SET stock_product = (
				SELECT COALESCE(SUM(stock_inventory), 0) FROM product_inventory WHERE id_product_inventory = :prod
			) WHERE id_product = :prod
		");
		$stmtUpdProd->execute([':prod' => $id_product]);
		
		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
	}
	exit;
}

/*=============================================
Editar Materia Prima
=============================================*/
if(isset($_POST["editRawMaterial"])){
	$db = LocalConnection::connect();
	try {
		$id_raw_material = intval($_POST['id_raw_material']);
		$name = trim(htmlspecialchars($_POST['name_raw_material']));
		$measure_type = $_POST['measure_type'];
		$unit = trim(htmlspecialchars($_POST['unit_raw_material']));
		$desc = trim(htmlspecialchars($_POST['description_raw_material']));
		
		// Validar duplicado de nombre (excluyendo el actual) en la misma sucursal
		$stmtGetOffice = $db->prepare("SELECT id_office_raw_material FROM raw_materials WHERE id_raw_material = :id");
		$stmtGetOffice->execute([':id' => $id_raw_material]);
		$id_office = $stmtGetOffice->fetchColumn();

		$stmtDup = $db->prepare("SELECT id_raw_material FROM raw_materials WHERE name_raw_material = :name AND id_office_raw_material = :office AND id_raw_material != :id LIMIT 1");
		$stmtDup->execute([':name' => $name, ':office' => $id_office, ':id' => $id_raw_material]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe una materia prima con ese nombre en esta sucursal.";
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
			echo "error|No se puede eliminar la materia prima porque aún tiene stock disponible (" . $mat['stock_raw_material'] . ").";
			exit;
		}

		// 2. Verificar si está en recetas
		$stmtRecipe = $db->prepare("SELECT COUNT(*) FROM recipe_ingredients WHERE id_raw_material_ingredient = :id");
		$stmtRecipe->execute([':id' => $id_raw_material]);
		if (intval($stmtRecipe->fetchColumn()) > 0) {
			echo "error|No se puede eliminar la materia prima porque está asociada a una o más recetas.";
			exit;
		}

		// 3. Verificar si está en historial de producciones
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
	$stmt = $db->prepare("SELECT * FROM raw_materials WHERE id_office_raw_material = :office ORDER BY id_raw_material ASC");
	$stmt->execute([':office' => $id_office]);
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

		// Validate duplicate name
		$stmtDup = $db->prepare("SELECT id_raw_material FROM raw_materials WHERE name_raw_material = :name AND id_office_raw_material = :office LIMIT 1");
		$stmtDup->execute([':name' => $name, ':office' => $id_office]);
		if($stmtDup->fetch()) {
			echo "error|Ya existe una materia prima con ese nombre en esta sucursal.";
			exit;
		}

		$stmt = $db->prepare("INSERT INTO raw_materials (name_raw_material, measure_type, unit_raw_material, description_raw_material, id_office_raw_material, id_admin_raw_material, stock_raw_material, date_created_raw_material) VALUES (:name, :measure, :unit, :desc, :office, :id_admin, 0, CURDATE())");
		$stmt->execute([
			':name' => $name,
			':measure' => $measure_type,
			':unit' => $unit,
			':desc' => $desc,
			':office' => $id_office,
			':id_admin' => $id_admin
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

		$stmt = $db->prepare("INSERT INTO raw_material_entries (id_raw_material_entry, qty_entry, lot_number_entry, supplier_entry, date_entry, id_admin_entry, status_entry, date_created_entry) VALUES (:id_raw, :qty, :lot, :supplier, :date, :id_admin, 'pendiente', NOW())");
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
// GET LAB ENTRIES
//=====================================
if(isset($_POST["getLabEntries"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT rme.*, rm.name_raw_material, rm.unit_raw_material 
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
	
	// Fetch all recipes with product title and cost
	$stmt = $db->prepare("
		SELECT r.*, p.title_product, p.rte_product 
		FROM recipes r 
		JOIN products p ON r.id_product_recipe = p.id_product 
		WHERE r.id_office_recipe = :office 
		ORDER BY r.id_recipe ASC
	");
	$stmt->execute([':office' => $id_office]);
	$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Fetch ingredients for each recipe
	foreach ($recipes as &$recipe) {
		$stmtIng = $db->prepare("
			SELECT ri.*, rm.name_raw_material, rm.unit_raw_material 
			FROM recipe_ingredients ri 
			JOIN raw_materials rm ON ri.id_raw_material_ingredient = rm.id_raw_material 
			WHERE ri.id_recipe_ingredient = :id_recipe
		");
		$stmtIng->execute([':id_recipe' => $recipe['id_recipe']]);
		$recipe['ingredients'] = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
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
// GET LAB WAREHOUSE
//=====================================
if(isset($_POST["getLabWarehouse"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT 
			p.id_product AS id_warehouse, 
			p.title_product AS name_product, 
			p.stock_product AS qty_warehouse, 
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
if(isset($_POST["getLoggedUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	if (isset($_SESSION["admin"])) {
		$db = LocalConnection::connect();
		$id_office = intval($_SESSION["admin"]->id_office_admin);
		
		$stmt = $db->prepare("SELECT * FROM offices WHERE id_office = :id");
		$stmt->execute([':id' => $id_office]);
		$office = $stmt->fetch(PDO::FETCH_ASSOC);

		echo json_encode([
			'status' => 200,
			'user' => [
				'id_admin' => intval($_SESSION["admin"]->id_admin),
				'name_admin' => $_SESSION["admin"]->name_admin,
				'rol_admin' => $_SESSION["admin"]->rol_admin
			],
			'office' => $office,
			'token' => 'session-token'
		]);
	} else {
		echo json_encode([
			'status' => 401,
			'message' => 'No session active'
		]);
	}
	exit;
}

//=====================================
// LOGOUT LAB USER
//=====================================
if(isset($_POST["logoutLabUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	session_destroy();
	echo "ok";
	exit;
}

//=====================================
// LOGIN LAB USER
//=====================================
if(isset($_POST["loginLabUser"])){
	if (session_status() === PHP_SESSION_NONE) { session_start(); }
	
	$email = $_POST["email"] ?? '';
	$password = $_POST["password"] ?? '';
	
	if (empty($email) || empty($password)) {
		echo json_encode([
			'status' => 400,
			'message' => 'El correo y la contraseña son requeridos'
		]);
		exit;
	}
	
	$db = LocalConnection::connect();
	$stmt = $db->prepare("SELECT * FROM admins WHERE email_admin = :email AND status_admin = 1");
	$stmt->execute([':email' => $email]);
	$admin = $stmt->fetch(PDO::FETCH_OBJ);
	
	if ($admin) {
		$encrypted = crypt($password, '$2a$07$azybxcags23425sdg23sdfhsd$');
		if ($admin->password_admin === $encrypted) {
			// Establecemos la sesión PHP como el login original
			$_SESSION["admin"] = $admin;
			
			// Obtenemos la información de la sucursal
			$id_office = intval($admin->id_office_admin);
			$stmtOffice = $db->prepare("SELECT * FROM offices WHERE id_office = :id");
			$stmtOffice->execute([':id' => $id_office]);
			$office = $stmtOffice->fetch(PDO::FETCH_ASSOC);

			echo json_encode([
				'status' => 200,
				'user' => [
					'id_admin' => intval($admin->id_admin),
					'name_admin' => $admin->name_admin,
					'rol_admin' => $admin->rol_admin
				],
				'office' => $office,
				'token' => $admin->token_admin
			]);
			exit;
		}
	}
	
	echo json_encode([
		'status' => 401,
		'message' => 'Correo o contraseña incorrectos, o el usuario está inactivo.'
	]);
	exit;
}


//=====================================
// SAVE PRODUCTION (Iniciar Producción)
//=====================================
if(isset($_POST["saveProduction"])){
	$db = LocalConnection::connect();
	try {
		$id_recipe   = intval($_POST['id_recipe']);
		$id_product  = intval($_POST['id_product']);
		$batches     = floatval($_POST['batches']);
		$total_qty   = floatval($_POST['total_qty']);
		$cif         = floatval($_POST['cif']);
		$mo          = floatval($_POST['mo']);
		$id_office   = intval($_POST['id_office']);
		$id_admin    = intval($_POST['id_admin']);

		// 1. Fetch recipe ingredients
		$stmtIng = $db->prepare("
			SELECT ri.id_raw_material_ingredient AS id_rm, ri.qty_ingredient,
			       rm.stock_raw_material, rm.name_raw_material
			FROM recipe_ingredients ri
			JOIN raw_materials rm ON ri.id_raw_material_ingredient = rm.id_raw_material
			WHERE ri.id_recipe_ingredient = :id_recipe
		");
		$stmtIng->execute([':id_recipe' => $id_recipe]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

		// 2. Check stock for each ingredient
		$totalMatCost = 0;
		foreach ($ingredients as $ing) {
			$needed = $ing['qty_ingredient'] * $batches;
			if ($ing['stock_raw_material'] < $needed - 0.001) {
				echo "stock_insuficiente|" . $ing['name_raw_material'];
				exit;
			}
		}

		// 3. Insert production record
		$projTotal = $totalMatCost + $mo + $cif;
		$projUnit  = ($total_qty > 0) ? ($projTotal / $total_qty) : 0;

		$stmtProd = $db->prepare("
			INSERT INTO productions (id_recipe_production, id_product_production, batches_production,
			total_qty_production, proj_labor_cost, proj_indirect_cost, proj_total_cost, proj_unit_cost,
			status_production, start_date_production, id_admin_production, id_office_production,
			date_created_production)
			VALUES (:recipe, :product, :batches, :qty, :mo, :cif, :total, :unit, 'proceso',
			CURDATE(), :admin, :office, CURDATE())
		");
		$stmtProd->execute([
			':recipe'  => $id_recipe,
			':product' => $id_product,
			':batches' => $batches,
			':qty'     => $total_qty,
			':mo'      => $mo,
			':cif'     => $cif,
			':total'   => $mo + $cif,
			':unit'    => 0,
			':admin'   => $id_admin,
			':office'  => $id_office
		]);
		$id_production = $db->lastInsertId();

		// 4. Consume raw materials from stock and record costs
		foreach ($ingredients as $ing) {
			$needed = $ing['qty_ingredient'] * $batches;

			// Get latest entry price
			$stmtPrice = $db->prepare("
				SELECT unit_price_entry FROM raw_material_entries
				WHERE id_raw_material_entry = :id_rm AND status_entry = 'aprobado'
				ORDER BY date_approved_entry DESC, id_entry DESC LIMIT 1
			");
			$stmtPrice->execute([':id_rm' => $ing['id_rm']]);
			$unitPrice = floatval($stmtPrice->fetchColumn() ?: 0);
			$subtotal  = $unitPrice * $needed;
			$totalMatCost += $subtotal;

			// Deduct from stock
			$stmtDeduct = $db->prepare("
				UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty
				WHERE id_raw_material = :id_rm
			");
			$stmtDeduct->execute([':qty' => $needed, ':id_rm' => $ing['id_rm']]);

			// Get entry id for traceability
			$stmtEntry = $db->prepare("
				SELECT id_entry FROM raw_material_entries
				WHERE id_raw_material_entry = :id_rm AND status_entry = 'aprobado'
				ORDER BY date_approved_entry DESC, id_entry DESC LIMIT 1
			");
			$stmtEntry->execute([':id_rm' => $ing['id_rm']]);
			$id_entry = intval($stmtEntry->fetchColumn() ?: 0);

			// Insert material cost record
			$stmtMat = $db->prepare("
				INSERT INTO production_material_costs
				(id_production_mat_cost, id_raw_material_mat_cost, id_entry_used_mat_cost,
				 qty_used_mat_cost, unit_price_at_production, total_cost_mat_cost)
				VALUES (:prod, :rm, :entry, :qty, :price, :total)
			");
			$stmtMat->execute([
				':prod'  => $id_production,
				':rm'    => $ing['id_rm'],
				':entry' => $id_entry,
				':qty'   => $needed,
				':price' => $unitPrice,
				':total' => $subtotal
			]);
		}

		// 5. Update production with real material cost
		$totalCost = $totalMatCost + $mo + $cif;
		$unitCost  = ($total_qty > 0) ? ($totalCost / $total_qty) : 0;
		$stmtUpdate = $db->prepare("
			UPDATE productions
			SET real_materials_cost = :mat, real_labor_cost = :mo, real_indirect_cost = :cif,
			    real_total_cost = :total, real_unit_cost = :unit,
			    proj_materials_cost = :mat, proj_total_cost = :total, proj_unit_cost = :unit
			WHERE id_production = :id
		");
		$stmtUpdate->execute([
			':mat'   => $totalMatCost,
			':mo'    => $mo,
			':cif'   => $cif,
			':total' => $totalCost,
			':unit'  => $unitCost,
			':id'    => $id_production
		]);

		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// COMPLETE PRODUCTION (Envasado → QC)
//=====================================
if(isset($_POST["completeProduction"])){
	$db = LocalConnection::connect();
	try {
		$id_production    = intval($_POST['id_production']);
		$id_recipe        = intval($_POST['id_recipe']);
		$id_product       = intval($_POST['id_product']);
		$extra_mo         = floatval($_POST['extra_mo']);
		$extra_cif        = floatval($_POST['extra_cif']);
		$pkg_final_qty    = intval($_POST['pkg_final_qty']);
		$pkg_final_name   = trim($_POST['pkg_final_name']);
		$pkg_envase_type  = trim($_POST['pkg_envase_type']);
		$real_bulk_qty    = $_POST['real_bulk_qty'] !== '' ? floatval($_POST['real_bulk_qty']) : null;
		$original_bulk_qty = floatval($_POST['original_bulk_qty']);
		$extra_mats       = json_decode($_POST['extra_mats'], true) ?: [];
		$id_office        = intval($_POST['id_office']);

		// Check packaging material stock
		foreach ($extra_mats as $mat) {
			$id_raw = intval($mat['id_raw']);
			$qty    = floatval($mat['qty']);
			$stmtCheck = $db->prepare("SELECT stock_raw_material, name_raw_material FROM raw_materials WHERE id_raw_material = :id");
			$stmtCheck->execute([':id' => $id_raw]);
			$rm = $stmtCheck->fetch(PDO::FETCH_ASSOC);
			if ($rm && $rm['stock_raw_material'] < $qty - 0.001) {
				echo "stock_insuficiente|" . $rm['name_raw_material'];
				exit;
			}
		}

		// Consume packaging materials and record costs
		$pkgMatCost = 0;
		foreach ($extra_mats as $mat) {
			$id_raw = intval($mat['id_raw']);
			$qty    = floatval($mat['qty']);

			$stmtPrice = $db->prepare("SELECT unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
			$stmtPrice->execute([':id' => $id_raw]);
			$unitPrice = floatval($stmtPrice->fetchColumn() ?: 0);
			$subtotal  = $unitPrice * $qty;
			$pkgMatCost += $subtotal;

			$stmtDeduct = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
			$stmtDeduct->execute([':qty' => $qty, ':id' => $id_raw]);

			$stmtEntry = $db->prepare("SELECT id_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
			$stmtEntry->execute([':id' => $id_raw]);
			$id_entry = intval($stmtEntry->fetchColumn() ?: 0);

			$stmtMat = $db->prepare("INSERT INTO production_material_costs (id_production_mat_cost, id_raw_material_mat_cost, id_entry_used_mat_cost, qty_used_mat_cost, unit_price_at_production, total_cost_mat_cost) VALUES (:prod, :rm, :entry, :qty, :price, :total)");
			$stmtMat->execute([':prod' => $id_production, ':rm' => $id_raw, ':entry' => $id_entry, ':qty' => $qty, ':price' => $unitPrice, ':total' => $subtotal]);
		}

		// Get current production costs
		$stmtGet = $db->prepare("SELECT real_materials_cost, real_labor_cost, real_indirect_cost FROM productions WHERE id_production = :id");
		$stmtGet->execute([':id' => $id_production]);
		$prod = $stmtGet->fetch(PDO::FETCH_ASSOC);

		$totalMat   = floatval($prod['real_materials_cost']) + $pkgMatCost;
		$totalMO    = floatval($prod['real_labor_cost']) + $extra_mo;
		$totalCIF   = floatval($prod['real_indirect_cost']) + $extra_cif;
		$totalCost  = $totalMat + $totalMO + $totalCIF;
		$unitCost   = ($pkg_final_qty > 0) ? ($totalCost / $pkg_final_qty) : 0;

		// Update production to pendiente_qc
		$yieldVariance = ($real_bulk_qty !== null) ? ($real_bulk_qty - $original_bulk_qty) : 0;
		$yieldVariancePct = ($original_bulk_qty > 0 && $real_bulk_qty !== null) ? (($real_bulk_qty - $original_bulk_qty) / $original_bulk_qty * 100) : 0;

		$stmtUpdate = $db->prepare("
			UPDATE productions SET
			  status_production = 'pendiente_qc',
			  real_materials_cost = :mat,
			  pkg_labor_cost = :pkg_mo,
			  pkg_indirect_cost = :pkg_cif,
			  real_total_cost = :total,
			  real_unit_cost = :unit,
			  qty_packaged_production = :pkg_qty,
			  pkg_name_production = :pkg_name,
			  real_bulk_qty = :real_bulk,
			  yield_variance = :variance,
			  yield_variance_pct = :variance_pct,
			  end_date_production = CURDATE()
			WHERE id_production = :id
		");
		$stmtUpdate->execute([
			':mat'          => $totalMat,
			':pkg_mo'       => $extra_mo,
			':pkg_cif'      => $extra_cif,
			':total'        => $totalCost,
			':unit'         => $unitCost,
			':pkg_qty'      => $pkg_final_qty,
			':pkg_name'     => $pkg_final_name,
			':real_bulk'    => $real_bulk_qty,
			':variance'     => $yieldVariance,
			':variance_pct' => $yieldVariancePct,
			':id'           => $id_production
		]);

		echo "ok";
	} catch (Exception $e) {
		echo "error|" . $e->getMessage();
	}
	exit;
}

//=====================================
// GET PENDING QC (Cola de Calidad)
//=====================================
if(isset($_POST["getPendingQC"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT p.*, pr.title_product, pr.unit_product,
		       r.unit_batch_recipe AS unit_recipe
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		JOIN recipes r ON p.id_recipe_production = r.id_recipe
		WHERE p.id_office_production = :office AND p.status_production = 'pendiente_qc'
		ORDER BY p.id_production DESC
	");
	$stmt->execute([':office' => $id_office]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(array_values($rows));
	exit;
}

//=====================================
// GET QC HISTORY (Historial Calidad)
//=====================================
if(isset($_POST["getQCHistory"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT qc.*, p.total_qty_production, p.qty_packaged_production,
		       p.status_production, pr.title_product, pr.unit_product,
		       a.name_admin AS qc_inspector_name
		FROM quality_checks qc
		JOIN productions p ON qc.id_production_qc = p.id_production
		JOIN products pr ON p.id_product_production = pr.id_product
		LEFT JOIN admins a ON qc.id_admin_qc = a.id_admin
		WHERE qc.id_office_qc = :office
		ORDER BY qc.id_qc DESC
	");
	$stmt->execute([':office' => $id_office]);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo json_encode(array_values($rows));
	exit;
}

//=====================================
// SUBMIT QUALITY CHECK
//=====================================
if(isset($_POST["submitQualityCheck"])){
	$db = LocalConnection::connect();
	try {
		$id_production = intval($_POST['id_production']);
		$id_admin      = intval($_POST['id_admin']);
		$id_office     = intval($_POST['id_office']);
		$result        = $_POST['result_qc'];
		$qty_approved  = floatval($_POST['qty_approved']);
		$qty_rejected  = floatval($_POST['qty_rejected']);
		$notes         = trim($_POST['notes_qc']);

		// Get production info
		$stmtProd = $db->prepare("SELECT * FROM productions WHERE id_production = :id");
		$stmtProd->execute([':id' => $id_production]);
		$prod = $stmtProd->fetch(PDO::FETCH_ASSOC);

		if (!$prod) {
			echo json_encode(['status' => 'error', 'message' => 'Producción no encontrada']);
			exit;
		}

		// 1. Insert QC record
		$stmtQC = $db->prepare("
			INSERT INTO quality_checks
			(id_production_qc, id_admin_qc, id_office_qc, result_qc, qty_approved_qc, qty_rejected_qc, notes_qc, date_created_qc)
			VALUES (:prod, :admin, :office, :result, :approved, :rejected, :notes, CURDATE())
		");
		$stmtQC->execute([
			':prod'     => $id_production,
			':admin'    => $id_admin,
			':office'   => $id_office,
			':result'   => $result,
			':approved' => $qty_approved,
			':rejected' => $qty_rejected,
			':notes'    => $notes
		]);

		// 2. Calculate real unit cost based on approved qty
		$totalCost = floatval($prod['real_total_cost']);
		$realUnitCost = ($qty_approved > 0) ? ($totalCost / $qty_approved) : 0;

		if ($result !== 'rechazado' && $qty_approved > 0) {
			// 3a. Set production to completado
			$stmtDone = $db->prepare("
				UPDATE productions SET
				  status_production = 'completado',
				  qty_approved_production = :approved,
				  qty_rejected_production = :rejected,
				  real_unit_cost = :unit_cost,
				  result_qc_production = :result,
				  notes_qc_production = :notes,
				  end_date_production = CURDATE()
				WHERE id_production = :id
			");
			$stmtDone->execute([
				':approved'   => $qty_approved,
				':rejected'   => $qty_rejected,
				':unit_cost'  => $realUnitCost,
				':result'     => $result,
				':notes'      => $notes,
				':id'         => $id_production
			]);

			// 4. Add product to final inventory (update stock in products table)
			// Find the packaged product (by pkg_name or existing product)
			$id_product = intval($prod['id_product_production']);
			$stmtStock = $db->prepare("
				UPDATE products SET stock_product = COALESCE(CAST(stock_product AS DECIMAL(10,2)), 0) + :qty,
				rte_product = :cost
				WHERE id_product = :id
			");
			$stmtStock->execute([
				':qty'  => $qty_approved,
				':cost' => $realUnitCost,
				':id'   => $id_product
			]);

			echo json_encode(['status' => 'ok', 'result' => 'completado']);
		} else {
			// Rechazado: mark as rejected
			$stmtRej = $db->prepare("
				UPDATE productions SET
				  status_production = 'rechazado',
				  qty_approved_production = :approved,
				  qty_rejected_production = :rejected,
				  result_qc_production = :result,
				  notes_qc_production = :notes,
				  end_date_production = CURDATE()
				WHERE id_production = :id
			");
			$stmtRej->execute([
				':approved' => $qty_approved,
				':rejected' => $qty_rejected,
				':result'   => $result,
				':notes'    => $notes,
				':id'       => $id_production
			]);

			echo json_encode(['status' => 'ok', 'result' => 'rechazado']);
		}
	} catch (Exception $e) {
		echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	}
	exit;
}

//=====================================
// GET PRODUCTION DETAILS (Historial Detallado)
//=====================================
if(isset($_POST["getProductionDetails"])){
	$db = LocalConnection::connect();
	$id_production = intval($_POST['id_production']);

	// Main production record
	$stmtProd = $db->prepare("
		SELECT p.*,
		       pr.title_product, pr.unit_product,
		       a.name_admin,
		       r.unit_batch_recipe AS unit_product
		FROM productions p
		JOIN products pr ON p.id_product_production = pr.id_product
		JOIN recipes r ON p.id_recipe_production = r.id_recipe
		LEFT JOIN admins a ON p.id_admin_production = a.id_admin
		WHERE p.id_production = :id
	");
	$stmtProd->execute([':id' => $id_production]);
	$production = $stmtProd->fetch(PDO::FETCH_ASSOC);

	// Get QC inspector name if QC exists
	if ($production) {
		$stmtQC = $db->prepare("
			SELECT qc.*, a.name_admin AS qc_inspector_name
			FROM quality_checks qc
			LEFT JOIN admins a ON qc.id_admin_qc = a.id_admin
			WHERE qc.id_production_qc = :id
			ORDER BY qc.id_qc DESC LIMIT 1
		");
		$stmtQC->execute([':id' => $id_production]);
		$qc = $stmtQC->fetch(PDO::FETCH_ASSOC);
		if ($qc) {
			$production['qc_notes'] = $qc['notes_qc'];
			$production['qc_inspector_name'] = $qc['qc_inspector_name'];
			$production['result_qc'] = $qc['result_qc'];
			$production['qty_approved_qc'] = $qc['qty_approved_qc'];
			$production['qty_rejected_qc'] = $qc['qty_rejected_qc'];
		}
	}

	// Material costs
	$stmtMat = $db->prepare("
		SELECT pmc.*, rm.name_raw_material, rm.unit_raw_material
		FROM production_material_costs pmc
		JOIN raw_materials rm ON pmc.id_raw_material_mat_cost = rm.id_raw_material
		WHERE pmc.id_production_mat_cost = :id
	");
	$stmtMat->execute([':id' => $id_production]);
	$materials = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

	echo json_encode([
		'production' => $production,
		'materials'  => $materials
	]);
	exit;
}

//=====================================
// GET LAB MATERIALS (Insumos de Envasado)
//=====================================
if(isset($_POST["getLabMaterials"])){
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT id_raw_material, name_raw_material, unit_raw_material,
		       stock_raw_material, measure_type
		FROM raw_materials
		WHERE id_office_raw_material = :office AND status_raw_material = 1
		ORDER BY name_raw_material ASC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode([
		'status' => 200,
		'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}
