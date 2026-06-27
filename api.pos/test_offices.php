<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = "127.0.0.1";
$db = "u228744577_pos";
// let's try to find the db name if it's different.
// The connection in api.pos might be using a .env file or default.
require "models/connection.php";
try {
    $pdo = Connection::connect();
    $stmt = $pdo->query("SELECT * FROM offices LIMIT 10");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
