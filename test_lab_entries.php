<?php
require 'api.pos/ajax/lib/LocalConnection.php';
require 'api.pos/load_env.php';

$db = LocalConnection::connect();
$_POST["getLabEntries"] = "ok";
$_POST["id_office"] = "7";

require 'api.pos/ajax/handlers/04_lab.php';
