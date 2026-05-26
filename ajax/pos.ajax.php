<?php

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

		$htmlProducts = "";

		if (!empty($products)){
		
			foreach ($products as $key => $value){

				$htmlProducts .= '<div class="col-12 col-lg-6 col-xl-4 p-2 btn addProductPos" idProduct="'.$value->id_product.'">
					
					<div class="card rounded border-0 position-relative">';

						if ($value->discount_product > 0){

							$htmlProducts .= '<div class="position-absolute small bg-red p-1 shadow-sm rounded" style="top:4px; left:4px; font-size:10px">'.$value->discount_product.'% OFF</div>';
							
						}
						
						$htmlProducts .= '<div class="position-absolute small bg-white p-1 shadow-sm rounded" style="top:4px; right:4px; font-size:10px">'.$value->sku_product.'</div>

						<img src="'.urldecode($value->img_product).'" class="card-img-top px-5 py-3 mx-auto" style="width:200px !important">

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

			if($product->stock_product == 0){

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

					$html = '<tr>
				
								<td>
									<div>
										<img src="'.urldecode($product->img_product).'" class="me-auto rounded mt-2 float-start"style="width:60px !important; height:60px !important">

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
											
											<span class="input-group-text rounded-start bg-light btnQty" type="btnMin" style="cursor:pointer" key="'.$product->id_product.'" stock="'.$product->stock_product.'">
												<i class="bi bi-dash-lg"></i>
											</span>

											<input type="number" class="form-control text-center showQuantity showQuantity_'.$product->id_product.'" value="1" key="'.$product->id_product.'" style="font-size:12px"stock="'.$product->stock_product.'">

											<span class="input-group-text rounded-end bg-light btnQty" type="btnMax" style="cursor:pointer" key="'.$product->id_product.'"stock="'.$product->stock_product.'">
												<i class="bi bi-plus-lg"></i>
											</span>

										</div>
									</div>
									
								</td>

								<td>
									<h6 class="text-center my-3 pricePurchase pricePurchase_'.$product->id_product.'" pricePurchase="'.$selling_price.'" originalPricePurchase="'.$selling_price.'">Bs '.number_format($selling_price,2).'</h6>
								</td>

								<td class="text-center">
									<button type="button" class="btn btn-sm rounded ms-1 mt-2 py-2 px-3 bg-red deleteSale deleteSale_'.$product->id_product.'" idSale="'.$createSale->results->lastId.'" taxSale="'.(explode("_", (isset($product->tax_product) && !empty($product->tax_product)) ? $product->tax_product : "0_0")[1] ?? "0").'" discountSale="'.$product->discount_product.'">
										<i class="bi bi-trash"></i>
									</button>
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
	$allowed_endpoints = ['raw_materials', 'raw_material_entries', 'recipes', 'productions'];
	$endpoint = explode('?', $url)[0];
	if(!in_array($endpoint, $allowed_endpoints)) {
		echo json_encode(["status" => 403, "results" => "Endpoint no permitido"]);
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
	$pkg_final_name = trim(htmlspecialchars($_POST['pkg_final_name'] ?? ''));
    $pkg_envase_type = trim(htmlspecialchars($_POST['pkg_envase_type'] ?? 'und'));
	$id_office = $_POST['id_office'] ?? 1; // Default or taken from session
	
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
			':id' => $id_production
		];
		$id_packaged_product = 0;

		// 5. Inventario de Productos Finales (is_compound_product = 1)
		if($pkg_final_name && $pkg_final_qty > 0) {
			// Buscar si existe el producto por nombre en esa sucursal
			$stmtFind = $db->prepare("SELECT id_product, stock_product, rte_product FROM products WHERE title_product = :name AND id_office_product = :office LIMIT 1");
			$stmtFind->execute([':name' => $pkg_final_name, ':office' => $id_office]);
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
Obtener Mano de Obra de Receta
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

	$stmtCheckStatus = $db->prepare("SELECT status_production, id_recipe_production, batches_production FROM productions WHERE id_production = :id");
	$stmtCheckStatus->execute([':id' => $id]);
	$prod = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);
	if(!$prod || $prod['status_production'] !== 'pendiente') {
		echo "error";
		exit;
	}

	$id_recipe = $prod['id_recipe_production'];
	$batches = (float)$prod['batches_production'];

	// Verificar stock de ingredientes antes de iniciar
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
			exit;
		}
	}

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
// GET PENDING QC
//=====================================
if(isset($_POST["getPendingQC"]) && $_POST["getPendingQC"] == "ok") {
	$db = LocalConnection::connect();
	$id_office = intval($_POST["id_office"]);
	$stmt = $db->prepare("
		SELECT p.id_production, p.total_qty_production, p.date_updated_production,
			   p.real_total_cost, p.real_unit_cost, p.id_packaged_product,
			   r.name_recipe, pr.title_product, pr.unit_product
		FROM productions p
		JOIN recipes r ON p.id_recipe_production = r.id_recipe
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE p.id_office_production = :office AND p.status_production = 'pendiente_qc'
		ORDER BY p.date_updated_production DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
	exit;
}

//=====================================
// SUBMIT QUALITY CHECK
//=====================================
if(isset($_POST["submitQualityCheck"]) && $_POST["submitQualityCheck"] == "ok") {
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
		$stmtProd = $db->prepare("UPDATE productions SET status_production = :status WHERE id_production = :id");
		$stmtProd->execute([':status' => $new_status, ':id' => $id_production]);

		// Solo ingresamos inventario de venta SI pasa el QC (aprobado o aprobado_con_obs)
		if ($qty_approved > 0 && $prod['id_packaged_product'] && $new_status === 'completado') {
			$stmtFind = $db->prepare("SELECT stock_product, rte_product FROM products WHERE id_product = :id");
            $stmtFind->execute([':id' => $prod['id_packaged_product']]);
            $pData = $stmtFind->fetch(PDO::FETCH_ASSOC);

            $old_stock = (float)$pData['stock_product'];
            $old_rte = (float)$pData['rte_product'];
            $unit_cost = (float)$prod['real_unit_cost'];
            
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
			   pr.title_product, pr.unit_product
		FROM quality_checks qc
		JOIN admins a ON qc.id_admin_qc = a.id_admin
		JOIN productions p ON qc.id_production_qc = p.id_production
		JOIN products pr ON p.id_product_production = pr.id_product
		WHERE qc.id_office_qc = :office
		ORDER BY qc.date_created_qc DESC
	");
	$stmt->execute([':office' => $id_office]);
	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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
