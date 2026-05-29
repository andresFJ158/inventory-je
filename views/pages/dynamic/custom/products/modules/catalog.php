<?php
require_once "controllers/install.controller.php";

$limit = 6;
$role = $_SESSION["admin"]->rol_admin;
$id_admin = $_SESSION["admin"]->id_admin;
$id_office = $_SESSION["admin"]->id_office_admin;

try {
	$db = InstallController::connect();

	if ($role != "superadmin" && $role != "admin" && $role != "despachador") {
		$sqlCount = "
			SELECT COUNT(*) as total
			FROM (
				SELECT p.id_product
				FROM products p
				INNER JOIN categories c ON p.id_category_product = c.id_category
				INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
				INNER JOIN (
					SELECT wa.id_product_assignment,
						   (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
							COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
					FROM warehouse_assignments wa
					JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
					WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
					GROUP BY wa.id_product_assignment
					HAVING stock > 0
				) sub ON p.id_product = sub.id_product_assignment
				WHERE p.status_product = 1
			) t
		";
		$stmtCount = $db->prepare($sqlCount);
		$stmtCount->execute([':admin' => $id_admin, ':office' => $id_office]);
	} else {
		$sqlCount = "
			SELECT COUNT(*) as total
			FROM products p
			INNER JOIN categories c ON p.id_category_product = c.id_category
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product
			WHERE pi.id_office_inventory = :office AND pi.status_inventory = 1 AND p.status_product = 1
		";
		$stmtCount = $db->prepare($sqlCount);
		$stmtCount->execute([':office' => $id_office]);
	}
	$totalResult = $stmtCount->fetch(PDO::FETCH_OBJ);
	$totalProducts = $totalResult ? (int)$totalResult->total : 0;
	$totalPageProducts = ceil($totalProducts / $limit);

	/*=============================================
	Query de productos: JOIN con product_inventory para stock y estado por sucursal.
	Para vendedores (sub-almacén), el stock viene de warehouse_assignments.
	=============================================*/
	if ($role != "superadmin" && $role != "admin" && $role != "despachador") {
		$sql = "
			SELECT p.*, c.title_category, c.img_category, c.order_category, c.status_category,
				   sub.stock as stock_product
			FROM products p
			INNER JOIN categories c ON p.id_category_product = c.id_category
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office AND pi.status_inventory = 1
			INNER JOIN (
				SELECT wa.id_product_assignment,
					   (COALESCE(SUM(CASE WHEN wa.type_assignment = 'despacho' THEN wa.qty_assignment ELSE 0 END), 0) -
						COALESCE(SUM(CASE WHEN wa.type_assignment IN ('devolucion', 'venta') THEN wa.qty_assignment ELSE 0 END), 0)) as stock
				FROM warehouse_assignments wa
				JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
				WHERE sw.id_admin_sub_warehouse = :admin AND sw.id_office_sub_warehouse = :office
				GROUP BY wa.id_product_assignment
				HAVING stock > 0
			) sub ON p.id_product = sub.id_product_assignment
			WHERE p.status_product = 1
			ORDER BY p.id_product DESC
			LIMIT 0, " . (int)$limit;
		$params = [':admin' => $id_admin, ':office' => $id_office];
	} else {
		$sql = "
			SELECT p.*, c.title_category, c.img_category, c.order_category, c.status_category,
				   COALESCE(pi.stock_inventory, 0) as stock_product
			FROM products p
			INNER JOIN categories c ON p.id_category_product = c.id_category
			INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product AND pi.id_office_inventory = :office
			WHERE pi.status_inventory = 1 AND p.status_product = 1
			ORDER BY pi.stock_inventory DESC
			LIMIT 0, " . (int)$limit;
		$params = [':office' => $id_office];
	}

	$stmt = $db->prepare($sql);
	$stmt->execute($params);
	$products = $stmt->fetchAll(PDO::FETCH_CLASS);
} catch (Exception $e) {
	$products = array();
	$totalPageProducts = 0;
}
$method = "GET";
$fields = array();
?>

<?php if (!empty($products)): ?>

	<div class="row p-2 viewProducts">
		
		<?php foreach ($products as $key => $value): ?>

			<div class="col-12 col-lg-6 col-xl-4 p-2 btn addProductPos" idProduct="<?php echo $value->id_product ?>">
				
				<div class="card rounded border-0 position-relative">

					<?php if ($value->discount_product > 0): ?>

						<div class="position-absolute small bg-red p-1 shadow-sm rounded" style="top:4px; left:4px; font-size:10px"><?php echo $value->discount_product ?>% OFF</div>
						
					<?php endif ?>
					
					<div class="position-absolute small bg-white p-1 shadow-sm rounded" style="top:4px; right:4px; font-size:10px"><?php echo $value->sku_product ?></div>

					<?php 
						$imgSrc = TemplateController::fallbackProductImage($value->sku_product ?? '', $value->title_product ?? '', $value->img_product ?? '');
						if (empty($imgSrc) || $imgSrc === 'NULL' || $imgSrc === 'null') {
							$imgSrc = 'views/assets/img/multimedia.png';
						}
					?>
					<img src="<?php echo urldecode($imgSrc) ?>" class="card-img-top px-5 py-3 mx-auto" style="width:180px !important">

					<div class="card-body">
						
						<h6 class="font-weight-bold text-gray samll"><?php echo urldecode($value->title_category) ?></h6>
						<h6 class="card-title pb-2 font-weight-bold"><?php echo urldecode($value->title_product) ?></h6>

						<div class="d-flex justify-content-between">

							<?php 

							if($value->stock_product < 50){

								$colorStock = "bg-maroon";
							}

							if($value->stock_product >= 50 && $value->stock_product < 100){

								$colorStock = "bg-indigo";
							}

							if($value->stock_product >= 100){

								$colorStock = "bg-teal";
							}

							?>

							<div class="card-text small h6 badge badge-default pb-0 <?php echo $colorStock  ?>" style="font-size:10px; padding-top:6px">
								
								<?php echo $value->stock_product ?>

							</div>

							<?php 

							$url = "purchases?linkTo=id_product_purchase&equalTo=".$value->id_product."&select=cost_purchase,date_created_purchase&orderBy=date_created_purchase&orderMode=DESC";

							$price = CurlController::request($url,$method,$fields);

							if($price->status == 200){

								$price = $price->results[0]->cost_purchase;

								if($value->discount_product > 0){

									$discount = $price-($price*($value->discount_product/100));
								}

							}else{

								$price = 0;
							}

							?>

							<?php if ($value->discount_product > 0): ?>

								<span class="small ms-auto pe-1 h6 mt-1 text-red font-weight-bold" style="font-size:12px"><s>$ <?php echo number_format($price,2) ?></s></span>


								<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs <?php echo number_format($discount,2) ?></strong></div>

							<?php else: ?>

								<div class="small h6 mt-1 textColor font-weight-bold"><strong>Bs <?php echo number_format($price,2) ?></strong></div>

							<?php endif ?>

						</div>

					</div>

				</div>
			</div>
			
		<?php endforeach ?>

	</div>

	<?php if ($totalPageProducts > 1): ?>

		<div id="loadPageProducts" class="d-flex justify-content-center mb-5">	
			<div><button class="btn btn-sm rounded bg-blue px-3 py-2">Cargar más productos</button></div>
		</div>
		
	<?php endif ?>

	<input type="hidden" id="totalPagesProducts" value="<?php echo $totalPageProducts ?>">
	<input type="hidden" id="currentPageProducts" value="1">
	<input type="hidden" id="limitProduct" value="<?php echo $limit ?>">
	<input type="hidden" id="idOffice" value="<?php echo $_SESSION["admin"]->id_office_admin ?>">
	<input type="hidden" id="filterByCategory" value="all">
	<input type="hidden" id="sellerId" value="<?php echo $_SESSION['admin']->id_admin ?>">
	<input type="hidden" id="sellerRole" value="<?php echo $_SESSION['admin']->rol_admin ?>">

<?php else: ?>

	<div class="row p-2 my-5 text-center">
		
		<?php include "svg.php" ?>

		<p>No hay productos agregados a esta Sucursal</p>

	</div>
	
<?php endif ?>