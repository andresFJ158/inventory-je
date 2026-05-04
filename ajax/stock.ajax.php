<?php

require_once "../controllers/curl.controller.php";

class StockAjax{

	public $id_office_admin;
	public $token_admin;

	public function updateStock(){

	    /*=============================================
	    Traer los productos de la sucursal (solo IDs)
	    =============================================*/
	    $url = "products?linkTo=id_office_product&equalTo=".$this->id_office_admin."&select=id_product";
	    $method = "GET";
	    $fields = array();

    	$productsStock = CurlController::request($url,$method,$fields);
    
    	if(isset($productsStock->status) && $productsStock->status == 200 && !empty($productsStock->results)){

        	$countStockProducts = 0;
        	$countTotalStock    = 0;
        	$arrayStock         = []; // id_product => ['stock' => X, 'status' => 0/1]

        	foreach ($productsStock->results as $key => $prod) {

	            $idProduct = (int)$prod->id_product;

	            /*=============================================
	            Traer total de compras
	            =============================================*/
	            $url = "purchases?linkTo=id_product_purchase&equalTo=".$idProduct."&select=qty_purchase";
	            $purchases = CurlController::request($url,$method,$fields);
	            
	            $totalPurchaseProduct = 0;
	            if(isset($purchases->status) && $purchases->status == 200 && !empty($purchases->results)){
	                foreach ($purchases->results as $item) {
	                    $totalPurchaseProduct += (int)$item->qty_purchase;
	                }
	            }

	            /*=============================================
	            Traer total de ventas
	            =============================================*/
	            $url = "sales?linkTo=id_product_sale&equalTo=".$idProduct."&select=qty_sale";
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

            	// status_product: >0 activo (1), <=0 inactivo (0)
            	$statusCalc = ($stockCalc > 0) ? 1 : 0;

            	$arrayStock[$idProduct] = [
            		'stock'  => $stockCalc,
            		'status' => $statusCalc
            	];

            	$countStockProducts++;

            	/*=============================================
            	Cuando termine el recorrido, actualiza en BD
            	==============================================*/
            	if($countStockProducts == count($productsStock->results)){

	                foreach ($arrayStock as $id => $vals) {

	                    $url    = "products?id=".$id."&nameId=id_product&token=".$this->token_admin."&table=admins&suffix=admin";
	                    $method = "PUT";
	                    $fields = array(
	                        "stock_product"  => $vals['stock'],
	                        "status_product" => $vals['status']
	                        // opcional: "date_updated_product" => date("Y-m-d H:i:s")
	                    );

	                    $fields = http_build_query($fields);
	                    $update = CurlController::request($url,$method,$fields);

	                    if(isset($update->status) && $update->status == 200){
	                    	$countTotalStock++;
	                    } else {
	                    	// Si alguna actualización falla, puedes manejar el error aquí si deseas
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
