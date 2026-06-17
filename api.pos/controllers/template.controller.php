<?php 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class TemplateController{

	/*=============================================
	Traemos la vista principal de la plantilla
	=============================================*/

	public function index(){

		include "views/template.php";
	
	}

	/*=============================================
	Ruta principal del sistema
	=============================================*/

	static public function path(){

		$protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") ? "https" : "http";
		$host = $_SERVER["HTTP_HOST"];
		$script = $_SERVER["SCRIPT_NAME"];
		$dir = str_replace("/index.php", "", $script);
		$dir = str_replace(array("/ajax", "/api.pos"), "", $dir);
		
		return $protocol . "://" . $host . $dir . "/";

	}


	/*=============================================
	Identificar el tipo de columna
	=============================================*/

	static public function typeColumn($value){

		if($value == "text" || 
		   $value == "textarea" ||
		   $value == "image" || 
		   $value == "video" ||
		   $value == "file" ||
		   $value == "link" ||
		   $value == "select" ||
		   $value == "array" ||
		   $value == "color" ||
		   $value == "password" || 
		   $value == "email"){

			$type = "TEXT NULL DEFAULT NULL";
		}

		if($value == "object"){

			$type = "TEXT NULL DEFAULT '{}'";
		}

		if($value == "json"){

			$type = "TEXT NULL DEFAULT '[]'";

		}

		if($value == "int" || $value == "relations" || $value == "order"){
	       
	       	$type = "INT NULL DEFAULT '0'";
		
		}

		if($value == "boolean"){
	       
	       	$type = "INT NULL DEFAULT '1'";
		
		}

		if($value == "double" || $value == "money"){
	       
	       	$type = "DOUBLE NULL DEFAULT '0'";
		
		}

		if($value == "date"){
	       	
	       	$type = "DATE NULL DEFAULT NULL";
	    
	    }

	    if($value == "time"){
	       	
	       	$type = "TIME NULL DEFAULT NULL";
	    
	    }

	    if($value == "datetime"){
	      	
	      	$type = "DATETIME NULL DEFAULT NULL";
	    
	    }

	    if($value == "timestamp"){
	      	
	      	$type = "TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
	    }

	    if($value == "code" || $value == "chatgpt"){

	       	$type = "LONGTEXT NULL DEFAULT NULL";
	    
	    }

	    return $type;

	}

	/*=============================================
	Función Reducir texto
	=============================================*/

	static public function reduceText($value, $limit){

		if(strlen($value) > $limit){

			$value = substr($value, 0, $limit)."...";
		}

		return $value;
	}

	/*=============================================
	Devuelva la miniatura de la lista
	=============================================*/

	static public function returnThumbnailList($value){

		$path = '';

		// Normalizar link_file para que use la ruta local si el dominio falla
		$link = $value->link_file;
		if (strpos($link, "/views/assets/files/") !== false) {
			$parts = explode("/views/assets/files/", $link);
			$link = TemplateController::path() . "views/assets/files/" . end($parts);
		}

		/*=============================================
		Capturar miniatura imagen
		=============================================*/

		if(explode("/",$value->type_file)[0] == "image"){

			$path = '<img src="'.$link.'" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';

		}

		/*=============================================
		Capturar miniatura video
		=============================================*/

		if(explode("/",$value->type_file)[0] == "video" && $value->id_folder_file != 4){

			if(explode("/",$value->type_file)[1] == "mp4"){

				$path = '<video class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">
				<source src="'.$link.'" type="'.$value->type_file.'">
				</video>';

			}else{

				$path = '<img src="/views/assets/img/multimedia.png" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';
			}

		}

		if(explode("/",$value->type_file)[0] == "video" && $value->id_folder_file == 4){

			$path = '<img src="'.$value->thumbnail_vimeo_file.'" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';

		}

		/*=============================================
		Capturar miniatura audio
		=============================================*/

		if(explode("/",$value->type_file)[0] == "audio"){

			$path = '<img src="/views/assets/img/multimedia.png" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';

		}

		/*=============================================
		Capturar miniatura pdf
		=============================================*/

		if(explode("/",$value->type_file)[1] == "pdf"){

			$path = '<img src="/views/assets/img/pdf.jpeg" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';
		}

		/*=============================================
		Capturar miniatura zip
		=============================================*/

		if(explode("/",$value->type_file)[1] == "zip"){

			$path = '<img src="/views/assets/img/zip.jpg" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;">';
		}

		return $path;
	}

	/*=============================================
	Devuelva la miniatura de la cuadrícula
	=============================================*/

	static public function returnThumbnailGrid($value){

		$path = '';

		// Normalizar link_file para que use la ruta local si el dominio falla
		$link = $value->link_file;
		if (strpos($link, "/views/assets/files/") !== false) {
			$parts = explode("/views/assets/files/", $link);
			$link = TemplateController::path() . "views/assets/files/" . end($parts);
		}

		/*=============================================
		Capturar miniatura imagen
		=============================================*/

		if(explode("/",$value->type_file)[0] == "image"){

			$path = '<img src="'.$link.'" class="rounded card-img-top w-100">';

		}

		/*=============================================
		Capturar miniatura video
		=============================================*/

		if(explode("/",$value->type_file)[0] == "video" && $value->id_folder_file != 4){

			if(explode("/",$value->type_file)[1] == "mp4"){

				$path = '<video class="rounded card-img-top w-100">
					<source src="'.$link.'" type="'.$value->type_file.'">
				</video>';

			}else{

				$path = '<img src="/views/assets/img/multimedia.png" class="rounded card-img-top w-100">';
			}

		}

		if(explode("/",$value->type_file)[0] == "video" && $value->id_folder_file == 4){

			$path = '<img src="'.$value->thumbnail_vimeo_file.'" class="rounded card-img-top w-100">';
			
		}

		/*=============================================
		Capturar miniatura audio
		=============================================*/

		if(explode("/",$value->type_file)[0] == "audio"){

			$path = '<img src="/views/assets/img/multimedia.png" class="rounded card-img-top w-100">';

		}

		/*=============================================
		Capturar miniatura pdf
		=============================================*/

 		if(explode("/",$value->type_file)[1] == "pdf"){

 			$path = '<img src="/views/assets/img/pdf.jpeg" class="rounded card-img-top w-100">';
 		}

 		/*=============================================
		Capturar miniatura zip
		=============================================*/

 		if(explode("/",$value->type_file)[1] == "zip"){

 			$path = '<img src="/views/assets/img/zip.jpg" class="rounded card-img-top w-100">';
 		}
	 		
		return $path;
	}

	/*=============================================
	Generar imagen fallback para productos
	=============================================*/
	static public function fallbackProductImage($sku, $title, $img = '') {
		if (!empty($img) && $img !== 'null' && $img !== 'NULL' && $img !== '[]' && $img !== '{}') {
			// Si es un JSON Array (fotos múltiples) sacamos la primera
			if (strpos($img, '[') === 0) {
				$decoded = json_decode($img, true);
				if (is_array($decoded) && count($decoded) > 0) {
					return $decoded[0];
				}
			}
			// Si no es JSON y tiene algo, devolvemos eso
			if (strpos($img, '[') === false && strpos($img, '{') === false) {
				return $img;
			}
		}

		// Generar avatar con iniciales o SKU
		$letter = 'P';
		if (!empty($sku)) {
			$letter = strtoupper(substr($sku, 0, 2));
		} elseif (!empty($title)) {
			$letter = strtoupper(substr($title, 0, 2));
		}
		
		$colors = ['#1abc9c', '#2ecc71', '#3498db', '#9b59b6', '#34495e', '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#2c3e50', '#f1c40f', '#e67e22', '#e74c3c', '#95a5a6', '#f39c12', '#d35400', '#c0392b', '#bdc3c7', '#7f8c8d'];
		$color = $colors[abs(crc32($sku . $title)) % count($colors)];

		return "https://ui-avatars.com/api/?name=" . urlencode($letter) . "&color=fff&background=" . substr($color, 1) . "&size=256&bold=true";
	}

	/*=============================================
	Función para generar códigos alfanuméricos aleatorios
	=============================================*/

	static public function genPassword($length){

		$chain = "0123456789abcdefghijklmnopqrstuvwxyz";

		$password = substr(str_shuffle($chain),0,$length);

		return $password;
	}

	/*=============================================
	Función para enviar correos electrónicos
	=============================================*/

	static public function sendEmail($subject, $email, $title, $message, $link){

		date_default_timezone_set("America/La_Paz");

		$mail = new PHPMailer;

		$mail->CharSet = 'utf-8';
		//$mail->Encoding = 'base64'; //Habilitar al subir el sistema a un hosting

		$mail->isMail();

		$mail->UseSendmailOptions = 0;

		$mail->setFrom("noreply@dashboard.com","CMS-BUILDER");

		$mail->Subject = $subject;

		$mail->addAddress($email);

		$mail->msgHTML('

			<div style="width:100%; background:#eee; position:relative; font-family:sans-serif; padding-top:40px; padding-bottom: 40px;">
	
				<div style="position:relative; margin:auto; width:600px; background:white; padding:20px">
					
					<center>
						
						<h3 style="font-weight:100; color:#999">'.$title.'</h3>

						<hr style="border:1px solid #ccc; width:80%">

						'.$message.'

						<a href="'.$link.'" target="_blank" style="text-decoration: none; mrgin-top:10px">

							<div style="line-height:25px; background:#000; width:60%; padding:10px; color:white; border-radius:5px">Haz clic aquí</div>

						</a>

						<hr style="border:1px solid #ccc; width:80%">

						<h5 style="font-weight:100; color:#999">Si no solicitó el envío de este correo, haga caso omiso de este mensaje.</h5>

					</center>

				</div>

			</div>	

		 ');

		$send = $mail->Send();

		if(!$send){

			return $mail->ErrorInfo;	
		
		}else{

			return "ok";

		}

	}

	/*=============================================
	Función para generar códigos numéricos aleatorios
	=============================================*/

	static public function genNumCode($length){

		$chain = "111222333444555666777888999";

		$numCode = substr(str_shuffle($chain),0,$length);

		return $numCode;

	}

	/*=============================================
	Validar no repetir transacción
	=============================================*/

	static public function transValidate($numCode){
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare("SELECT id_order FROM orders WHERE transaction_order = :code LIMIT 1");
			$stmt->execute([':code' => $numCode]);
			return $stmt->fetchColumn() === false; // true = no existe = válido
		} catch (Throwable $e) {
			return true; // Si falla la consulta, asumimos que es válido
		}
	}
}

?>