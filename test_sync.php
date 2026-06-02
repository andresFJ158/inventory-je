<?php
require_once 'controllers/curl.controller.php';
require_once 'controllers/template.controller.php';
require_once 'controllers/dynamic.controller.php';
$token = 'gdfhdfhsdfyeryr34646fhdfy4564t3456fhgdy'; // Using generic API token for testing

$today = date("Y-m-d");
$officeId = 5;

// Simulate what syncOpenCashTotalsForOffice does
$urlCashToday = "cashs?linkTo=date_created_cash,status_cash,id_office_cash&equalTo=".$today.",1,".$officeId."&select=id_cash,start_cash,date_created_cash,id_office_cash,date_start_cash,date_end_cash,status_cash";
$cashResp = CurlController::request($urlCashToday, "GET", array());

$row = null;
if(isset($cashResp->status) && $cashResp->status == 200 && !empty($cashResp->results)){
    $row = $cashResp->results[0];
}
echo "Row:\n";
print_r($row);

if($row !== null){
    $cashRow = json_decode(json_encode($row), true);
    list($tStart, $tEnd) = TemplateController::cashSessionTimeBounds($cashRow);
    
    echo "Bounds: $tStart -> $tEnd\n";

    $totalBills = 0.0;
    $urlBills = TemplateController::billsSessionApiUrl($officeId, $tStart, $tEnd);
    echo "URL Bills: $urlBills\n";
    $bills = CurlController::request($urlBills, "GET", array());
    print_r($bills);

    $totalOrders = 0.0;
    $urlOrders = TemplateController::ordersSessionApiUrl($officeId, $tStart, $tEnd);
    echo "URL Orders: $urlOrders\n";
    $orders = CurlController::request($urlOrders, "GET", array());
    print_r($orders);
}
