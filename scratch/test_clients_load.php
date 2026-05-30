<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Requerir controladores necesarios
require_once "controllers/curl.controller.php";
require_once "controllers/template.controller.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simular sesión de cajero en Ventura Mall (Oficina 10)
$adminObj = new stdClass();
$adminObj->rol_admin = "cajero";
$adminObj->id_admin = 26;
$adminObj->id_office_admin = 10;
$_SESSION["admin"] = $adminObj;

// Definir objeto $module para "clients"
$module = new stdClass();
$module->id_module = 6;
$module->title_module = "clients";
$module->suffix_module = "client";
$module->width_module = "100";
$module->editable_module = 1;
$module->url_page = "clientes";

// Definir $routesArray
$routesArray = ["clientes"];

// Ejecutar la consulta inicial de columnas (igual que en tables.php)
$url = "columns?linkTo=id_module_column&equalTo=".$module->id_module;
$method = "GET";
$fields = array();
$columns = CurlController::request($url,$method,$fields);

if ($columns->status == 200) {
    $module->columns = $columns->results;
} else {
    $module->columns = array();
}

echo "=== COLUMNAS DEL MÓDULO ===\n";
foreach ($module->columns as $col) {
    echo "Column: " . $col->title_column . " | Visible: " . $col->visible_column . "\n";
}
echo "\n";

// Ejecutar la lógica de tables.php para cargar contenido
$limit = 10;
$totalPages = 0;
$totalData = 0;

if ($module->title_module == "products") {
	$url = "products?linkTo=date_created_product&between1=1970-01-01&between2=2030-01-01&orderBy=id_product&orderMode=DESC&startAt=0&endAt=".$limit;
} else {
	if($_SESSION["admin"]->id_office_admin == 0 || !in_array("id_office_".$module->suffix_module, array_column($module->columns, "title_column")) || $module->title_module == "clients"){
		$url = $module->title_module."?orderBy=id_".$module->suffix_module."&orderMode=DESC&startAt=0&endAt=".$limit;
	}else{
		$url = $module->title_module."?orderBy=id_".$module->suffix_module."&orderMode=DESC&startAt=0&endAt=".$limit."&linkTo=id_office_".$module->suffix_module."&equalTo=".$_SESSION["admin"]->id_office_admin;
	}
}

echo "URL generada para la tabla: " . $url . "\n";

$method = "GET";
$fields = array();

$table = CurlController::request($url,$method,$fields);

echo "Status de respuesta de la tabla: " . $table->status . "\n";
if ($table->status == 200) {
    echo "Total registros devueltos: " . count($table->results) . "\n";
    foreach ($table->results as $row) {
        $id_field = "id_" . $module->suffix_module;
        $name_field = "name_" . $module->suffix_module;
        echo "ID: " . $row->$id_field . " | Nombre: " . urldecode($row->$name_field) . " | Oficina: " . ($row->id_office_client ?? 'N/A') . "\n";
    }
} else {
    echo "Error o vacío: " . json_encode($table) . "\n";
}

// Simular AJAX
require_once "ajax/dynamic-tables.ajax.php";
$ajax = new DynamicTablesController();
$ajax->contentModule = json_encode($module);
$ajax->orderBy = "id_client";
$ajax->orderMode = "DESC";
$ajax->limit = 10;
$ajax->page = 1;
$ajax->rolAdmin = "cajero";
$ajax->search = "";
$ajax->between1 = "1969-12-31";
$ajax->between2 = "2026-05-30";
$ajax->idOffice = 10;

echo "\n=== SIMULANDO AJAX ===\n";
ob_start();
$ajax->loadAjaxTable();
$ajaxOutput = ob_get_clean();
$decodedAjax = json_decode($ajaxOutput);
if ($decodedAjax === null) {
    echo "Respuesta AJAX inválida (no es JSON): " . $ajaxOutput . "\n";
} else {
    echo "Total registros devueltos por AJAX: " . ($decodedAjax->totalData ?? 'N/A') . "\n";
    echo "HTML de la tabla generado:\n";
    echo strip_tags($decodedAjax->HTMLTable) . "\n";
}

echo "\n=== FIN ===\n";
