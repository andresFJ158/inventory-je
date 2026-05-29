<?php

require_once "../controllers/curl.controller.php";

class DynamicFormsController{

	public $matrix_column;
	public $id_column;
	public $pre_value;
	public $token;

	public function updateMatrixColumn(){

		$url = "columns?id=".$this->id_column."&nameId=id_column&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = "matrix_column=".$this->matrix_column;

		$updateMatrix = CurlController::request($url,$method,$fields);

		if($updateMatrix->status == 200){

			$url = "columns?linkTo=id_column&equalTo=".$this->id_column."&select=matrix_column";
			$method = "GET";
			$fields = array();

			$getMatrix = CurlController::request($url,$method,$fields);

			if($getMatrix->status == 200){

				$html = "";
				$count = 0;

				foreach (explode(",",$getMatrix->results[0]->matrix_column) as $key => $value) {

					$selected = "";

					if($this->pre_value != null){

						if($value == $this->pre_value){

							$selected = "selected";
						}
					}
					
					$html .= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
					
					$count++;

					if($count == count(explode(",",$getMatrix->results[0]->matrix_column))){

						echo $html;
					}

				}

			}

		}

	}

	/*=============================================
	Devolver información de la tabla
	=============================================*/

	public $table;

	public function getTable(){

		$url = "columns?id=".$this->id_column."&nameId=id_column&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = "matrix_column=".$this->table;

		$updateMatrix = CurlController::request($url,$method,$fields);

		if($updateMatrix->status == 200){

			$url = $this->table;
			$method = "GET";
			$fields = array();

			$columns = CurlController::request($url,$method,$fields);

			if($columns->status == 200){

				echo urldecode(json_encode($columns->results));
			
			}

		}
	}


	/*=============================================
	Actualizar matrix y Devolver consulta chatgpt
	=============================================*/

	public $matrix_prompt;
	public $id_prompt;

	public function updateMatrixPrompt(){

		$url = "columns?id=".$this->id_prompt."&nameId=id_column&token=".$this->token."&table=admins&suffix=admin";
		$method = "PUT";
		$fields = "matrix_column=".$this->matrix_prompt;

		$updateMatrix = CurlController::request($url,$method,$fields);

		if($updateMatrix->status == 200 && !empty($this->matrix_prompt)){

			/*=============================================
			Traer info del administrador
			=============================================*/

			$url = "admins?linkTo=token_admin&equalTo=".$this->token."&select=chatgpt_admin";
			$method = "GET";
			$fields = array();

			$admin = CurlController::request($url,$method,$fields);

			if($admin->status == 200){

				$content = $this->matrix_prompt;
				$chatgptConfig = json_decode($admin->results[0]->chatgpt_admin);
				$token = isset($chatgptConfig->token) ? $chatgptConfig->token : "";	
				$org = isset($chatgptConfig->org) ? $chatgptConfig->org : "";		

				$chatGPT = CurlController::chatGPT($content,$token,$org);
				
				echo $chatGPT;
			}

		}


	}

	/*=============================================
	Obtener productos por sucursal
	=============================================*/

	public $id_office;

	public function getProductsByOffice(){
		try {
			$host = getenv("DB_HOST") ?: "127.0.0.1";
			$dbName = getenv("DB_NAME") ?: "u228744577_pos";
			$user = getenv("DB_USER") ?: "root";
			$pass = getenv("DB_PASS") ?: "";
			$port = getenv("DB_PORT") ?: "3306";
			$db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
			$db->exec("set names utf8");
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$stmt = $db->prepare("
				SELECT p.id_product, p.title_product, p.sku_product 
				FROM products p
				ORDER BY p.title_product ASC
			");
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if(!empty($results)){
				echo json_encode(array("status" => 200, "results" => $results));
			} else {
				echo json_encode(array("status" => 404, "error" => "No products found"));
			}
		} catch (Exception $e) {
			echo json_encode(array("status" => 500, "error" => $e->getMessage()));
		}
	}

}

/*=============================================
Variables POST
=============================================*/ 

if(isset($_POST["matrix_column"])){

	$ajax = new DynamicFormsController();
	$ajax -> matrix_column = $_POST["matrix_column"];
	$ajax -> id_column = $_POST["id_column"];
	$ajax -> pre_value = $_POST["pre_value"];
	$ajax -> token = $_POST["token"];
	$ajax -> updateMatrixColumn(); 

}

if(isset($_POST["table"])){

	$ajax = new DynamicFormsController();
	$ajax -> table = $_POST["table"];
	$ajax -> id_column = $_POST["id_column"];
	$ajax -> token = $_POST["token"];
	$ajax -> getTable(); 

}

if(isset($_POST["matrix_prompt"])){

	$ajax = new DynamicFormsController();
	$ajax -> matrix_prompt = $_POST["matrix_prompt"];
	$ajax -> id_prompt = $_POST["id_prompt"];
	$ajax -> token = $_POST["token"];
	$ajax -> updateMatrixPrompt(); 

}

if(isset($_POST["id_office"])){

	$ajax = new DynamicFormsController();
	$ajax -> id_office = $_POST["id_office"];
	$ajax -> token = $_POST["token"];
	$ajax -> getProductsByOffice(); 

}
