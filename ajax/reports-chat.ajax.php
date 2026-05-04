<?php

require_once "../controllers/curl.controller.php";

/*=============================================
Chat con ChatGPT para Reportes
=============================================*/

class ReportsChatController {

	public $message;
	public $token;

	/*=============================================
	Obtener datos de reportes para contexto
	=============================================*/

	public function getReportsData($startDate, $endDate) {
		
		$method = "GET";
		$fields = array();
		
		// Obtener órdenes del rango de fechas
		if($startDate == $endDate){
			$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&linkTo=date_created_order&equalTo=".$startDate."&orderBy=id_order&orderMode=DESC";
		}else{
			$ordersUrl = "relations?rel=orders,clients,offices&type=order,client,office&between1=date_created_order&between2=".$startDate.",".$endDate."&orderBy=id_order&orderMode=DESC";
		}
		
		$orders = CurlController::request($ordersUrl, $method, $fields);
		
		if($orders->status == 200){
			$ordersData = $orders->results;
		}else{
			$ordersData = array();
		}
		
		// Calcular estadísticas
		$stats = [
			'total_orders' => count($ordersData),
			'total_amount' => 0,
			'total_subtotal' => 0,
			'total_discount' => 0,
			'completed_orders' => 0,
			'pending_orders' => 0,
			'by_office' => []
		];
		
		foreach ($ordersData as $order) {
			$stats['total_amount'] += floatval($order->total_order ?? 0);
			$stats['total_subtotal'] += floatval($order->subtotal_order ?? 0);
			$stats['total_discount'] += floatval($order->discount_order ?? 0);
			
			if(($order->status_order ?? '') == 'Completada'){
				$stats['completed_orders']++;
			}else{
				$stats['pending_orders']++;
			}
			
			// Agrupar por sucursal
			$officeId = $order->id_office_order ?? 0;
			$officeName = urldecode($order->title_office ?? 'Sin sucursal');
			
			if(!isset($stats['by_office'][$officeId])){
				$stats['by_office'][$officeId] = [
					'name' => $officeName,
					'total_orders' => 0,
					'total_amount' => 0
				];
			}
			
			$stats['by_office'][$officeId]['total_orders']++;
			$stats['by_office'][$officeId]['total_amount'] += floatval($order->total_order ?? 0);
		}
		
		$stats['avg_order'] = $stats['total_orders'] > 0 ? $stats['total_amount'] / $stats['total_orders'] : 0;
		
		return $stats;
	}

	/*=============================================
	Enviar mensaje a ChatGPT
	=============================================*/

	public function sendMessage() {
		
		// Obtener credenciales de ChatGPT del admin
		$url = "admins?linkTo=token_admin&equalTo=".$this->token."&select=chatgpt_admin";
		$method = "GET";
		$fields = array();
		
		$admin = CurlController::request($url, $method, $fields);
		
		if($admin->status != 200 || empty($admin->results)){
			echo json_encode([
				'status' => 'error',
				'message' => 'No se encontraron credenciales de ChatGPT. Por favor, configura tu API key en tu perfil de administrador.'
			]);
			return;
		}
		
		// Obtener el JSON de chatgpt_admin
		$chatgptJson = isset($admin->results[0]->chatgpt_admin) ? $admin->results[0]->chatgpt_admin : null;
		
		// Si el campo no existe o está vacío, retornar error
		if($chatgptJson === null || $chatgptJson === ""){
			echo json_encode([
				'status' => 'error',
				'message' => 'Credenciales de ChatGPT no configuradas. Por favor, configura tu API key en tu perfil de administrador.'
			]);
			return;
		}
		
		// Limpiar el JSON (eliminar espacios en blanco)
		$chatgptJson = trim($chatgptJson);
		
		// Decodificar JSON
		$chatgptConfig = json_decode($chatgptJson, false);
		
		// Verificar que el JSON se decodificó correctamente
		if($chatgptConfig === null){
			$jsonError = json_last_error();
			if($jsonError !== JSON_ERROR_NONE){
				echo json_encode([
					'status' => 'error',
					'message' => 'Error al leer las credenciales de ChatGPT: '.json_last_error_msg().'. Por favor, verifica tu configuración en el perfil.'
				]);
				return;
			}
		}
		
		// Verificar que es un objeto válido
		if(!is_object($chatgptConfig)){
			echo json_encode([
				'status' => 'error',
				'message' => 'Error: Las credenciales de ChatGPT no tienen el formato correcto. Por favor, verifica tu configuración en el perfil.'
			]);
			return;
		}
		
		// Verificar que el token existe y no está vacío
		if(!isset($chatgptConfig->token) || empty($chatgptConfig->token) || trim($chatgptConfig->token) === ""){
			echo json_encode([
				'status' => 'error',
				'message' => 'API Key de ChatGPT no configurada. Por favor, configura tu API key en tu perfil de administrador.'
			]);
			return;
		}
		
		// Organization ID es opcional, usar cadena vacía si no está configurado
		$orgId = isset($chatgptConfig->org) && !empty($chatgptConfig->org) && trim($chatgptConfig->org) !== "" ? trim($chatgptConfig->org) : "";
		
		// Obtener fechas del rango actual
		$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d', strtotime('first day of last month'));
		$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('last day of last month'));
		
		// Obtener datos de reportes
		$reportsData = $this->getReportsData($startDate, $endDate);
		
		// Preparar contexto para ChatGPT
		$context = "Eres un asistente de análisis de datos para un sistema POS. Aquí tienes los datos de reportes del período ".$startDate." al ".$endDate.":\n\n";
		$context .= "ESTADÍSTICAS GENERALES:\n";
		$context .= "- Total de órdenes: ".$reportsData['total_orders']."\n";
		$context .= "- Órdenes completadas: ".$reportsData['completed_orders']."\n";
		$context .= "- Órdenes pendientes: ".$reportsData['pending_orders']."\n";
		$context .= "- Total de ventas: Bs ".number_format($reportsData['total_amount'], 2, '.', ',')."\n";
		$context .= "- Subtotal: Bs ".number_format($reportsData['total_subtotal'], 2, '.', ',')."\n";
		$context .= "- Descuentos: Bs ".number_format($reportsData['total_discount'], 2, '.', ',')."\n";
		$context .= "- Promedio por orden: Bs ".number_format($reportsData['avg_order'], 2, '.', ',')."\n\n";
		
		if(!empty($reportsData['by_office'])){
			$context .= "VENTAS POR SUCURSAL:\n";
			foreach ($reportsData['by_office'] as $office) {
				$context .= "- ".$office['name'].": ".$office['total_orders']." órdenes, Bs ".number_format($office['total_amount'], 2, '.', ',')."\n";
			}
			$context .= "\n";
		}
		
		$context .= "Responde de manera clara y concisa en español. Solo usa los datos proporcionados. Si el usuario pregunta algo que no está en los datos, indícale que no tienes esa información.\n\n";
		$context .= "Pregunta del usuario: ".$this->message;
		
		// Enviar a ChatGPT
		try {
			$response = CurlController::chatGPT($context, $chatgptConfig->token, $orgId);
			
			if($response === false || empty($response)){
				throw new Exception('No se recibió respuesta de ChatGPT');
			}
			
			echo json_encode([
				'status' => 'success',
				'message' => $response
			]);
		} catch (Exception $e) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Error al comunicarse con ChatGPT: '.$e->getMessage()
			]);
		}
	}
}

/*=============================================
Procesar petición
=============================================*/

if(isset($_POST['message']) && isset($_POST['token'])){
	
	$chat = new ReportsChatController();
	$chat->message = $_POST['message'];
	$chat->token = $_POST['token'];
	$chat->sendMessage();
	
}