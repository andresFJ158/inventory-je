<?php
$url = 'http://localhost/ajax/dynamic-tables.ajax.php'; // or test directly by instantiating the controller
require_once "ajax/dynamic-tables.ajax.php";
$ajax = new DynamicTablesController();
$ajax->contentModule = '{"title_module":"orders","suffix_module":"order","columns":[{"title_column":"id_order","type_column":"text","visible_column":1},{"title_column":"transaction_order","type_column":"text","visible_column":1},{"title_column":"id_admin_order","type_column":"relations","visible_column":1},{"title_column":"id_client_order","type_column":"relations","visible_column":1},{"title_column":"total_order","type_column":"money","visible_column":1}]}';
$ajax->orderBy = "id_order";
$ajax->orderMode = "DESC";
$ajax->limit = 10;
$ajax->page = 1;
$ajax->search = "daniel_fernandez";
$ajax->idOffice = 1;

ob_start();
$ajax->loadAjaxTable();
$output = ob_get_clean();

echo "Raw output:\n" . $output . "\n";
?>
