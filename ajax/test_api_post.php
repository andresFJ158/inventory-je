<?php
require_once "pos.ajax.php";
try {
    $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3ODAxMDIyMjMsImV4cCI6MTc4MDE4ODYyMywiZGF0YSI6eyJpZCI6MTgsImVtYWlsIjoibGFiX2FkbWluQHRlc3QuY29tIn19.L0tkUwEJ9Is0tFtjOA-ef-j2WpdwlPDbdjMNsNhH2lE";
    $url = "raw_material_entries?token=" . $token . "&table=admins&suffix=admin";
    $fields = [
        "id_raw_material_entry" => 1,
        "qty_entry" => 5,
        "lot_number_entry" => "LOT-001",
        "supplier_entry" => "Supplier Test",
        "date_entry" => "2026-05-30",
        "id_admin_entry" => 18,
        "status_entry" => "pendiente"
    ];
    
    $res = CurlController::request($url, "POST", http_build_query($fields));
    print_r($res);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
