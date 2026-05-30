<?php
// Mock POST parameters for request 5
$_POST["dispatchRequest"] = true;
$_POST["id_request"] = 5;
$_POST["qty_dispatch"] = 3;
$_POST["notes_dispatcher"] = "Despachando solicitud #5";
$_POST["id_dispatched_by"] = 19;
$_POST["id_office"] = 7;

// Include pos.ajax.php
require_once dirname(__DIR__) . '/ajax/pos.ajax.php';
