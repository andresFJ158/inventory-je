<?php
// Set up $_POST mock parameters for manual assignment
$_POST["assignToSubWarehouse"] = true;
$_POST["id_product"] = 14;
$_POST["id_admin_dest"] = 18;
$_POST["qty"] = 5;
$_POST["notes"] = "Asignacion manual en test";
$_POST["id_office"] = 7;
$_POST["id_dispatched_by"] = 19;

// Include pos.ajax.php to execute the handler
require_once dirname(__DIR__) . '/ajax/pos.ajax.php';
