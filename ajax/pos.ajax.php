<?php

require_once "../controllers/curl.controller.php";
require_once "../controllers/template.controller.php";

date_default_timezone_set("America/La_Paz");

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
	require_once "../api.pos/models/connection.php";
	$id_raw_material = $_POST["id_raw_material"];
	$qty = $_POST["qty"];

	$db = Connection::connect();
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
	require_once "../api.pos/models/connection.php";
	$id_raw_material = $_POST["id_raw_material"];
	$qty = $_POST["qty"];

	$db = Connection::connect();
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
		// 5. Insertar CIFs
		$cifs = json_decode($_POST['cifs'], true);
		if (!empty($cifs)) {
			$stmtCif = $db->prepare("INSERT INTO recipe_indirect_costs (id_recipe_indirect_recipe, id_type_indirect, amount_per_batch_indirect, date_created_indirect) VALUES (:id_rec, :id_type, :amt, NOW())");
			foreach($cifs as $cif) {
				$stmtCif->execute([
					':id_rec' => $id_recipe,
					':id_type' => $cif['id_type'],
					':amt' => $cif['amount']
				]);
			}
		}

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error: " . $e->getMessage();
	}
}

/*=============================================
Completar Producción (Laboratorio)
=============================================*/
if(isset($_POST["completeProduction"])){
	require_once "../api.pos/models/connection.php";
	$db = Connection::connect();
	
	$id_production = $_POST['id_production'];
	$id_recipe = $_POST['id_recipe'];
	$batches = (float)$_POST['batches'];
	$id_product = $_POST['id_product'];
	
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
			$stmtPrice = $db->prepare("SELECT unit_price_entry FROM raw_material_entries WHERE id_raw_material_entry = :id AND status_entry = 'aprobado' ORDER BY id_entry DESC LIMIT 1");
			$stmtPrice->execute([':id' => $id_raw]);
			$price_info = $stmtPrice->fetch(PDO::FETCH_ASSOC);
			$unit_price = $price_info ? (float)$price_info['unit_price_entry'] : 0;

			$subtotal = $unit_price * $qty_needed;
			$total_mp_cost += $subtotal;

			// Guardar snapshot para luego
			$costs_snapshot[] = [
				'id_raw' => $id_raw,
				'qty' => $qty_needed,
				'price' => $unit_price,
				'subtotal' => $subtotal
			];

			// Descontar stock
			$stmtUpdMP = $db->prepare("UPDATE raw_materials SET stock_raw_material = stock_raw_material - :qty WHERE id_raw_material = :id");
			$stmtUpdMP->execute([':qty' => $qty_needed, ':id' => $id_raw]);
		}

		// 2. Calcular otros costos (Mano de obra y CIF)
		$total_mo_cost = 0;
		$stmtLab = $db->prepare("SELECT total_cost_labor FROM recipe_labor WHERE id_recipe_labor = :id_recipe");
		$stmtLab->execute([':id_recipe' => $id_recipe]);
		while($lab = $stmtLab->fetch(PDO::FETCH_ASSOC)) {
			$total_mo_cost += ($lab['total_cost_labor'] * $batches);
		}

		$total_cif_cost = 0;
		$stmtCif = $db->prepare("SELECT amount_per_batch_indirect FROM recipe_indirect_costs WHERE id_recipe_indirect_recipe = :id_recipe");
		$stmtCif->execute([':id_recipe' => $id_recipe]);
		while($cif = $stmtCif->fetch(PDO::FETCH_ASSOC)) {
			$total_cif_cost += ($cif['amount_per_batch_indirect'] * $batches);
		}

		$total_production_cost = $total_mp_cost + $total_mo_cost + $total_cif_cost;

		// 3. Registrar snapshot en production_material_costs
		$stmtSnap = $db->prepare("INSERT INTO production_material_costs (id_production_cost, id_raw_material_cost, qty_used_cost, unit_price_snapshot, total_cost_snapshot, date_created_cost) VALUES (:id_prod, :id_raw, :qty, :price, :sub, NOW())");
		foreach($costs_snapshot as $snap) {
			$stmtSnap->execute([
				':id_prod' => $id_production,
				':id_raw' => $snap['id_raw'],
				':qty' => $snap['qty'],
				':price' => $snap['price'],
				':sub' => $snap['subtotal']
			]);
		}

		// 4. Actualizar Producción (Estado y Costo)
		$stmtProd = $db->prepare("UPDATE productions SET status_production = 'completado', total_cost_production = :cost WHERE id_production = :id");
		$stmtProd->execute([':cost' => $total_production_cost, ':id' => $id_production]);

		// 5. Incrementar stock del producto final
		// Determinar cuantas unidades rinde la receta
		$stmtRend = $db->prepare("SELECT batch_size_recipe FROM recipes WHERE id_recipe = :id_recipe");
		$stmtRend->execute([':id_recipe' => $id_recipe]);
		$batch_size = (float)$stmtRend->fetchColumn();
		$unidades_finales = $batch_size * $batches;

		// Y el precio de costo del producto en el catálogo puede actualizarse
		$unit_cost_final = $total_production_cost / $unidades_finales;

		$stmtUpdProd = $db->prepare("UPDATE products SET stock_product = stock_product + :qty, cost_product = :cost WHERE id_product = :id_product");
		$stmtUpdProd->execute([':qty' => $unidades_finales, ':cost' => $unit_cost_final, ':id_product' => $id_product]);

		$db->commit();
		echo "ok";
	} catch (Exception $e) {
		$db->rollBack();
		echo "error|" . $e->getMessage();
	}
}

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

