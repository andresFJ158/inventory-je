<?php

use Dompdf\Dompdf;
use Dompdf\Options;

// Si se solicita un PDF de una orden específica
if(isset($_GET["id_order"])){

	ob_start();

// Configuración para imágenes remotas y otras opciones
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

/*=============================================
Traer info de la orden
=============================================*/

$url = "relations?rel=orders,clients,admins,offices&type=order,client,admin,office&linkTo=id_order&equalTo=".base64_decode($_GET["id_order"]);
$method = "GET";
$fields = array();

$getOrder = CurlController::request($url,$method,$fields);

if($getOrder->status == 200){

	$order = $getOrder->results[0];
	$order->products = [];

	/*=============================================
	Agregarle los productos a la orden
	=============================================*/

	$url = "relations?rel=sales,products&type=sale,product&linkTo=id_order_sale&equalTo=".base64_decode($_GET["id_order"]);
	$method = "GET";
	$fields = array();

	$getProducts = CurlController::request($url,$method,$fields);

	if($getProducts->status == 200){

		$products = $getProducts->results;

		foreach ($products as $key => $value) {
			
			array_push($order->products, $value);
			
		}
	}

	// echo '<pre>$order '; print_r($order); echo '</pre>';

}

?>

<!-- <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; }
        h1 { color: #007BFF; }
        p { font-size: 14px; }
    </style>
</head>
<body>
    <h1>Factura de Compra</h1>
    <p>Gracias por tu compra. Este es tu comprobante en PDF.</p>
</body>
</html> -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Transacción #<?= $order->transaction_order ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        h1 { color: #611be4; }
        .section { margin-bottom: 20px; }
        .small { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f0f0f0; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

<h1>Transacción #<?= $order->transaction_order ?></h1>

<div class="section">
    <strong>Sucursal:</strong> <?= $order->title_office ?><br>
    Dirección: <?= $order->address_office ?><br>
    Tel: <?= $order->phone_office ?><br>
    NIT: <?= $order->dni_office ?>
</div>

<div class="section">
    <strong>Cliente:</strong> <?= $order->name_client . ' ' . $order->surname_client ?><br>
    Tel: <?= $order->phone_client ?><br>
    Email: <?= $order->email_client ?><br>
    Dirección: <?= str_replace("+", " ", $order->address_client) ?>
</div>

<div class="section">
    <strong>Fecha:</strong> <?= $order->date_order ?><br>
    <strong>Método de pago:</strong> <?= $order->method_order ?><br>
    <strong>Estado:</strong> <?= $order->status_order ?>
</div>

<div class="section">
    <strong>Detalle de productos:</strong><br><br>
    <table>
        <thead>
            <tr>
                <th>Nombre Producto</th>
                <th>Cantidad</th>
                <th>IVA (%)</th>
                <th>Descuento (%)</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->products as $p): ?>
            <tr>
                <td><?= urldecode($p->title_product) ?></td>
                <td><?= $p->qty_sale ?></td>
                <td><?= $p->tax_sale ?>%</td>
                <td><?= $p->discount_sale ?>%</td>
                <td class="right">Bs<?= number_format($p->subtotal_sale, 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="section">
    <table>
        <tr>
            <td class="right bold">Subtotal:</td>
            <td class="right">Bs<?= number_format($order->subtotal_order, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="right bold">Descuento total:</td>
            <td class="right">- Bs<?= number_format($order->discount_order, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="right bold">Impuestos:</td>
            <td class="right">Bs<?= number_format($order->tax_order, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="right bold">Total a pagar:</td>
            <td class="right bold">Bs<?= number_format($order->total_order, 0, ',', '.') ?></td>
        </tr>
    </table>
</div>

</body>
</html>

<?php

$html = ob_get_clean();

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Limpia el búfer de salida y establece el tipo de contenido
ob_clean();
header("Content-Type: application/pdf");

// Envía el archivo al navegador sin forzar la descarga
$dompdf->stream("archivo_generado.pdf", ["Attachment" => false]);

	exit;

}

/*=============================================
Página de Informes - Listados de órdenes y ventas
=============================================*/

// Filtros de fechas - Predeterminado: Último mes completo
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('first day of last month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('last day of last month'));

// Construir URL para órdenes con filtros directamente en la API
$method = "GET";
$fields = array();

// URL base para órdenes con relaciones y filtro de fechas en la API
// Si las fechas son iguales, usar equalTo; si son diferentes, usar between
if($startDate == $endDate){
	// Fecha específica: usar equalTo
	if($_SESSION["admin"]->id_office_admin == 0){
		// Administrador principal: todas las oficinas
		$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=date_created_order&equalTo=".$startDate."&orderBy=id_order&orderMode=DESC";
	}else{
		// Usuario de oficina específica: filtrar por oficina Y fecha
		$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order,date_created_order&equalTo=".$_SESSION["admin"]->id_office_admin.",".$startDate."&orderBy=id_order&orderMode=DESC";
	}
}else{
	// Rango de fechas: usar between
	if($_SESSION["admin"]->id_office_admin == 0){
		// Administrador principal: todas las oficinas
		$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
	}else{
		// Usuario de oficina específica: filtrar por oficina Y fecha
		$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order&equalTo=".$_SESSION["admin"]->id_office_admin."&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
	}
}

$orders = CurlController::request($ordersUrl, $method, $fields);

if($orders->status == 200){
	$ordersData = $orders->results;
}else{
	$ordersData = array();
}

// URL base para ventas con relaciones y filtro de fechas en la API
// Si las fechas son iguales, usar equalTo; si son diferentes, usar between
if($startDate == $endDate){
	// Fecha específica: usar equalTo
	if($_SESSION["admin"]->id_office_admin == 0){
		// Administrador principal: todas las ventas
		$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=date_created_sale&equalTo=".$startDate."&orderBy=id_sale&orderMode=DESC";
	}else{
		// Usuario de oficina específica: filtrar por oficina Y fecha
		$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=id_office_sale,date_created_sale&equalTo=".$_SESSION["admin"]->id_office_admin.",".$startDate."&orderBy=id_sale&orderMode=DESC";
	}
}else{
	// Rango de fechas: usar between
	if($_SESSION["admin"]->id_office_admin == 0){
		// Administrador principal: todas las ventas
		$salesUrl = "relations?rel=sales,products&type=sale,product&between1=date_created_sale&between2=".$startDate.",".$endDate."&orderBy=id_sale&orderMode=DESC";
	}else{
		// Usuario de oficina específica: filtrar por oficina Y fecha
		$salesUrl = "relations?rel=sales,products&type=sale,product&linkTo=id_office_sale&equalTo=".$_SESSION["admin"]->id_office_admin."&between1=date_created_sale&between2=".$startDate.",".$endDate."&orderBy=id_sale&orderMode=DESC";
	}
}

$sales = CurlController::request($salesUrl, $method, $fields);

if($sales->status == 200){
	$salesData = $sales->results;
}else{
	$salesData = array();
}

/*=============================================
Calcular estadísticas y métricas directamente de los datos filtrados
=============================================*/

// Estadísticas de órdenes - Los datos ya vienen con relaciones de la API
$totalOrders = count($ordersData);
$totalOrdersAmount = 0;
$totalOrdersSubtotal = 0;
$totalOrdersDiscount = 0;
$completedOrders = 0;
$pendingOrders = 0;

foreach ($ordersData as $orderInfo) {
	// Los datos ya vienen con las relaciones desde la API
	$totalOrdersAmount += floatval($orderInfo->total_order ?? 0);
	$totalOrdersSubtotal += floatval($orderInfo->subtotal_order ?? 0);
	$totalOrdersDiscount += floatval($orderInfo->discount_order ?? 0);
	
	if(($orderInfo->status_order ?? '') == 'Completada'){
		$completedOrders++;
	}else{
		$pendingOrders++;
	}
}

// Estadísticas de ventas - Los datos ya vienen con relaciones de la API
$totalSales = count($salesData);
$totalSalesAmount = 0;
$totalSalesQty = 0;
$salesByProduct = [];

foreach ($salesData as $saleInfo) {
	// Los datos ya vienen con las relaciones desde la API
	$totalSalesAmount += floatval($saleInfo->subtotal_sale ?? 0);
	$totalSalesQty += intval($saleInfo->qty_sale ?? 0);
	
	// Agrupar por producto para gráficos
	$productName = urldecode($saleInfo->title_product ?? 'Sin nombre');
	if(!isset($salesByProduct[$productName])){
		$salesByProduct[$productName] = 0;
	}
	$salesByProduct[$productName] += floatval($saleInfo->subtotal_sale ?? 0);
}

// Calcular promedios
$avgOrderAmount = $totalOrders > 0 ? $totalOrdersAmount / $totalOrders : 0;
$avgSaleAmount = $totalSales > 0 ? $totalSalesAmount / $totalSales : 0;

// Preparar datos para gráficos (top 10 productos)
arsort($salesByProduct);
$topProducts = array_slice($salesByProduct, 0, 10, true);

// Preparar datos para gráfico de ventas por día usando date_created_order (campo DATE)
// Los datos ya vienen filtrados y con relaciones desde la API
$salesByDay = [];
foreach ($ordersData as $orderInfo) {
	// Usar date_created_order (campo DATE) en lugar de date_order (campo DATETIME)
	$date = $orderInfo->date_created_order ?? date('Y-m-d');
	
	if(!isset($salesByDay[$date])){
		$salesByDay[$date] = 0;
	}
	$salesByDay[$date] += floatval($orderInfo->total_order ?? 0);
}
ksort($salesByDay);

/*=============================================
Estadísticas por Sucursal (para todos los administradores)
=============================================*/

$statsByOffice = [];

// Obtener todas las sucursales (para cualquier admin)
$urlOffices = "offices?select=id_office,title_office";
$officesResponse = CurlController::request($urlOffices, $method, $fields);

if($officesResponse->status == 200){
	$officesList = $officesResponse->results;
	
	// Calcular estadísticas por cada sucursal
	foreach ($officesList as $office) {
		$officeId = $office->id_office;
		$officeName = urldecode($office->title_office);
		
		// Obtener órdenes de esta sucursal en el rango de fechas
		if($startDate == $endDate){
			$urlOfficeOrders = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order,date_created_order&equalTo=".$officeId.",".$startDate."&orderBy=id_order&orderMode=DESC";
		}else{
			$urlOfficeOrders = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=id_office_order&equalTo=".$officeId."&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
		}
		
		$officeOrders = CurlController::request($urlOfficeOrders, $method, $fields);
		
		if($officeOrders->status == 200){
			$officeOrdersData = $officeOrders->results;
			
			// Calcular estadísticas de esta sucursal
			$officeStats = [
				'id_office' => $officeId,
				'name' => $officeName,
				'total_orders' => count($officeOrdersData),
				'total_amount' => 0,
				'completed_orders' => 0,
				'pending_orders' => 0,
				'avg_order' => 0
			];
			
			foreach ($officeOrdersData as $orderInfo) {
				$officeStats['total_amount'] += floatval($orderInfo->total_order ?? 0);
				
				if(($orderInfo->status_order ?? '') == 'Completada'){
					$officeStats['completed_orders']++;
				}else{
					$officeStats['pending_orders']++;
				}
			}
			
			$officeStats['avg_order'] = $officeStats['total_orders'] > 0 ? $officeStats['total_amount'] / $officeStats['total_orders'] : 0;
			
			$statsByOffice[] = $officeStats;
		}
	}
	
	// Ordenar por total_amount descendente
	usort($statsByOffice, function($a, $b) {
		return $b['total_amount'] - $a['total_amount'];
	});
}

?>

<!--=============================================
Página de Informes
=============================================-->

<div class="container-fluid py-3 p-lg-4">
	
	<div class="row">
		
		<div class="col-12 mb-4">
			
			<div class="card rounded p-3">
				
				<div class="card-header bg-white pb-3">
					
					<div class="d-lg-flex justify-content-between align-items-center">
						
						<h4 class="mb-0"><i class="bi bi-graph-up me-2"></i> Informes de Ventas</h4>
						
						<!--=========================================
						Filtro de fechas
						===========================================-->
						
						<div class="mt-3 mt-lg-0">
							
							<button type="button" class="btn btn-sm btn-default rounded" id="report-daterange-btn">
								
								<i class="far fa-calendar-alt me-1"></i>
								
								<small>
									<span id="report-startDate"><?php echo $startDate ?></span>
									-
									<span id="report-endDate"><?php echo $endDate ?></span>
									<i class="fas fa-caret-down ms-1"></i>
								</small>

							</button>
							
						</div>
						
					</div>
					
				</div>
				
				<div class="card-body">
					
					<!--=========================================
					Métricas y Resúmenes
					===========================================-->
					
					<div class="row mb-4">
						
						<!-- Total de Ventas -->
						<div class="col-12 col-md-6 col-lg-3 mb-3">
							<div class="card border-0 shadow-sm bg-primary text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="mb-1 text-white-50">Total Ventas</h6>
											<h4 class="mb-0">Bs <?php echo number_format($totalOrdersAmount, 2, ',', '.') ?></h4>
										</div>
										<i class="bi bi-cash-coin fs-1 opacity-50"></i>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Total de Órdenes -->
						<div class="col-12 col-md-6 col-lg-3 mb-3">
							<div class="card border-0 shadow-sm bg-success text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="mb-1 text-white-50">Total Órdenes</h6>
											<h4 class="mb-0"><?php echo $totalOrders ?></h4>
											<small class="text-white-50">Completadas: <?php echo $completedOrders ?></small>
										</div>
										<i class="bi bi-cart-check fs-1 opacity-50"></i>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Promedio por Orden -->
						<div class="col-12 col-md-6 col-lg-3 mb-3">
							<div class="card border-0 shadow-sm bg-info text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="mb-1 text-white-50">Promedio Orden</h6>
											<h4 class="mb-0">Bs <?php echo number_format($avgOrderAmount, 2, ',', '.') ?></h4>
										</div>
										<i class="bi bi-graph-up fs-1 opacity-50"></i>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Total Productos Vendidos -->
						<div class="col-12 col-md-6 col-lg-3 mb-3">
							<div class="card border-0 shadow-sm bg-warning text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="mb-1 text-white-50">Productos Vendidos</h6>
											<h4 class="mb-0"><?php echo $totalSalesQty ?></h4>
											<small class="text-white-50">Ventas: <?php echo $totalSales ?></small>
										</div>
										<i class="bi bi-box-seam fs-1 opacity-50"></i>
									</div>
								</div>
							</div>
						</div>
						
					</div>
					
					<!--=========================================
					Resumen Financiero
					===========================================-->
					
					<div class="row mb-4">
						
						<div class="col-12 col-md-6 col-lg-4 mb-3">
							<div class="card border-0 shadow-sm">
								<div class="card-body text-center">
									<h6 class="text-muted mb-2">Subtotal</h6>
									<h5 class="mb-0 text-primary">Bs <?php echo number_format($totalOrdersSubtotal, 2, ',', '.') ?></h5>
								</div>
							</div>
						</div>
						
						<div class="col-12 col-md-6 col-lg-4 mb-3">
							<div class="card border-0 shadow-sm">
								<div class="card-body text-center">
									<h6 class="text-muted mb-2">Descuentos</h6>
									<h5 class="mb-0 text-danger">- Bs <?php echo number_format($totalOrdersDiscount, 2, ',', '.') ?></h5>
								</div>
							</div>
						</div>
						
						<div class="col-12 col-md-6 col-lg-4 mb-3">
							<div class="card border-0 shadow-sm bg-light">
								<div class="card-body text-center">
									<h6 class="text-muted mb-2">Total General</h6>
									<h4 class="mb-0 text-success">Bs <?php echo number_format($totalOrdersAmount, 2, ',', '.') ?></h4>
								</div>
							</div>
						</div>
						
					</div>
					
					<!--=========================================
					Comparación por Sucursal (para todos los administradores)
					===========================================-->
					
					<?php if(!empty($statsByOffice)): ?>
					
					<div class="row mb-4">
						
						<div class="col-12 mb-3">
							<div class="card border-0 shadow-sm">
								<div class="card-header bg-white">
									<h6 class="mb-0"><i class="bi bi-building me-2"></i> Comparación de Ventas por Sucursal</h6>
								</div>
								<div class="card-body">
									<canvas id="officesComparisonChart" height="60"></canvas>
								</div>
							</div>
						</div>
						
					</div>
					
					<?php endif ?>
					

					
					<!--=========================================
					Gráficos
					===========================================-->
					
					<div class="row mb-4">
						
						<!-- Gráfico de Ventas por Día -->
						<div class="col-12 col-lg-8 mb-3">
							<div class="card border-0 shadow-sm">
								<div class="card-header bg-white">
									<h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Ventas por Día</h6>
								</div>
								<div class="card-body">
									<canvas id="salesByDayChart" height="100"></canvas>
								</div>
							</div>
						</div>
						
						<!-- Top Productos -->
						<div class="col-12 col-lg-4 mb-3">
							<div class="card border-0 shadow-sm">
								<div class="card-header bg-white">
									<h6 class="mb-0"><i class="bi bi-trophy me-2"></i> Top 10 Productos</h6>
								</div>
								<div class="card-body">
									<canvas id="topProductsChart" height="250"></canvas>
								</div>
							</div>
						</div>
						
					</div>
					
					<!--=========================================
					Tabs para órdenes y ventas
					===========================================-->
					
					<ul class="nav nav-tabs mb-4" id="reportsTabs" role="tablist">
						
						<li class="nav-item" role="presentation">
							<button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="true">
								<i class="bi bi-cart-check me-1"></i> Órdenes
							</button>
						</li>
						
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab" aria-controls="sales" aria-selected="false">
								<i class="bi bi-cash-coin me-1"></i> Ventas
							</button>
						</li>
						
					</ul>
					
					<div class="tab-content" id="reportsTabsContent">
						
						<!--=========================================
						Tab de Órdenes
						===========================================-->
						
						<div class="tab-pane fade show active" id="orders" role="tabpanel" aria-labelledby="orders-tab">
							
							<div class="d-flex justify-content-end mb-3">
								
								<button type="button" class="btn btn-success btn-sm rounded" id="exportOrdersExcel">
									<i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
								</button>
								
							</div>
							
							<div class="table-responsive">
								
								<table class="table table-striped table-hover" id="ordersTable">
									
									<thead>
										<tr>
											<th>ID</th>
											<th>Transacción</th>
											<th>Cliente</th>
											<th>Fecha</th>
											<th>Método de Pago</th>
											<th>Estado</th>
											<th>Subtotal</th>
											<th>Descuento</th>
											<th>Total</th>
											<th class="text-center">Acciones</th>
										</tr>
									</thead>
									
									<tbody>
										
										<?php if(!empty($ordersData)): ?>
											
											<?php 
											
											// Los datos ya vienen con relaciones desde la API
											foreach ($ordersData as $index => $orderInfo): 
												
											?>
											
											<tr>
												<td><?php echo $index + 1 ?></td>
												<td><?php echo htmlspecialchars($orderInfo->transaction_order ?? 'N/A') ?></td>
												<td><?php echo htmlspecialchars(($orderInfo->name_client ?? '') . ' ' . ($orderInfo->surname_client ?? '')) ?></td>
												<td><?php echo htmlspecialchars($orderInfo->date_created_order ?? $orderInfo->date_order ?? 'N/A') ?></td>
												<td><?php echo htmlspecialchars($orderInfo->method_order ?? 'N/A') ?></td>
												<td>
													<span class="badge <?php echo ($orderInfo->status_order == 'Completada') ? 'bg-success' : 'bg-warning' ?>">
														<?php echo htmlspecialchars($orderInfo->status_order ?? 'N/A') ?>
													</span>
												</td>
												<td>Bs <?php echo number_format($orderInfo->subtotal_order ?? 0, 2, ',', '.') ?></td>
												<td>Bs <?php echo number_format($orderInfo->discount_order ?? 0, 2, ',', '.') ?></td>
												<td><strong>Bs <?php echo number_format($orderInfo->total_order ?? 0, 2, ',', '.') ?></strong></td>
												<td class="text-center">
													<a href="/reports?id_order=<?php echo base64_encode($orderInfo->id_order) ?>" class="btn btn-sm text-danger rounded m-0 p-1 border-0" target="_blank" title="Ver PDF">
														<i class="bi bi-filetype-pdf"></i>
													</a>
												</td>
											</tr>
											
											<?php endforeach ?>
											
										<?php else: ?>
											
											<tr>
												<td colspan="10" class="text-center py-4">No hay órdenes en el rango de fechas seleccionado</td>
											</tr>
											
										<?php endif ?>
										
									</tbody>
									
								</table>
								
							</div>
							
						</div>
						
						<!--=========================================
						Tab de Ventas
						===========================================-->
						
						<div class="tab-pane fade" id="sales" role="tabpanel" aria-labelledby="sales-tab">
							
							<div class="d-flex justify-content-end mb-3">
								
								<button type="button" class="btn btn-success btn-sm rounded" id="exportSalesExcel">
									<i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
								</button>
								
							</div>
							
							<div class="table-responsive">
								
								<table class="table table-striped table-hover" id="salesTable">
									
									<thead>
										<tr>
											<th>ID</th>
											<th>Producto</th>
											<th>Cantidad</th>
											<th>Precio Unitario</th>
											<th>IVA (%)</th>
											<th>Descuento (%)</th>
											<th>Subtotal</th>
											<th>Estado</th>
											<th>Fecha</th>
										</tr>
									</thead>
									
									<tbody>
										
										<?php if(!empty($salesData)): ?>
											
											<?php 
											
											// Los datos ya vienen con relaciones desde la API
											foreach ($salesData as $index => $saleInfo): 
												
											?>
											
											<tr>
												<td><?php echo $index + 1 ?></td>
												<td><?php echo htmlspecialchars(urldecode($saleInfo->title_product ?? 'N/A')) ?></td>
												<td><?php echo htmlspecialchars($saleInfo->qty_sale ?? '0') ?></td>
												<td>Bs <?php echo number_format($saleInfo->price_sale ?? 0, 2, ',', '.') ?></td>
												<td><?php echo htmlspecialchars($saleInfo->tax_sale ?? '0') ?>%</td>
												<td><?php echo htmlspecialchars($saleInfo->discount_sale ?? '0') ?>%</td>
												<td><strong>Bs <?php echo number_format($saleInfo->subtotal_sale ?? 0, 2, ',', '.') ?></strong></td>
												<td>
													<span class="badge <?php echo ($saleInfo->status_sale == 'Completada') ? 'bg-success' : 'bg-warning' ?>">
														<?php echo htmlspecialchars($saleInfo->status_sale ?? 'N/A') ?>
													</span>
												</td>
												<td><?php echo htmlspecialchars($saleInfo->date_created_sale ?? 'N/A') ?></td>
											</tr>
											
											<?php endforeach ?>
											
										<?php else: ?>
											
											<tr>
												<td colspan="9" class="text-center py-4">No hay ventas en el rango de fechas seleccionado</td>
											</tr>
											
										<?php endif ?>
										
									</tbody>
									
								</table>
								
							</div>
							
						</div>
						
					</div>
					
				</div>
				
			</div>
			
		</div>
		
	</div>
	
</div>

<!--=============================================
Inputs ocultos para filtros
=============================================-->

<input type="hidden" id="report-between1" value="<?php echo $startDate ?>">
<input type="hidden" id="report-between2" value="<?php echo $endDate ?>">

<!--=============================================
Scripts para filtros y exportación
=============================================-->

<script>
	
$(document).ready(function(){
	
	/*=============================================
	Inicializar daterangepicker para informes
	=============================================*/
	
	$('#report-daterange-btn').daterangepicker({
		"locale": {
			"format": "YYYY-MM-DD",
			"separator": " - ",
			"applyLabel": "Aplicar",
			"cancelLabel": "Cancelar",
			"fromLabel": "Desde",
			"toLabel": "Hasta",
			"customRangeLabel": "Rango Personalizado",
			"daysOfWeek": ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
			"monthNames": ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
			"firstDay": 1
		},
		ranges: {
			'Hoy': [moment(), moment()],
			'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
			'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
			'Este Mes': [moment().startOf('month'), moment().endOf('month')],
			'Último Mes': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
			'Este Año': [moment().startOf('year'), moment().endOf('year')],
			'Último Año': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
		},
		startDate: moment($("#report-between1").val()),
		endDate: moment($("#report-between2").val())
	}, function (start, end) {
		
		var between1 = start.format('YYYY-MM-DD');
		var between2 = end.format('YYYY-MM-DD');
		
		$("#report-startDate").html(between1);
		$("#report-endDate").html(between2);
		
		$("#report-between1").val(between1);
		$("#report-between2").val(between2);
		
		// Recargar la página con los nuevos filtros
		window.location.href = '/reports?start_date=' + between1 + '&end_date=' + between2;
		
	});
	
	/*=============================================
	Exportar órdenes a Excel
	=============================================*/
	
	$('#exportOrdersExcel').on('click', function(){
		
		var startDate = $('#report-between1').val();
		var endDate = $('#report-between2').val();
		
		window.location.href = '/ajax/reports.ajax.php?action=export_orders&start_date=' + startDate + '&end_date=' + endDate;
		
	});
	
	/*=============================================
	Exportar ventas a Excel
	=============================================*/
	
	$('#exportSalesExcel').on('click', function(){
		
		var startDate = $('#report-between1').val();
		var endDate = $('#report-between2').val();
		
		window.location.href = '/ajax/reports.ajax.php?action=export_sales&start_date=' + startDate + '&end_date=' + endDate;
		
	});
	
	/*=============================================
	Gráficos con Chart.js
	=============================================*/
	
	// Datos PHP preparados para JavaScript
	var salesByDayData = <?php echo json_encode(array_values($salesByDay)); ?>;
	var salesByDayLabels = <?php echo json_encode(array_keys($salesByDay)); ?>;
	var topProductsData = <?php echo json_encode(array_values($topProducts)); ?>;
	var topProductsLabels = <?php echo json_encode(array_map(function($name) { return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name; }, array_keys($topProducts))); ?>;
	
	// Datos para comparación por sucursal (solo para administrador principal)
	var statsByOffice = <?php echo json_encode($statsByOffice); ?>;
	
	// Gráfico de Ventas por Día
	var ctxSalesByDay = document.getElementById('salesByDayChart');
	if(ctxSalesByDay && salesByDayData.length > 0){
		var salesByDayChart = new Chart(ctxSalesByDay, {
			type: 'line',
			data: {
				labels: salesByDayLabels,
				datasets: [{
					label: 'Ventas (Bs)',
					data: salesByDayData,
					borderColor: 'rgb(75, 192, 192)',
					backgroundColor: 'rgba(75, 192, 192, 0.2)',
					tension: 0.4,
					fill: true
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						display: true,
						position: 'top'
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								return 'Bs ' + context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
							}
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						ticks: {
							callback: function(value) {
								return 'Bs ' + value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
							}
						}
					}
				}
			}
		});
	} else if(ctxSalesByDay) {
		// Mostrar mensaje si no hay datos
		ctxSalesByDay.parentElement.innerHTML = '<div class="text-center py-4 text-muted">No hay datos para mostrar en el rango de fechas seleccionado</div>';
	}
	
	// Gráfico de Top Productos
	var ctxTopProducts = document.getElementById('topProductsChart');
	if(ctxTopProducts && topProductsData.length > 0){
		var topProductsChart = new Chart(ctxTopProducts, {
			type: 'doughnut',
			data: {
				labels: topProductsLabels,
				datasets: [{
					label: 'Ventas (Bs)',
					data: topProductsData,
					backgroundColor: [
						'rgba(255, 99, 132, 0.8)',
						'rgba(54, 162, 235, 0.8)',
						'rgba(255, 206, 86, 0.8)',
						'rgba(75, 192, 192, 0.8)',
						'rgba(153, 102, 255, 0.8)',
						'rgba(255, 159, 64, 0.8)',
						'rgba(199, 199, 199, 0.8)',
						'rgba(83, 102, 255, 0.8)',
						'rgba(255, 99, 255, 0.8)',
						'rgba(99, 255, 132, 0.8)'
					],
					borderColor: [
						'rgba(255, 99, 132, 1)',
						'rgba(54, 162, 235, 1)',
						'rgba(255, 206, 86, 1)',
						'rgba(75, 192, 192, 1)',
						'rgba(153, 102, 255, 1)',
						'rgba(255, 159, 64, 1)',
						'rgba(199, 199, 199, 1)',
						'rgba(83, 102, 255, 1)',
						'rgba(255, 99, 255, 1)',
						'rgba(99, 255, 132, 1)'
					],
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						display: true,
						position: 'right'
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var label = context.label || '';
								var value = context.parsed || 0;
								var total = context.dataset.data.reduce((a, b) => a + b, 0);
								var percentage = ((value / total) * 100).toFixed(1);
								return label + ': Bs ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' (' + percentage + '%)';
							}
						}
					}
				}
			}
		});
	} else if(ctxTopProducts) {
		// Mostrar mensaje si no hay datos
		ctxTopProducts.parentElement.innerHTML = '<div class="text-center py-4 text-muted">No hay datos para mostrar en el rango de fechas seleccionado</div>';
	}
	
	/*=============================================
	Gráfico de Comparación por Sucursal
	=============================================*/
	
	var ctxOfficesComparison = document.getElementById('officesComparisonChart');
	if(ctxOfficesComparison && statsByOffice && statsByOffice.length > 0){
		var officeNames = statsByOffice.map(function(office) { 
			return office.name.length > 20 ? office.name.substring(0, 20) + '...' : office.name; 
		});
		var officeAmounts = statsByOffice.map(function(office) { return office.total_amount; });
		var officeAvg = statsByOffice.map(function(office) { return office.avg_order; });
		
		// Generar colores para cada sucursal
		var colors = [
			'rgba(54, 162, 235, 0.8)', 'rgba(255, 99, 132, 0.8)', 'rgba(75, 192, 192, 0.8)',
			'rgba(255, 206, 86, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)',
			'rgba(199, 199, 199, 0.8)', 'rgba(83, 102, 255, 0.8)', 'rgba(255, 99, 255, 0.8)',
			'rgba(99, 255, 132, 0.8)', 'rgba(255, 159, 64, 0.8)', 'rgba(153, 102, 255, 0.8)'
		];
		var borderColors = [
			'rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)',
			'rgba(255, 206, 86, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)',
			'rgba(199, 199, 199, 1)', 'rgba(83, 102, 255, 1)', 'rgba(255, 99, 255, 1)',
			'rgba(99, 255, 132, 1)', 'rgba(255, 159, 64, 1)', 'rgba(153, 102, 255, 1)'
		];
		
		var officesComparisonChart = new Chart(ctxOfficesComparison, {
			type: 'bar',
			data: {
				labels: officeNames,
				datasets: [{
					label: 'Total Ventas (Bs)',
					data: officeAmounts,
					backgroundColor: officeAmounts.map(function(_, i) { return colors[i % colors.length]; }),
					borderColor: officeAmounts.map(function(_, i) { return borderColors[i % borderColors.length]; }),
					borderWidth: 2
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: true,
				plugins: {
					legend: {
						display: true,
						position: 'top'
					},
					tooltip: {
						callbacks: {
							label: function(context) {
								var value = context.parsed.y || 0;
								var officeIndex = context.dataIndex;
								var avg = officeAvg[officeIndex];
								return 'Ventas: Bs ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",") + 
									   ' | Promedio: Bs ' + avg.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
							}
						}
					}
				},
				scales: {
					x: {
						grid: {
							display: false
						}
					},
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: 'Ventas (Bs)',
							font: {
								weight: 'bold'
							}
						},
						ticks: {
							callback: function(value) {
								return 'Bs ' + value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
							}
						}
					}
				}
			}
		});
	} else if(ctxOfficesComparison) {
		// Mostrar mensaje si no hay datos
		ctxOfficesComparison.parentElement.innerHTML = '<div class="text-center py-4 text-muted">No hay datos de sucursales para mostrar en el rango de fechas seleccionado</div>';
	}
	
	/*=============================================
	Chat con ChatGPT para Análisis de Datos
	=============================================*/
	
	var chatMessages = $('#chatMessages');
	var chatInput = $('#chatInput');
	var sendButton = $('#sendChatMessage');
	var startDate = '<?php echo $startDate ?>';
	var endDate = '<?php echo $endDate ?>';
	var isLoading = false;
	
	// Función para añadir mensaje al chat
	function addMessage(content, isUser) {
		var messageClass = isUser ? 'text-end' : 'text-start';
		var bgClass = isUser ? 'bg-primary text-white' : 'bg-white';
		var icon = isUser ? '<i class="bi bi-person-circle me-2"></i>' : '<i class="bi bi-robot me-2"></i>';
		
		var messageHtml = '<div class="mb-3 ' + messageClass + '">' +
			'<div class="d-inline-block ' + bgClass + ' rounded p-3 shadow-sm" style="max-width: 80%;">' +
			'<div class="mb-1">' + icon + '<strong>' + (isUser ? 'Tú' : 'ChatGPT') + '</strong></div>' +
			'<div>' + content.replace(/\n/g, '<br>') + '</div>' +
			'</div>' +
			'</div>';
		
		// Si es el primer mensaje, limpiar el placeholder
		if(chatMessages.find('.text-center').length > 0){
			chatMessages.html('');
		}
		
		chatMessages.append(messageHtml);
		chatMessages.scrollTop(chatMessages[0].scrollHeight);
	}
	
	// Función para enviar mensaje
	function sendMessage() {
		var message = chatInput.val().trim();
		
		if(!message || isLoading){
			return;
		}
		
		// Añadir mensaje del usuario
		addMessage(message, true);
		chatInput.val('');
		chatInput.prop('disabled', true);
		sendButton.prop('disabled', true);
		isLoading = true;
		
		// Mostrar indicador de carga
		addMessage('<div class="spinner-border spinner-border-sm me-2" role="status"></div>Analizando datos...', false);
		
		// Enviar a ChatGPT
		var data = new FormData();
		data.append('message', message);
		data.append('token', localStorage.getItem('tokenAdmin'));
		data.append('start_date', startDate);
		data.append('end_date', endDate);
		
		$.ajax({
			url: '/ajax/reports-chat.ajax.php',
			method: 'POST',
			data: data,
			contentType: false,
			cache: false,
			processData: false,
			success: function(response) {
				// Remover mensaje de carga
				chatMessages.find('.mb-3').last().remove();
				
				try {
					var result = JSON.parse(response);
					
					if(result.status === 'success'){
						addMessage(result.message, false);
					}else{
						addMessage('<span class="text-danger">' + result.message + '</span>', false);
					}
				} catch(e) {
					addMessage('<span class="text-danger">Error al procesar la respuesta: ' + e.message + '</span>', false);
				}
				
				chatInput.prop('disabled', false);
				sendButton.prop('disabled', false);
				isLoading = false;
				chatInput.focus();
			},
			error: function(xhr, status, error) {
				// Remover mensaje de carga
				chatMessages.find('.mb-3').last().remove();
				
				addMessage('<span class="text-danger">Error al comunicarse con el servidor: ' + error + '</span>', false);
				
				chatInput.prop('disabled', false);
				sendButton.prop('disabled', false);
				isLoading = false;
				chatInput.focus();
			}
		});
	}
	
	// Enviar mensaje con botón
	sendButton.on('click', function() {
		sendMessage();
	});
	
	// Enviar mensaje con Enter
	chatInput.on('keypress', function(e) {
		if(e.which === 13 && !e.shiftKey){
			e.preventDefault();
			sendMessage();
		}
	});
	
});

</script>

