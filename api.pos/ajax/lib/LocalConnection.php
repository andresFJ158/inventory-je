<?php
require_once __DIR__ . '/SchemaMigrator.php';
class LocalConnection {
	private static $instance = null;
	static public function connect(){
		if(self::$instance instanceof PDO){ return self::$instance; }
		$host = getenv("DB_HOST") ?: "127.0.0.1";
		$db = getenv("DB_NAME") ?: "u228744577_pos";
		$user = getenv("DB_USER") ?: "root";
		$pass = getenv("DB_PASS") ?: "";
		$port = getenv("DB_PORT") ?: "3306";
		$link = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		]);
		$link->exec("SET NAMES utf8mb4");
		$link->exec("SET time_zone = '-04:00'");
		if (getenv("RUNTIME_SCHEMA") === "1" && !SchemaMigrator::isApplied($link)) {
			SchemaMigrator::run($link);
		}
		self::$instance = $link;
		return $link;
	}
}
