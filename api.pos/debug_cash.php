<?php
require 'controllers/curl.controller.php';
require 'controllers/template.controller.php';

$officeId = 4;
$today = date('Y-m-d');
$urlCashToday = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".$today.",1,".$officeId."&select=id_cash,start_cash,date_created_cash,id_office_cash,date_start_cash,date_end_cash,status_cash";
$cashResp = CurlController::request($urlCashToday, 'GET', array());
$row = $cashResp->results[0];
$cashRow = json_decode(json_encode($row), true);
list($tStart, $tEnd) = TemplateController::cashSessionTimeBounds($cashRow);
echo 'Start: ' . $tStart . ' End: ' . $tEnd . "\n";

$vendedores = CurlController::request('admins?linkTo=rol_admin&equalTo=vendedor&select=id_admin', 'GET', array());
$independentSellers = [];
foreach ($vendedores->results as $v) {
	$independentSellers[] = (int) $v->id_admin;
}
echo 'Independent sellers: ' . implode(',', $independentSellers) . "\n";

$urlOrders = TemplateController::ordersSessionApiUrl($officeId, $tStart, $tEnd);
echo 'URL Orders: ' . $urlOrders . "\n";
$orders = CurlController::request($urlOrders, 'GET', array());
echo 'Orders found: ' . count($orders->results) . "\n";
foreach($orders->results as $o){
    echo 'Order ID: ' . $o->id_order . ' Admin: ' . $o->id_admin_order . ' Status: ' . $o->status_order . ' Total: ' . $o->total_order . "\n";
    if (in_array((int)($o->id_admin_order ?? 0), $independentSellers)) { echo "SKIPPED DUE TO INDEPENDENT SELLER\n"; }
}
