<?php
require_once "controllers/curl.controller.php";

$value = new stdClass();
$value->id_product = 29;
$value->discount_product = 0;

$method = null;
$fields = null;

$url = "purchases?linkTo=id_product_purchase&equalTo=".$value->id_product."&select=cost_purchase,date_created_purchase&orderBy=date_created_purchase&orderMode=DESC";

$price = CurlController::request($url,$method,$fields);
print_r($price);
