<?php
$db = new PDO('mysql:host=127.0.0.1;dbname=test;charset=utf8mb4', 'root', '');
$stmt = $db->query('SHOW TABLES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
