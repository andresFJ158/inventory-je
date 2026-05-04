<?php if ($module->columns[$i]->type_column == "double" || $module->columns[$i]->type_column == "money"): ?>

<?php

$readonly = "";

if(!empty($data) && $routesArray[0] == "caja"){

	/*=============================================
	Dinero Inicial
	=============================================*/

	if($module->columns[$i]->title_column == "start_cash" && $data[$module->columns[$i]->title_column] > 0){

		$readonly = "readonly";
	}

	/*=============================================
	Gastos
	=============================================*/

	if($module->columns[$i]->title_column == "bills_cash"){

		// Inicializar el total de gastos en 0 para calcular desde cero
		$totalBills = 0;

		// Obtener la fecha de creación de la caja (no la fecha actual)
		// Esto asegura que solo se muestren los gastos de la caja específica
		$cashDate = isset($data["date_created_cash"]) ? $data["date_created_cash"] : date("Y-m-d");
		$cashOffice = isset($data["id_office_cash"]) ? $data["id_office_cash"] : $_SESSION["admin"]->id_office_admin;

		// Buscar gastos solo de la fecha de la caja y la sucursal de la caja
		$url = "bills?linkTo=date_created_bill,id_office_bill&equalTo=".$cashDate.",".$cashOffice;
		$method = "GET";
		$fields = array();

		$bills = CurlController::request($url,$method,$fields);

		if($bills->status == 200){

			foreach ($bills->results as $key => $value) {
				
				$totalBills += $value->cost_bill;
			}

		}

		// Asignar el total de gastos como valor positivo (no negativo)
		$data[$module->columns[$i]->title_column] = $totalBills;

		$readonly = "readonly";

	}

	/*=============================================
	Ingresos en Efectivo
	=============================================*/

	if($module->columns[$i]->title_column == "money_cash"){

		// Obtener la fecha de creación de la caja (no la fecha actual)
		// Esto asegura que solo se muestren las órdenes de la caja específica
		$cashDate = isset($data["date_created_cash"]) ? $data["date_created_cash"] : date("Y-m-d");
		$cashOffice = isset($data["id_office_cash"]) ? $data["id_office_cash"] : $_SESSION["admin"]->id_office_admin;

		$url = "orders?linkTo=date_created_order,id_office_order,method_order,status_order&equalTo=".$cashDate.",".$cashOffice.",efectivo,Completada";
		$method = "GET";
		$fields = array();
	
		$orders = CurlController::request($url,$method,$fields);

		if($orders->status == 200){

			// Inicializar en 0 si no existe valor previo
			if(!isset($data[$module->columns[$i]->title_column]) || empty($data[$module->columns[$i]->title_column])){
				$data[$module->columns[$i]->title_column] = 0;
			}

			foreach ($orders->results as $key => $value) {
				
				$data[$module->columns[$i]->title_column] += $value->total_order;
			}

		}

		$readonly = "readonly";

	}

	/*=============================================
	Diferencia
	=============================================*/

	if($module->columns[$i]->title_column == "diff_cash"){

		// Obtener la fecha de creación de la caja (no la fecha actual)
		// Esto asegura que solo se calculen los valores de la caja específica
		$cashDate = isset($data["date_created_cash"]) ? $data["date_created_cash"] : date("Y-m-d");
		$cashOffice = isset($data["id_office_cash"]) ? $data["id_office_cash"] : $_SESSION["admin"]->id_office_admin;

		$totalBills = 0;

		$url = "bills?linkTo=date_created_bill,id_office_bill&equalTo=".$cashDate.",".$cashOffice;
		$method = "GET";
		$fields = array();

		$bills = CurlController::request($url,$method,$fields);

		if($bills->status == 200){

			foreach ($bills->results as $key => $value) {
				
				$totalBills += $value->cost_bill;
			}

		}

		$totalOrders  = 0;

		$url = "orders?linkTo=date_created_order,id_office_order,method_order,status_order&equalTo=".$cashDate.",".$cashOffice.",efectivo,Completada";
		$method = "GET";
		$fields = array();

		$orders = CurlController::request($url,$method,$fields);

		if($orders->status == 200){

			foreach ($orders->results as $key => $value) {
				
				$totalOrders += $value->total_order;
			}

		}

		// Calcular diferencia: dinero inicial + ingresos - gastos
		$startCash = isset($data["start_cash"]) ? $data["start_cash"] : 0;
		$data[$module->columns[$i]->title_column] = $startCash + $totalOrders - $totalBills;

		$readonly = "readonly";

	}

	/*=============================================
	Brecha
	=============================================*/

	if($module->columns[$i]->title_column == "gap_cash"){

		$readonly = "readonly";
	}

}

?>

 	<input 
	type="number" 
	step="any"
	class="form-control rounded"
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
