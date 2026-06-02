<?php if ($module->columns[$i]->type_column == "double" || $module->columns[$i]->type_column == "money"): ?>

<?php

$readonly = "";
$extraInputClass = "";

$isCajaCashForm = (isset($routesArray[0]) && $routesArray[0] == "caja" && $module->title_module == "cashs");

if(!empty($data) && $isCajaCashForm){

	list($cashSessionStart, $cashSessionEnd) = TemplateController::cashSessionTimeBounds($data);
	$cashSessionOffice = isset($data["id_office_cash"]) ? (int) $data["id_office_cash"] : (int) $_SESSION["admin"]->id_office_admin;

	/*=============================================
	Dinero Inicial
	=============================================*/

	if($module->columns[$i]->title_column == "start_cash" && $data[$module->columns[$i]->title_column] > 0){

		$readonly = "readonly";
	}

	/*=============================================
	Gastos (solo sesión de caja: desde apertura hasta cierre o ahora)
	=============================================*/

	if($module->columns[$i]->title_column == "bills_cash"){

		$totalBills = 0;

		$url = TemplateController::billsSessionApiUrl($cashSessionOffice, $cashSessionStart, $cashSessionEnd);
		$method = "GET";
		$fields = array();

		$bills = CurlController::request($url,$method,$fields);

		if($bills->status == 200){

			foreach ($bills->results as $key => $value) {
				
				$totalBills += $value->cost_bill;
			}

		}

		$data[$module->columns[$i]->title_column] = $totalBills;

		$readonly = "readonly tabindex=\"-1\"";
		$extraInputClass = " bg-light";

	}

	/*=============================================
	Ingresos por ventas (misma ventana temporal; órdenes completadas en la sesión)
	=============================================*/

	if($module->columns[$i]->title_column == "money_cash"){

		$url = TemplateController::ordersSessionApiUrl($cashSessionOffice, $cashSessionStart, $cashSessionEnd);
		$method = "GET";
		$fields = array();
	
		$orders = CurlController::request($url,$method,$fields);

		if($orders->status == 200){

			$data[$module->columns[$i]->title_column] = 0;

			foreach ($orders->results as $key => $value) {

				$s = isset($value->status_order) ? (string) $value->status_order : "";
				if($s === "Completada"){
					$data[$module->columns[$i]->title_column] += (float) $value->total_order;
				}
			}

		}

		$readonly = "readonly tabindex=\"-1\"";
		$extraInputClass = " bg-light";

	}

	/*=============================================
	Diferencia
	=============================================*/

	if($module->columns[$i]->title_column == "diff_cash"){

		$totalBills = 0;

		$urlB = TemplateController::billsSessionApiUrl($cashSessionOffice, $cashSessionStart, $cashSessionEnd);
		$bills = CurlController::request($urlB,"GET",array());

		if($bills->status == 200){

			foreach ($bills->results as $key => $value) {
				
				$totalBills += $value->cost_bill;
			}

		}

		$totalOrders  = 0;

		$urlO = TemplateController::ordersSessionApiUrl($cashSessionOffice, $cashSessionStart, $cashSessionEnd);
		$orders = CurlController::request($urlO,"GET",array());

		if($orders->status == 200){

			foreach ($orders->results as $key => $value) {

				$s = isset($value->status_order) ? (string) $value->status_order : "";
				if($s === "Completada"){
					$totalOrders += (float) $value->total_order;
				}
			}

		}

		$startCash = isset($data["start_cash"]) ? $data["start_cash"] : 0;
		$data[$module->columns[$i]->title_column] = $startCash + $totalOrders - $totalBills;

		$readonly = "readonly tabindex=\"-1\"";
		$extraInputClass = " bg-light";

	}

	/*=============================================
	Brecha
	=============================================*/

	if($module->columns[$i]->title_column == "gap_cash"){

			$readonly = "disabled";
	}

}

/*=============================================
Nueva caja: gastos / ingresos / diferencia no editables (el servidor calcula al guardar)
=============================================*/
if(empty($data) && $isCajaCashForm && in_array($module->columns[$i]->title_column, array("bills_cash", "money_cash", "diff_cash"), true)){

	$readonly = "readonly tabindex=\"-1\"";
	$extraInputClass = " bg-light";

}

?>

 	<input 
	type="number" 
	step="any"
	class="form-control rounded<?php echo $extraInputClass ?>"
	id="<?php echo $module->columns[$i]->title_column ?>"
	name="<?php echo $module->columns[$i]->title_column ?>"
	inputmode="decimal"
	placeholder="Ingresa un valor numerico"
	value="<?php if (!empty($data)): ?><?php echo urldecode($data[$module->columns[$i]->title_column]) ?><?php endif ?>"
	<?php echo $readonly ?>>
	<div class="invalid-feedback">Ingresa un monto numerico valido.</div>

	<script>
		
		$(document).on("change","#end_cash",function(){

			$("#gap_cash").val((Number($(this).val())-Number($("#diff_cash").val())).toFixed(2));
		
		})

	</script>
 	
<?php endif ?>
