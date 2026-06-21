<?php
$_POST["getLoggedUser"] = "ok";
// Mock session
session_start();
$_SESSION["admin"] = new stdClass();
$_SESSION["admin"]->id_admin = 24;
$_SESSION["admin"]->name_admin = "vendedor";
$_SESSION["admin"]->rol_admin = "vendedor";
$_SESSION["admin"]->id_office_admin = 4;
$_SESSION["admin"]->id_warehouse_admin = 2;
$_SESSION["admin"]->permissions_admin = "{}";
$_SESSION["admin"]->token_admin = "fake_token";

require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/ajax/lib/LocalConnection.php';
require_once __DIR__ . '/ajax/handlers/05_session_auth.php';
