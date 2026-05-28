<?php
try {
    $link = new PDO("mysql:host=pos-db;dbname=u228744577_pos", "root", "root");
    $stmt = $link->query("DESCRIBE purchases");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
