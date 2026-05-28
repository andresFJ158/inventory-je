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
		$method = "GET";
		$fields = array();
		$role = $_POST["sellerRole"] ?? null;
		$id_admin = $_POST["sellerId"] ?? null;

		if ($role !== null && $role != "superadmin" && $role != "admin" && $role != "despachador") {
			$db = LocalConnection::connect();
			
			$categoryQuery = "";
			$searchQuery = "";
			$params = [':admin' => $id_admin, ':office' => $this->idOffice];
			
			if ($this->category != "all") {
				$categoryQuery = " AND p.id_category_product = :category";
				$params[':category'] = $this->category;
			}
			
			if ($this->search != "") {
				$searchQuery = " AND (p.title_product LIKE :search OR p.sku_product LIKE :search OR p.code_product LIKE :search)";
				$params[':search'] = "%" . $this->search . "%";
			}
			
			$sql = "
				SELECT p.*, c.title_category, c.img_category, c.order_category, c.status_category,
					   COALESCE(sub.stock, 0) as stock_product
				FROM products p
				INNER JOIN categories c ON p.id_category_product = c.id_category
				LEFT JOIN (
					SELECT wa.id_product_assignment,
						   (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
							COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
					FROM warehouse_assignments wa
					JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
					WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
					GROUP BY wa.id_product_assignment
				) sub ON p.id_product = sub.id_product_assignment
				WHERE p.id_office_product = :office AND p.status_product = 1
				$categoryQuery
				$searchQuery
				ORDER BY p.id_product DESC
			";
			
			$stmtAll = $db->prepare($sql);
			$stmtAll->execute($params);
			$allProducts = $stmtAll->fetchAll(PDO::FETCH_CLASS);
			
			$totalPageProducts = ceil(count($allProducts) / $this->limit);
			
			// Apply limit and offset
			$sqlLimit = $sql . " LIMIT " . (int)$this->startAt . ", " . (int)$this->limit;
			$stmtLimit = $db->prepare($sqlLimit);
			$stmtLimit->execute($params);
			$products = $stmtLimit->fetchAll(PDO::FETCH_CLASS);
		} else {
			if($this->category == "all"){

				if($this->search == ""){

					$url = "relations?rel=products,categories&type=product,category&linkTo=id_office_product,status_product&equalTo=".$this->idOffice.",1&orderBy=id_product&orderMode=DESC&startAt=".$this->startAt."&endAt=".$this->limit;
					$method = "GET";
					$fields = array();

					$products = CurlController::request($url,$method,$fields);

					if($products->status == 200){

						$products = $products->results;	

						/*=============================================
						Traer Total de productos
						=============================================*/

						$url = "relations?rel=products,categories&type=product,category&linkTo=id_office_product,status_product&equalTo=".$this->idOffice.",1";

						$totalPageProducts = ceil(CurlController::request($url,$method,$fields)->total/$this->limit);

					}else{

						$products = array();
						$totalPageProducts = 0;
					}

				}else{

					/*=============================================
					Columnas de búsqueda - Buscar en todos los atributos del producto
					Basado en la estructura real de la tabla: title_product, sku_product, code_product (Código de Barras), unit_product
					=============================================*/

					$linkTo = ["title_product","sku_product","code_product","unit_product"];

					/*=============================================
					Itineración de búsqueda - Buscar en todos los campos
					=============================================*/

					$allProducts = array();
					$foundResults = false;

					// Obtener más resultados de cada campo para asegurar suficientes resultados únicos
					// Aumentar este valor si se necesitan más resultados por búsqueda
					$maxResultsPerField = 500;

					foreach ($linkTo as $key => $value) {
						
						try {
							// Obtener resultados sin paginación inicial para combinar todos los campos
							$url = "relations?rel=products,categories&type=product,category&linkTo=".$value.",id_office_product,status_product&search=".str_replace(" ", "_",$this->search).",".$this->idOffice.",1&orderBy=id_product&orderMode=DESC&startAt=0&endAt=".$maxResultsPerField;

							$method = "GET";
							$fields = array();

							$products = CurlController::request($url,$method,$fields);

							if($products->status == 200 && !empty($products->results)){

								// Combinar resultados de todos los campos
								foreach($products->results as $product){
									// Evitar duplicados usando id_product como clave
									if(isset($product->id_product)){
										$allProducts[$product->id_product] = $product;
									}
								}
								$foundResults = true;
							}
						} catch (Exception $e) {
							// Si un campo no existe o hay error, continuar con el siguiente campo
							continue;
						}
					}

					/*=============================================
					Si encontramos resultados, procesarlos
					=============================================*/

					if($foundResults && !empty($allProducts)){

						// Convertir array asociativo a array indexado y ordenar por id_product DESC
						$productsArray = array_values($allProducts);
						
						// Ordenar por id_product descendente
						usort($productsArray, function($a, $b) {
							return $b->id_product - $a->id_product;
						});

						// Aplicar paginación
						$products = array_slice($productsArray, $this->startAt, $this->limit);

						/*=============================================
						Traer Total de productos (contar todos los resultados únicos)
						=============================================*/

						$totalPageProducts = ceil(count($allProducts)/$this->limit);

					}else{

						$products = array();
						$totalPageProducts = 0;

					}

				}

			}else{

				$url = "relations?rel=products,categories&type=product,category&linkTo=id_office_product,status_product,id_category_product&equalTo=".$this->idOffice.",1,".$this->category."&orderBy=id_product&orderMode=DESC&startAt=".$this->startAt."&endAt=".$this->limit;
				$method = "GET";
				$fields = array();

				$products = CurlController::request($url,$method,$fields);

				if($products->status == 200){

					$products = $products->results;	

					/*=============================================
					Traer Total de productos
					=============================================*/

					$url = "relations?rel=products,categories&type=product,category&linkTo=id_office_product,status_product,id_category_product&equalTo=".$this->idOffice.",1,".$this->category;

					$totalPageProducts = ceil(CurlController::request($url,$method,$fields)->total/$this->limit);

				}else{

					$products = array();
					$totalPageProducts = 0;
				}

			}
		}

		$htmlProducts = "";

		if (!empty($products)){
		
			foreach ($products as $key => $value){

				$htmlProducts .= '<div class="col-12 col-lg-6 col-xl-4 p-2 btn addProductPos" idProduct="'.$value->id_product.'">
					
					<div class="card rounded border-0 position-relative">';

						if ($value->discount_product > 0){

							$htmlProducts .= '<div class="position-absolute small bg-red p-1 shadow-sm rounded" style="top:4px; left:4px; font-size:10px">'.$value->discount_product.'% OFF</div>';
							
						}
						
						$imgSrc = TemplateController::fallbackProductImage($value->sku_product ?? '', $value->title_product ?? '', $value->img_product ?? '');
						if (empty($imgSrc) || $imgSrc === 'NULL' || $imgSrc === 'null') {
							$imgSrc = 'views/assets/img/multimedia.png';
						}

						$htmlProducts .= '<div class="position-absolute small bg-white p-1 shadow-sm rounded" style="top:4px; right:4px; font-size:10px">'.$value->sku_product.'</div>

						<img src="'.urldecode($imgSrc).'" class="card-img-top px-5 py-3 mx-auto" style="width:200px !important">

						<div class="card-body">
							
							<h6 class="font-weight-bold text-gray samll">'.urldecode($value->title_category).'</h6>
							<h6 class="card-title pb-2 font-weight-bold">'.urldecode($value->title_product).'</h6>

							<div class="d-flex justify-content-between">';

								if($value->stock_product < 50){

									$colorStock = "bg-maroon";
								}

								if($value->stock_product >= 50 && $value->stock_product < 100){

									$colorStock = "bg-indigo";
								}

								if($value->stock_product >= 100){

									$colorStock = "bg-teal";
								}

								$htmlProducts .= '<div class="card-text small h6 badge badge-default pb-0 '.$colorStock .'" style="font-size:10px; padding-top:6px">
									
									'.$value->stock_product.'

								</div>';

								$url = "purchases?linkTo=id_product_purchase&equalTo=".$value->id_product."&select=cost_purchase";

								$price = CurlController::request($url,$method,$fields);

								if($price->status == 200){

									$price = $price->results[0]->cost_purchase;

									if($value->discount_product > 0){

										$discount = $price-($price*($value->discount_product/100));
									}

								}else{

									$price = 0;
								}

								if ($value->discount_product > 0){

									$htmlProducts .= '<span class="small ms-auto pe-1 h6 mt-1 text-red font-weight-bold" style="font-size:12px"><s>Bs '.number_format($price,2).'</s></span>


									<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs '.number_format($discount,2).'</strong></div>';

								}else{

									$htmlProducts .= '<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs '.number_format($price,2).'</strong></div>';

								}

							$htmlProducts .= '</div>

						</div>

					</div>
				</div>';
				
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

		$url = "relations?rel=purchases,products&type=purchase,product&linkTo=id_product&equalTo=".$this->idProduct;
		$method = "GET";
		$fields = array();

		$getProduct = CurlController::request($url,$method,$fields);

		if($getProduct->status == 200){

			$product = $getProduct->results[0];

			// Obtener el rol del vendedor y calcular el stock correspondiente
			$db = LocalConnection::connect();
			$stmtAdmin = $db->prepare("SELECT rol_admin FROM admins WHERE id_admin = :id LIMIT 1");
			$stmtAdmin->execute([':id' => $this->seller]);
			$role = $stmtAdmin->fetchColumn() ?: null;

			$stock = 0;
			if ($role !== null && $role != "superadmin" && $role != "admin" && $role != "despachador") {
				// Consultar stock en sub-almacén
				$stmtStock = $db->prepare("
					SELECT (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
							COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
					FROM warehouse_assignments wa
					JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
					WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office AND wa.id_product_assignment = :product
				");
				$stmtStock->execute([
					':admin' => $this->seller,
					':office' => $this->idOffice,
					':product' => $this->idProduct
				]);
				$stock = (int)($stmtStock->fetchColumn() ?: 0);
			} else {
				$stock = (int)$product->stock_product;
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

		$url = "sales?linkTo=id_order_sale&equalTo=".$this->idOrder."&select=id_sale,id_product_sale,qty_sale,discount_sale";
		$method = "GET";
		$fields = array();

		$getSales = CurlController::request($url,$method,$fields);

		if(isset($getSales->status) && $getSales->status == 200){

			foreach ($getSales->results as $key => $sale) {
				
				$urlProduct = "purchases?linkTo=id_product_purchase&equalTo=".$sale->id_product_sale."&select=cost_purchase,may_product";
				$getProduct = CurlController::request($urlProduct,$method,$fields);

				if(isset($getProduct->status) && $getProduct->status == 200){
					
					$product = $getProduct->results[0];
					$selling_price = ($this->isWholesale == 1 && !empty($product->may_product) && $sale->discount_sale <= 0) ? $product->may_product : $product->cost_purchase;

					$urlUpdate = "sales?id=".$sale->id_sale."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
					$methodUpdate = "PUT";
					$fieldsUpdate = array(
						"subtotal_sale" => round($selling_price, 2)
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

	public function overridePrice(){

		/*=============================================
		Actualizar Venta con nuevo precio
		=============================================*/

		$url = "sales?id=".$this->idSaleOverride."&nameId=id_sale&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = array(
			"subtotal_sale" => round($this->newPriceOverride, 2),
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
Actualizar Stock Laboratorio
=============================================*/
if(isset($_POST["updateLabStock"])){
	// require_once removed
	$id_raw_material = $_POST["id_raw_material"];
	$qty = $_POST["qty"];

	$db = LocalConnection::connect();
	$stmt = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty WHERE id_raw_material = :id");
	$stmt->bindParam(":qty", $qty);
	$stmt->bindParam(":id", $id_raw_material);
	
	if($stmt->execute()){
		echo "ok";
	}else{
		echo "error";
	}
}

/*=============================================
Actualizar Stock Laboratorio
=============================================*/
if(isset($_POST["updateLabStock"])){
	// require_once removed
	$id_raw_material = $_POST["id_raw_material"];
	$qty = $_POST["qty"];

	$db = LocalConnection::connect();
	$stmt = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty WHERE id_raw_material = :id");
	$stmt->bindParam(":qty", $qty);
	$stmt->bindParam(":id", $id_raw_material);
	
	if($stmt->execute()){
		echo "ok";
	}else{
		echo "error";
	}
}

/*=============================================
Actualizar orden

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
Actualizar Stock Laboratorio
=============================================*/
if(isset($_POST["updateLabStock"])){
	// require_once removed
	$id_raw_material = $_POST["id_raw_material"];
	$qty = $_POST["qty"];

	$db = LocalConnection::connect();
	$stmt = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material + :qty WHERE id_raw_material = :id");
	$stmt->bindParam(":qty", $qty);
	$stmt->bindParam(":id", $id_raw_material);
	
	if($stmt->execute()){
		echo "ok";
	}else{
		echo "error";
	}
}

/*=============================================
Proxy API Genérico
=============================================*/
if(isset($_POST["apiProxy"])){
	$url = $_POST["url"];
	$method = $_POST["method"];
	$fields = json_decode($_POST["fields"], true);
	
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

		$name_product = $_POST['name_product'];
		$batch_size = (float)$_POST['batch_size'];
		$unit_batch = $_POST['unit_batch'];
		$id_office = $_POST['id_office'];
		$id_admin = $_POST['id_admin'];

		// 1. Crear producto (a granel, is_compound_product=1)
		$stmtProd = $db->prepare("INSERT INTO products (title_product, unit_product, id_office_product, is_compound_product, status_product, stock_product, rte_product) VALUES (:name, :unit, :office, 1, 1, '0', '0')");
		$stmtProd->execute([
			':name' => $name_product,
			':unit' => $unit_batch,
			':office' => $id_office
		]);
		$id_product = $db->lastInsertId();

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
	$pkg_final_name = $_POST['pkg_final_name'] ?? '';
    $pkg_envase_type = $_POST['pkg_envase_type'] ?? 'und';
	$id_office = $_POST['id_office'] ?? 1; // Default or taken from session
	
	try {
		$db->beginTransaction();

		// 1. Obtener y validar stock de ingredientes
		$stmtIng = $db->prepare("SELECT id_raw_material_ingredient, qty_ingredient FROM recipe_ingredients WHERE id_recipe_ingredient = :id_recipe");
		$stmtIng->execute([':id_recipe' => $id_recipe]);
		$ingredients = $stmtIng->fetchAll(PDO::FETCH_ASSOC);
		
		$total_mp_cost = 0;
		$costs_snapshot = [];

		foreach($ingredients as $ing) {
			$id_raw = $ing['id_raw_material_ingredient'];
			$qty_needed = $ing['qty_ingredient'] * $batches;

			// Verificar stock y nombre para error
			$stmtCheck = $db->prepare("SELECT name_raw_material, stock_raw_material FROM raw_materials WHERE id_raw_material = :id");
			$stmtCheck->execute([':id' => $id_raw]);
			$mp_info = $stmtCheck->fetch(PDO::FETCH_ASSOC);

			if($mp_info['stock_raw_material'] < $qty_needed) {
				echo "stock_insuficiente|" . $mp_info['name_raw_material'];
				$db->rollBack();
				exit;
			}

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

			// Descontar stock
			$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
			$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
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
		// We will update the production record to show the final_qty packaged, and we could also change the name in the recipe or product but we leave id_product as is, and just update the DB with the new final product.
		$unit_cost_final = $pkg_final_qty > 0 ? ($total_production_cost / $pkg_final_qty) : 0;
		
		// Retrasamos este update para tener el id_packaged_product
		$updateProdData = [
			':cost' => $total_production_cost, 
			':mo_cost' => $total_mo_cost, 
			':cif_cost' => $total_cif_cost,
			':pkg_mo' => $extra_mo,
			':pkg_cif' => $extra_cif,
			':final_qty' => $pkg_final_qty,
			':unit_cost' => $unit_cost_final,
			':id' => $id_production
		];

		// 5. Inventario de Productos Finales (is_compound_product = 1)
		if($pkg_final_name && $pkg_final_qty > 0) {
			// Buscar si existe el producto por nombre en esa sucursal
			$stmtFind = $db->prepare("SELECT id_product, stock_product, rte_product FROM products WHERE title_product = :name AND id_office_product = :office LIMIT 1");
			$stmtFind->execute([':name' => $pkg_final_name, ':office' => $id_office]);
			$existing_product = $stmtFind->fetch(PDO::FETCH_ASSOC);

			if($existing_product) {
				// Actualizar stock y recalcular precio promedio ponderado
				$old_stock = (float)$existing_product['stock_product'];
				$old_rte = (float)$existing_product['rte_product'];
				$new_stock = $old_stock + $pkg_final_qty;
				$new_rte = (($old_stock * $old_rte) + ($pkg_final_qty * $unit_cost_final)) / $new_stock;

				$stmtUpdProd = $db->prepare("UPDATE products SET stock_product = :stock, rte_product = :rte, unit_product = :unit WHERE id_product = :id");
				$stmtUpdProd->execute([':stock' => $new_stock, ':rte' => $new_rte, ':unit' => $pkg_envase_type, ':id' => $existing_product['id_product']]);
			} else {
				// Insertar nuevo producto final
				$stmtInsProd = $db->prepare("INSERT INTO products (title_product, unit_product, stock_product, rte_product, is_compound_product, id_office_product, status_product) VALUES (:name, 'und', :stock, :rte, 1, :office, 1)");
				$stmtInsProd->execute([
					':name' => $pkg_final_name,
					':unit' => $pkg_envase_type,
					':stock' => $pkg_final_qty,
					':rte' => $unit_cost_final,
					':office' => $id_office
				]);
			}
		}

		// 5. Incrementar stock del producto final
		// Determinar cuantas unidades rinde la receta
		$stmtRend = $db->prepare("SELECT batch_size_recipe FROM recipes WHERE id_recipe = :id_recipe");
		$stmtRend->execute([':id_recipe' => $id_recipe]);
		$batch_size = (float)$stmtRend->fetchColumn();
		$unidades_finales = $batch_size * $batches;

		// Y el precio de costo del producto en el catálogo puede actualizarse
		$unit_cost_final = $total_production_cost / $unidades_finales;

		$stmtUpdProd = $db->prepare("UPDATE products SET stock_product = stock_product + :qty, rte_product = :cost WHERE id_product = :id_product");
		$stmtUpdProd->execute([':qty' => $unidades_finales, ':cost' => $unit_cost_final, ':id_product' => $id_product]);

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
    $stmt = $db->prepare("UPDATE productions SET status_production = 'en_proceso', start_date_production = NOW() WHERE id_production = :id");
    if($stmt->execute([':id' => $id])){
        echo "ok";
    }else{
        echo "error";
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
    $stmtProd = $db->prepare("SELECT p.*, prod.title_product, prod.unit_product 
        FROM productions p 
        JOIN products prod ON p.id_product_production = prod.id_product 
        WHERE id_production = :id");
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

        // Obtener ID del producto asociado
        $stmtGetProd = $db->prepare("SELECT id_product_recipe FROM recipes WHERE id_recipe = :id");
        $stmtGetProd->execute([':id' => $id_recipe]);
        $id_product = $stmtGetProd->fetchColumn();

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
                    ':id_raw' => $ing['id'],
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

        $stmtDelIng = $db->prepare("DELETE FROM recipe_ingredients WHERE id_recipe_ingredient = :id");
        $stmtDelIng->execute([':id' => $id_recipe]);

        $stmtDelLab = $db->prepare("DELETE FROM recipe_labor WHERE id_recipe_labor = :id");
        $stmtDelLab->execute([':id' => $id_recipe]);

        $stmtDelRec = $db->prepare("DELETE FROM recipes WHERE id_recipe = :id");
        $stmtDelRec->execute([':id' => $id_recipe]);

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
    $id_packaged_product = $_POST["id_packaged_product"];
    $stmt = $db->prepare("SELECT id_production, total_qty_production, real_unit_cost, real_total_cost, date_updated_production FROM productions WHERE id_packaged_product = :id AND status_production = 'completado' ORDER BY date_updated_production DESC");
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
		// Return main warehouse stock (available stock = stock_product - total_assigned)
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   (p.stock_product - COALESCE(sub.total_assigned, 0)) as stock
			FROM products p
			LEFT JOIN (
				SELECT wa.id_product_assignment,
					   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment 
								WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment 
								ELSE 0 END) as total_assigned
				FROM warehouse_assignments wa
				JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
				WHERE sw.id_office_sub_warehouse = :office
				GROUP BY wa.id_product_assignment
			) sub ON p.id_product = sub.id_product_assignment
			WHERE p.id_office_product = :office AND p.status_product = 1
		");
		$stmt->execute([':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else {
		// Ensure sub-warehouse exists
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_admin_sub_warehouse = :admin AND id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':admin' => $id_admin, ':office' => $id_office]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		if (!$sub) {
			$stmtName = $db->prepare("SELECT name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
			$stmtName->execute([':admin' => $id_admin]);
			$admName = $stmtName->fetchColumn() ?: "Usuario";
			$subName = "Sub-Almacén de " . $admName;
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (:admin, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':admin' => $id_admin, ':office' => $id_office, ':name' => $subName]);
		}

		// Return sub-warehouse stock
		$stmt = $db->prepare("
			SELECT p.id_product, p.title_product, p.sku_product, p.unit_product,
				   (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
					COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
			FROM warehouse_assignments wa
			JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
			JOIN products p ON wa.id_product_assignment = p.id_product
			WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
			GROUP BY wa.id_product_assignment
		");
		$stmt->execute([':admin' => $id_admin, ':office' => $id_office]);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
		WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':admin' => $id_admin, ':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET ASSIGNED BY OFFICE
//=====================================
if (isset($_POST["getAssignedByOffice"])) {
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
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
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET SUB WAREHOUSES DETAIL
//=====================================
if (isset($_POST["getSubWarehousesDetail"])) {
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	
	$stmtSw = $db->prepare("
		SELECT sw.id_sub_warehouse, sw.name_sub_warehouse, a.name_admin
		FROM sub_warehouses sw
		JOIN admins a ON sw.id_admin_sub_warehouse = a.id_admin
		WHERE sw.id_office_sub_warehouse = :office
	");
	$stmtSw->execute([':office' => $id_office]);
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
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT wa.date_created_assignment, wa.type_assignment, p.title_product, wa.qty_assignment, wa.notes_assignment,
			   dest_a.name_admin as name_admin,
			   disp_a.name_admin as dispatcher_name
		FROM warehouse_assignments wa
		JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
		JOIN products p ON wa.id_product_assignment = p.id_product
		JOIN admins dest_a ON sw.id_admin_sub_warehouse = dest_a.id_admin
		LEFT JOIN admins disp_a ON wa.id_dispatched_by = disp_a.id_admin
		WHERE sw.id_office_sub_warehouse = :office
		ORDER BY wa.id_assignment DESC
	");
	$stmt->execute([':office' => $id_office]);
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
		
		// Find or create sub-warehouse for dest admin
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_admin_sub_warehouse = :admin AND id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':admin' => $id_admin_dest, ':office' => $id_office]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$stmtName = $db->prepare("SELECT name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
			$stmtName->execute([':admin' => $id_admin_dest]);
			$admName = $stmtName->fetchColumn() ?: "Usuario";
			$subName = "Sub-Almacén de " . $admName;
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (:admin, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':admin' => $id_admin_dest, ':office' => $id_office, ':name' => $subName]);
			$id_sub = $db->lastInsertId();
		} else {
			$id_sub = $sub['id_sub_warehouse'];
		}
		
		// Insert assignment
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
		
		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
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
	$db = LocalConnection::connect();

	$stmt = $db->prepare("
		INSERT INTO inventory_requests (id_admin_request, id_office_request, id_product_request, qty_request, status_request, notes_request, date_created_request)
		VALUES (:admin, :office, :id_prod, :qty, 'pendiente', :notes, NOW())
	");
	if ($stmt->execute([
		':admin' => $id_admin,
		':office' => $id_office,
		':id_prod' => $id_product,
		':qty' => $qty,
		':notes' => $notes
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
		SELECT ir.date_created_request, p.title_product, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request
		FROM inventory_requests ir
		JOIN products p ON ir.id_product_request = p.id_product
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
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT ir.id_request, ir.date_created_request, ir.qty_request, ir.notes_request,
			   a.name_admin, p.title_product,
			   (p.stock_product - COALESCE(sub.total_assigned, 0)) as available_stock
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		LEFT JOIN (
			SELECT wa.id_product_assignment,
				   SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment 
							WHEN wa.type_assignment IN ('devolucion', 'venta') THEN -wa.qty_assignment 
							ELSE 0 END) as total_assigned
			FROM warehouse_assignments wa
			JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
			WHERE sw.id_office_sub_warehouse = :office
			GROUP BY wa.id_product_assignment
		) sub ON p.id_product = sub.id_product_assignment
		WHERE ir.id_office_request = :office AND ir.status_request = 'pendiente'
		ORDER BY ir.id_request DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// GET REQUEST HISTORY
//=====================================
if (isset($_POST["getRequestHistory"])) {
	$id_office = $_POST["id_office"];
	$db = LocalConnection::connect();
	$stmt = $db->prepare("
		SELECT ir.date_created_request, ir.qty_request, ir.qty_dispatched_request, ir.status_request, ir.notes_dispatcher_request, ir.notes_request,
			   a.name_admin, p.title_product
		FROM inventory_requests ir
		JOIN admins a ON ir.id_admin_request = a.id_admin
		JOIN products p ON ir.id_product_request = p.id_product
		WHERE ir.id_office_request = :office AND ir.status_request != 'pendiente'
		ORDER BY ir.id_request DESC
	");
	$stmt->execute([':office' => $id_office]);
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
		$stmtReq = $db->prepare("SELECT id_admin_request, id_product_request FROM inventory_requests WHERE id_request = :id LIMIT 1");
		$stmtReq->execute([':id' => $id_request]);
		$req = $stmtReq->fetch(PDO::FETCH_ASSOC);
		if (!$req) {
			throw new Exception("Solicitud no encontrada");
		}
		
		$id_admin_dest = $req['id_admin_request'];
		$id_product = $req['id_product_request'];
		
		// Find or create sub-warehouse for dest admin
		$stmtCheck = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_admin_sub_warehouse = :admin AND id_office_sub_warehouse = :office LIMIT 1");
		$stmtCheck->execute([':admin' => $id_admin_dest, ':office' => $id_office]);
		$sub = $stmtCheck->fetch(PDO::FETCH_ASSOC);
		
		if (!$sub) {
			$stmtName = $db->prepare("SELECT name_admin FROM admins WHERE id_admin = :admin LIMIT 1");
			$stmtName->execute([':admin' => $id_admin_dest]);
			$admName = $stmtName->fetchColumn() ?: "Usuario";
			$subName = "Sub-Almacén de " . $admName;
			$stmtIns = $db->prepare("INSERT INTO sub_warehouses (id_admin_sub_warehouse, id_office_sub_warehouse, name_sub_warehouse, status_sub_warehouse, date_created_sub_warehouse) VALUES (:admin, :office, :name, 1, CURDATE())");
			$stmtIns->execute([':admin' => $id_admin_dest, ':office' => $id_office, ':name' => $subName]);
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
		
		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
	}
	exit;
}