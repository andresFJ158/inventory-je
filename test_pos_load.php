<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Run this from the root directory so ajax/pos.ajax.php's relative includes work.
// Wait, ajax/pos.ajax.php has `require_once "../controllers..."` which assumes it's being run from `ajax/` directory!
// So we MUST run from `ajax/` directory. That means our includes here must be `../controllers/` too!

require_once "../controllers/curl.controller.php";
require_once "../controllers/template.controller.php";
require_once "pos.ajax.php";

date_default_timezone_set("America/La_Paz");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate session for Santiago
$adminObj = new stdClass();
$adminObj->rol_admin = "cajero";
$adminObj->id_admin = 22;
$adminObj->id_office_admin = 7;
$_SESSION["admin"] = $adminObj;

$pos = new PosController();
$pos->limit = 10;
$pos->startAt = 0;
$pos->category = "all";
$pos->search = "";
$pos->idOffice = 7;
$_POST["sellerRole"] = "cajero";
$_POST["sellerId"] = 22;
$_POST["isWholesale"] = 0;

$pos->loadProducts();
