<?php

session_start();

require_once "../controllers/curl.controller.php";

class ReportsController{

	/*=============================================
	Exportar órdenes a Excel
	=============================================*/

	public function exportOrders(){
		
		// Filtros de fechas - Usar las fechas enviadas o valores por defecto
		$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
		$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
		
		$method = "GET";
		$fields = array();
		
		// URL base para órdenes con relaciones y filtro de fechas directamente en la API
		// Si las fechas son iguales, usar equalTo; si son diferentes, usar between
		if($startDate == $endDate){
			// Fecha específica: usar equalTo
			if(isset($_SESSION["admin"]) && $_SESSION["admin"]->id_office_admin == 0){
				// Administrador principal: todas las oficinas
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=date_created_order&equalTo=".$startDate."&orderBy=id_order&orderMode=DESC";
			}else if(isset($_SESSION["admin"])){
				// Usuario de oficina específica: filtrar por oficina Y fecha
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order,date_created_order&equalTo=".$_SESSION["admin"]->id_office_admin.",".$startDate."&orderBy=id_order&orderMode=DESC";
			}else{
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=date_created_order&equalTo=".$startDate."&orderBy=id_order&orderMode=DESC";
			}
		}else{
			// Rango de fechas: usar between
			if(isset($_SESSION["admin"]) && $_SESSION["admin"]->id_office_admin == 0){
				// Administrador principal: todas las oficinas
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
			}else if(isset($_SESSION["admin"])){
				// Usuario de oficina específica: filtrar por oficina Y fecha
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order&equalTo=".$_SESSION["admin"]->id_office_admin."&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
			}else{
				$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
			}
		}
		
		$orders = CurlController::request($ordersUrl, $method, $fields);
		
		if($orders->status == 200){
			$ordersData = $orders->results;
		}else{
			$ordersData = array();
		}
		
		// Configurar headers para descarga Excel
		header('Content-Type: application/vnd.ms-excel; charset=utf-8');
		header('Content-Disposition: attachment; filename="ordenes_'.$startDate.'_'.$endDate.'.xls"');
		header('Cache-Control: max-age=0');
		
		// BOM para UTF-8 (permite caracteres especiales en Excel)
		echo "\xEF\xBB\xBF";
		
		// Crear tabla HTML que Excel puede leer
		echo '<table border="1">';
		echo '<tr>';
		echo '<th>#</th>';
		echo '<th>Transacción</th>';
		echo '<th>Cliente</th>';
		echo '<th>Fecha</th>';
		echo '<th>Método de Pago</th>';
		echo '<th>Estado</th>';
		echo '<th>Subtotal</th>';
		echo '<th>Descuento</th>';
		echo '<th>Total</th>';
		echo '</tr>';
		
		foreach ($ordersData as $index => $orderInfo) {
			
			// Los datos ya vienen con relaciones desde la API
			echo '<tr>';
			echo '<td>'.($index + 1).'</td>';
			echo '<td>'.htmlspecialchars($orderInfo->transaction_order ?? 'N/A').'</td>';
			echo '<td>'.htmlspecialchars(($orderInfo->name_client ?? '') . ' ' . ($orderInfo->surname_client ?? '')).'</td>';
			echo '<td>'.htmlspecialchars($orderInfo->date_created_order ?? $orderInfo->date_order ?? 'N/A').'</td>';
			echo '<td>'.htmlspecialchars($orderInfo->method_order ?? 'N/A').'</td>';
			echo '<td>'.htmlspecialchars($orderInfo->status_order ?? 'N/A').'</td>';
			echo '<td>'.number_format($orderInfo->subtotal_order ?? 0, 2, '.', '').'</td>';
			echo '<td>'.number_format($orderInfo->discount_order ?? 0, 2, '.', '').'</td>';
			echo '<td>'.number_format($orderInfo->total_order ?? 0, 2, '.', '').'</td>';
			echo '</tr>';
		}
		
		echo '</table>';
		exit;
		
	}
	
	/*=============================================
	Exportar ventas a Excel
	=============================================*/

	public function exportSales(){

		$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
		$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
		
		$method = "GET";
		$fields = array();
		
		if($startDate == $endDate){
			// Fecha específica: usar equalTo
			if(isset($_SESSION["admin"]) && $_SESSION["admin"]->id_office_admin == 0){
				// Administrador principal: todas las ventas
				$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=date_created_sale&equalTo=".$startDate."&orderBy=id_sale&orderMode=DESC";
			}else if(isset($_SESSION["admin"])){
				// Usuario de oficina específica: filtrar por oficina Y fecha
				$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=id_office_sale,date_created_sale&equalTo=".$_SESSION["admin"]->id_office_admin.",".$startDate."&orderBy=id_sale&orderMode=DESC";
			}else{
				$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=date_created_sale&equalTo=".$startDate."&orderBy=id_sale&orderMode=DESC";
			}
		}else{
			// Rango de fechas: usar between
			if(isset($_SESSION["admin"]) && $_SESSION["admin"]->id_office_admin == 0){
				// Administrador principal: todas las ventas
				$salesUrl = "relations?rel=sales,products&type=sale,product&between1=date_created_sale&between2=".$startDate.",".$endDate."&orderBy=id_sale&orderMode=DESC";
			}else if(isset($_SESSION["admin"])){
				// Usuario de oficina específica: filtrar por oficina Y fecha
				$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=id_office_sale&equalTo=".$_SESSION["admin"]->id_office_admin."&between1=date_created_sale&between2=".$startDate.",".$endDate."&orderBy=id_sale&orderMode=DESC";
			}else{
				$salesUrl = "relations?rel=sales,products&type=sale,product&between1=date_created_sale&between2=".$startDate.",".$endDate."&orderBy=id_sale&orderMode=DESC";
			}
		}
		
		$sales = CurlController::request($salesUrl, $method, $fields);
		
		if($sales->status == 200){
			$salesData = $sales->results;
		}else{
			$salesData = array();
		}
		
		// Configurar headers para descarga Excel
		header('Content-Type: application/vnd.ms-excel; charset=utf-8');
		header('Content-Disposition: attachment; filename="ventas_'.$startDate.'_'.$endDate.'.xls"');
		header('Cache-Control: max-age=0');
		
		// BOM para UTF-8 (permite caracteres especiales en Excel)
		echo "\xEF\xBB\xBF";
		
		// Crear tabla HTML que Excel puede leer
		echo '<table border="1">';
		echo '<tr>';
		echo '<th>#</th>';
		echo '<th>Producto</th>';
		echo '<th>Cantidad</th>';
		echo '<th>Precio Unitario</th>';
		echo '<th>IVA (%)</th>';
		echo '<th>Descuento (%)</th>';
		echo '<th>Subtotal</th>';
		echo '<th>Estado</th>';
		echo '<th>Fecha</th>';
		echo '</tr>';
		
		foreach ($salesData as $index => $saleInfo) {
			
			echo '<tr>';
			echo '<td>'.($index + 1).'</td>';
			echo '<td>'.htmlspecialchars(urldecode($saleInfo->title_product ?? 'N/A')).'</td>';
			echo '<td>'.htmlspecialchars($saleInfo->qty_sale ?? '0').'</td>';
			echo '<td>'.number_format($saleInfo->price_sale ?? 0, 2, '.', '').'</td>';
			echo '<td>'.htmlspecialchars($saleInfo->tax_sale ?? '0').'%</td>';
			echo '<td>'.htmlspecialchars($saleInfo->discount_sale ?? '0').'%</td>';
			echo '<td>'.number_format($saleInfo->subtotal_sale ?? 0, 2, '.', '').'</td>';
			echo '<td>'.htmlspecialchars($saleInfo->status_sale ?? 'N/A').'</td>';
			echo '<td>'.htmlspecialchars($saleInfo->date_created_sale ?? 'N/A').'</td>';
			echo '</tr>';
		}
		
		echo '</table>';
		exit;
		
	}

}

/*=============================================
Ejecutar acción solicitada
=============================================*/

if(isset($_GET["action"])){
	
	$reports = new ReportsController();
	
	if($_GET["action"] == "export_orders"){
		
		$reports->exportOrders();
		
	}else if($_GET["action"] == "export_sales"){
		
		$reports->exportSales();
		
	}
	
}

?>