<?php
// Set up $_POST mock parameters
$_POST["dispatchRequest"] = true;
$_POST["id_request"] = 1;
$_POST["qty_dispatch"] = 4;
$_POST["notes_dispatcher"] = "Aprobado en test";
$_POST["id_dispatched_by"] = 19;
$_POST["id_office"] = 7;

// Include pos.ajax.php to execute the handler
require_once dirname(__DIR__) . '/ajax/pos.ajax.php';
