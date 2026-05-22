<?php
require_once "controllers/curl.controller.php";
$rolesRes = CurlController::request("roles?select=name_role", "GET", array());
echo json_encode($rolesRes);
