<?php
try {
    $link = new PDO("mysql:host=pos-db;dbname=u228744577_pos", "root", "root");
    $stmt = $link->query("SELECT * FROM columns WHERE id_module_column = (SELECT id_module FROM modules WHERE title_module = 'purchases')");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
