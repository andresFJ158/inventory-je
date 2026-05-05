<?php 

class CurlController{

	/*=============================================
	Peticiones a la API
	=============================================*/	

	static public function request($url,$method,$fields){

		$curl = curl_init();
		
		$defaultApiUrl = (isset($_SERVER["SERVER_NAME"]) && ($_SERVER["SERVER_NAME"] == "localhost" || $_SERVER["SERVER_NAME"] == "127.0.0.1")) 
						 ? "http://api.pos.local" // O el puerto que use su api local
						 : "https://api.desarrolloweb24siete.com";

		$apiBaseUrl = getenv("API_BASE_URL") ?: $defaultApiUrl;
		$apiToken = getenv("API_AUTHORIZATION") ?: "gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy";

		curl_setopt_array($curl, array(
			CURLOPT_URL => rtrim($apiBaseUrl, '/').'/'.$url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => $fields,
			CURLOPT_HTTPHEADER => array(
				'Authorization: '.$apiToken
			),
		));

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curlError = curl_error($curl);

		curl_close($curl);

		if(!empty($curlError)){
			return (object) array(
				"status" => 500,
				"results" => null,
				"comment" => "Curl error: ".$curlError,
				"httpCode" => $httpCode
			);
		}

		$decodedResponse = json_decode($response);

		if($decodedResponse === null){
			return (object) array(
				"status" => ($httpCode > 0 ? $httpCode : 500),
				"results" => null,
				"comment" => "Invalid JSON response from API",
				"rawResponse" => $response
			);
		}

		if(!isset($decodedResponse->status)){
			$decodedResponse->status = ($httpCode > 0 ? $httpCode : 200);
		}

		return $decodedResponse;

	}


	/*=============================================
	Peticiones a la API de ChatGPT
	=============================================*/	

	static public function chatGPT($content,$token,$org = ""){

		$curl = curl_init();
		
		// Preparar headers - Organization ID es opcional
		$headers = array(
			'Authorization: Bearer '.$token,
			'Content-Type: application/json'
		);
		
		// Solo añadir Organization header si está configurado y no está vacío
		if(!empty($org) && trim($org) != ""){
			$headers[] = 'OpenAI-Organization: '.trim($org);
		}

		$payload = array(
			"model" => "gpt-3.5-turbo",
			"messages" => array(
				array(
					"role" => "user",
					"content" => $content
				)
			)
		);
		
		// Convertir a JSON con codificación correcta
		$jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS => $jsonPayload,
		  CURLOPT_HTTPHEADER => $headers,
		));

		$response = curl_exec($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curlError = curl_error($curl);
		
		curl_close($curl);
		
		if($curlError){
			throw new Exception('Error de conexión: '.$curlError);
		}
		
		$response = json_decode($response);
		
		if($httpCode != 200){
			$errorMsg = isset($response->error->message) ? $response->error->message : 'Error desconocido';
			throw new Exception('Error de API: '.$errorMsg);
		}
		
		if(!isset($response->choices[0]->message->content)){
			throw new Exception('Respuesta inválida de ChatGPT');
		}
		
		return $response->choices[0]->message->content;

	}

	/*=============================================
	Conexión a la impresora
	=============================================*/

	static public function ticketPrint($idOrder,$name,$cude){
	
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://....app/pos/printer/?order='.$idOrder."&name=".$name."&cude=".$cude,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);

		curl_close($curl);
		$response = json_decode($response);
		return $response;

	}

	/*=============================================
	API de Título Valor Colombia
	=============================================*/

	static public function apiTituloValor($url,$method,$fields){

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => $method,
		  CURLOPT_POSTFIELDS => $fields,
		  CURLOPT_HTTPHEADER => array(
		    'Authorization: OTl+Y29ycmVvLnR1dG...........................',
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		
		$response = json_decode($response);
		return $response;

	}


}
