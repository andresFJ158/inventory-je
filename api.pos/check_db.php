<?php
$db = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
$stmt = $db->query('SHOW DATABASES');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
