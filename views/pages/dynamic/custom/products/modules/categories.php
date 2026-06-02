<?php 

/*=============================================
Traer categorías desde la BD
=============================================*/

$url = "categories?linkTo=status_category&equalTo=1";
$method = "GET";
$fields = array();

$categories = CurlController::request($url,$method,$fields);

if($categories->status == 200){

	$categories = $categories->results;

}else{

	$categories = array();
}

?>

<!--===================================
JD SLIDER	
=====================================-->

<div class="jd-slider mb-0 pb-0">
	
	<div class="slide-inner">
		
		<ul class="slide-area">

			<?php if (!empty($categories)): ?>

				<li>

					<div class="border-0 rounded text-center bg-white mx-1 p-3 pb-0 loadCategory" idCategory="all">
							
						<img src="<?php echo TemplateController::normalizeImage('https://pos.tutorialesatualcance.com/views/assets/files/67659e224786f6.png') ?>" class="img-fluid mx-auto" style="width:50px; cursor:pointer">
						<p class="pt-2 mb-0 lead" style="cursor:move"><strong>Todo</strong></p>

						<?php 

								if ($_SESSION["admin"]->id_office_admin > 0) {
									require_once "controllers/install.controller.php";
									$db = InstallController::connect();
									$role = $_SESSION["admin"]->rol_admin;
									$id_admin = $_SESSION["admin"]->id_admin;
									$id_office = $_SESSION["admin"]->id_office_admin;
									$hasSubWarehouse = false;
									if ($id_admin) {
										$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
										$stmtHasSub->execute([':office' => $id_office]);
										$hasSubWarehouse = (bool)$stmtHasSub->fetch(PDO::FETCH_ASSOC);
									}

									if ($hasSubWarehouse) {
										require_once "controllers/install.controller.php";
										$db = InstallController::connect();
										$stmtTot = $db->prepare("
											SELECT COUNT(DISTINCT wa.id_product_assignment)
											FROM warehouse_assignments wa
											JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
											JOIN products p ON wa.id_product_assignment = p.id_product
											WHERE sw.id_office_sub_warehouse = :office AND p.status_product = 1
										");
										$stmtTot->execute([':office' => $id_office]);
										$totalProducts = (int)$stmtTot->fetchColumn();
									} else {
										$url = "product_inventory?linkTo=id_office_inventory,status_inventory&equalTo=".$_SESSION["admin"]->id_office_admin.",1&select=id_inventory";
										$totalProducts = CurlController::request($url,$method,$fields);
										if($totalProducts->status == 200){
											$totalProducts = $totalProducts->total;
										}else{
											$totalProducts = 0;
										}
									}
								} else {
									$totalProducts = 0;
								}
	
						?>

						<p class="small pb-3" style="cursor:move"><?php echo $totalProducts ?> items</p>

					</div>
					
					
				</li>

				<?php foreach ($categories as $key => $value): ?>

					<li>
						
						<div class="border-0 rounded text-center bg-white mx-1 p-3 pb-0 loadCategory" idCategory="<?php echo $value->id_category ?>">
							
							<img src="<?php echo TemplateController::normalizeImage($value->img_category) ?>" class="img-fluid mx-auto" style="width:50px; cursor:pointer">
							<p class="pt-2 mb-0 lead" style="cursor:move"><strong><?php echo urldecode($value->title_category) ?></strong></p>

							<?php 

								if ($_SESSION["admin"]->id_office_admin > 0) {
									require_once "controllers/install.controller.php";
									$db = InstallController::connect();
									$role = $_SESSION["admin"]->rol_admin;
									$id_admin = $_SESSION["admin"]->id_admin;
									$id_office = $_SESSION["admin"]->id_office_admin;
									$hasSubWarehouse = false;
									if ($id_admin) {
										$stmtHasSub = $db->prepare("SELECT id_sub_warehouse FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
										$stmtHasSub->execute([':office' => $id_office]);
										$hasSubWarehouse = (bool)$stmtHasSub->fetch(PDO::FETCH_ASSOC);
									}

									if ($hasSubWarehouse) {
										require_once "controllers/install.controller.php";
										$db = InstallController::connect();
										$stmtTot = $db->prepare("
											SELECT COUNT(DISTINCT wa.id_product_assignment)
											FROM warehouse_assignments wa
											JOIN sub_warehouses sw ON wa.id_sub_warehouse_assignment = sw.id_sub_warehouse
											JOIN products p ON wa.id_product_assignment = p.id_product
											WHERE sw.id_office_sub_warehouse = :office AND p.id_category_product = :category AND p.status_product = 1
										");
										$stmtTot->execute([':office' => $id_office, ':category' => $value->id_category]);
										$totalProducts = (int)$stmtTot->fetchColumn();
									} else {
										// Contar productos activos en esta categoría para esta sucursal
										$url = "product_inventory?linkTo=id_office_inventory,status_inventory&equalTo=".$_SESSION["admin"]->id_office_admin.",1&select=id_product_inventory";
										$allInv = CurlController::request($url,$method,$fields);
										if(isset($allInv->status) && $allInv->status == 200 && !empty($allInv->results)){
											$productIds = array_map(function($r){ return $r->id_product_inventory; }, $allInv->results);
											$urlCat = "products?linkTo=id_category_product&equalTo=".$value->id_category."&select=id_product";
											$catProds = CurlController::request($urlCat,$method,$fields);
											if(isset($catProds->status) && $catProds->status == 200 && !empty($catProds->results)){
												$catIds = array_map(function($r){ return $r->id_product; }, $catProds->results);
												$totalProducts = count(array_intersect($productIds, $catIds));
											}else{
												$totalProducts = 0;
											}
										}else{
											$totalProducts = 0;
										}
									}

								}else{

									$totalProducts = 0;
								}
							 ?>

							<p class="small pb-3" style="cursor:move"><?php echo $totalProducts ?> items</p>

						</div>
					</li>
					
				<?php endforeach ?>
				
			<?php endif ?>
			

		</ul>

		<a href="#" class="prev ps-1">	
			<i class="bi bi-chevron-left"></i>
		</a>

		<a href="#" class="next ps-1">	
			<i class="bi bi-chevron-right"></i>
		</a>

	</div>

	<div class="controller d-none">
		<div class="indicate-area"></div>	
	</div>

</div>