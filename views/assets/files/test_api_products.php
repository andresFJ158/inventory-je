<?php
chdir("/var/www/html/ajax");
require "../controllers/curl.controller.php";
$res = CurlController::request("relations?rel=products,categories&type=product,category&linkTo=id_office_product,status_product&equalTo=7,1", "GET", array());
echo json_encode($res);
