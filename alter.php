<?php
require 'api.pos/models/connection.php';
try {
    Connection::connect()->exec("ALTER TABLE admins ADD COLUMN type_seller VARCHAR(50) DEFAULT NULL;");
    echo "OK";
} catch (Exception $e) {
    echo $e->getMessage();
}
