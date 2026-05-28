<?php
require 'controllers/curl.controller.php';
$resp = CurlController::request('products?linkTo=id_product&equalTo=35', 'GET', []);
echo $resp->results[0]->img_product;
?>
