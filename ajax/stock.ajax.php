<?php

require_once "../controllers/curl.controller.php";

class StockAjax{

	public $id_office_admin;
	public $token_admin;

	public function updateStock(){

	    /*=============================================
	    Traer los registros de product_inventory para la sucursal
	    =============================================*/
	    $url = "product_inventory?linkTo=id_office_inventory&equalTo=".$this->id_office_admin."&select=id_inventory,id_product_inventory";
	    $method = "GET";
	    $fields = array();

    	$inventoryRows = CurlController::request($url,$method,$fields);
    
    	if(isset($inventoryRows->status) && $inventoryRows->status == 200 && !empty($inventoryRows->results)){

        	$countStockProducts = 0;
        	$countTotalStock    = 0;
        	$arrayStock         = []; // id_inventory => ['id_product' => X, 'stock' => X, 'status' => 0/1]

        	$warehouseIds = [];
        	$urlWH = "warehouses?linkTo=id_office_warehouse&equalTo=".$this->id_office_admin."&select=id_warehouse";
        	$respWH = CurlController::request($urlWH, "GET", []);
        	if (isset($respWH->status) && $respWH->status == 200 && !empty($respWH->results)) {
        	    foreach ($respWH->results as $wh) {
        	        $warehouseIds[] = (int)$wh->id_warehouse;
        	    }
        	}

        	foreach ($inventoryRows->results as $key => $inv) {

	            $idProduct   = (int)$inv->id_product_inventory;
	            $idInventory = (int)$inv->id_inventory;

	            /*=============================================
	            Traer total de compras de ese producto en esa sucursal (por almacenes)
	            =============================================*/
	            $url = "purchases?linkTo=id_product_purchase&equalTo=".$idProduct."&select=qty_purchase,id_office_purchase";
	            $purchases = CurlController::request($url,$method,$fields);
	            
	            $totalPurchaseProduct = 0;
	            if(isset($purchases->status) && $purchases->status == 200 && !empty($purchases->results)){
	                foreach ($purchases->results as $item) {
	                    if (in_array((int)$item->id_office_purchase, $warehouseIds)) {
	                        $totalPurchaseProduct += (int)$item->qty_purchase;
	                    }
	                }
	            }

	            /*=============================================
	            Traer total de ventas completadas de ese producto en esa sucursal
	            =============================================*/
	            $url = "sales?linkTo=id_product_sale,id_office_sale,status_sale&equalTo=".$idProduct.",".$this->id_office_admin.",Completada&select=qty_sale";
	            $sales = CurlController::request($url,$method,$fields);

             	$totalSaleProduct = 0;
	            if(isset($sales->status) && $sales->status == 200 && !empty($sales->results)){
	                foreach ($sales->results as $item) {
	                    $totalSaleProduct += (int)$item->qty_sale;
	                }
	            }

	            /*=============================================
	            Stock = Compras - Ventas
	            =============================================*/
            	$stockCalc = (int)($totalPurchaseProduct - $totalSaleProduct);

            	// status_inventory: >0 activo (1), <=0 inactivo (0)
            	$statusCalc = ($stockCalc > 0) ? 1 : 0;

            	$arrayStock[$idInventory] = [
            		'id_product' => $idProduct,
            		'stock'      => $stockCalc,
            		'status'     => $statusCalc
            	];

            	$countStockProducts++;

            	/*=============================================
            	Cuando termine el recorrido, actualiza en BD
            	==============================================*/
            	if($countStockProducts == count($inventoryRows->results)){

	                foreach ($arrayStock as $idInv => $vals) {

	                    // Actualizar product_inventory (fuente principal)
	                    $url    = "product_inventory?id=".$idInv."&nameId=id_inventory&token=".$this->token_admin."&table=admins&suffix=admin";
	                    $method = "PUT";
	                    $fields = array(
	                        "stock_inventory"  => $vals['stock'],
	                        "status_inventory" => $vals['status']
	                    );
	                    $fields = http_build_query($fields);
	                    CurlController::request($url,$method,$fields);

	                    // Actualizar products.stock_product como suma global (compatibilidad)
	                    $method = "GET";
	                    $fields = array();
	                    $urlTotalStock = "product_inventory?linkTo=id_product_inventory&equalTo=".$vals['id_product']."&select=stock_inventory";
	                    $allOfficeStocks = CurlController::request($urlTotalStock,$method,$fields);
	                    $globalStock = 0;
	                    if(isset($allOfficeStocks->status) && $allOfficeStocks->status == 200){
	                        foreach($allOfficeStocks->results as $s){
	                            $globalStock += (float)$s->stock_inventory;
	                        }
	                    }
	                    $urlProd    = "products?id=".$vals['id_product']."&nameId=id_product&token=".$this->token_admin."&table=admins&suffix=admin";
	                    $method     = "PUT";
	                    $fieldsProd = "stock_product=".rawurlencode($globalStock);
	                    $updProd    = CurlController::request($urlProd,$method,$fieldsProd);

	                    if(isset($updProd->status) && $updProd->status == 200){
	                    	$countTotalStock++;
	                    }
                	}

                	// Si todas las actualizaciones respondieron 200
                	if($countTotalStock == count($arrayStock)){
                		echo 200;
                	}
             	}
        	}
    	}
	}
}

if(isset($_POST["id_office_admin"])){

	$ajax = new StockAjax();
	$ajax->id_office_admin = $_POST["id_office_admin"];
	$ajax->token_admin     = $_POST["token_admin"];
	$ajax->updateStock();
}
