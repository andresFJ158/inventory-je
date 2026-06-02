<?php
require_once "controllers/curl.controller.php";
$url = "pages?orderBy=order_page&orderMode=ASC";
$pages = CurlController::request($url, "GET", array());
print_r($pages);
