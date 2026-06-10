<?php 

require_once "models/get.model.php";
require_once "models/post.model.php";
require_once "models/connection.php";

require_once "vendor/autoload.php";
use Firebase\JWT\JWT;

require_once "models/put.model.php";

class PostController{

	static public function postData($table, $data){

		$response = PostModel::postData($table, $data);
		
		$return = new PostController();
		$return -> fncResponse($response,null,null);

	}

	static public function postRegister($table, $data, $suffix){

		if(!isset($data["password_".$suffix]) || $data["password_".$suffix] === null || $data["password_".$suffix] === ""){

			$return = new PostController();
			$return -> fncResponse(null, "Password required", $suffix);
			return;
		}

		$data["password_".$suffix] = Connection::hashPassword($data["password_".$suffix]);

		$response = PostModel::postData($table, $data);

		$return = new PostController();
		$return -> fncResponse($response,null,$suffix);
	}

	static public function postLogin($table, $data, $suffix){

		$response = GetModel::getDataFilter($table, "*", "email_".$suffix, $data["email_".$suffix], null,null,null,null);
		
		if(!empty($response)){	

			if($response[0]->{"password_".$suffix} == null || $response[0]->{"password_".$suffix} === "")	{
				$return = new PostController();
				$return -> fncResponse(null, "Password required", $suffix);
				return;
			}
			
			if(!Connection::verifyPassword($data["password_".$suffix], $response[0]->{"password_".$suffix})){

				$return = new PostController();
				$return -> fncResponse(null, "Wrong password", $suffix);
				return;
			}

			$token = Connection::jwt($response[0]->{"id_".$suffix}, $response[0]->{"email_".$suffix});
			$jwt = JWT::encode($token, Connection::jwtSecret());

			$data = array(
				"token_".$suffix => $jwt,
				"token_exp_".$suffix => $token["exp"]
			);

			$update = PutModel::putData($table, $data, $response[0]->{"id_".$suffix}, "id_".$suffix);

			if(isset($update["comment"]) && $update["comment"] == "The process was successful" ){

				$response[0]->{"token_".$suffix} = $jwt;
				$response[0]->{"token_exp_".$suffix} = $token["exp"];

				$return = new PostController();
				$return -> fncResponse($response, null, $suffix);
				return;
			}

			$return = new PostController();
			$return -> fncResponse(null, "Token update failed", $suffix);
			return;

		}

		$return = new PostController();
		$return -> fncResponse(null, "Wrong email", $suffix);
	}

	public function fncResponse($response,$error,$suffix){

		if(!empty($response)){

			if(isset($response[0]->{"password_".$suffix})){

				unset($response[0]->{"password_".$suffix});

			}

			$json = array(
				'status' => 200,
				'results' => $response
			);

		}else{

			if($error != null){

				$json = array(
					'status' => 400,
					"results" => $error
				);

			}else{

				$json = array(
					'status' => 404,
					'results' => 'Not Found',
					'method' => 'post'
				);
			}

		}

		http_response_code($json["status"]);
		echo json_encode($json);
	}

}
