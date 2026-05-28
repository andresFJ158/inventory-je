<?php
session_start();
$_SESSION['admin'] = new stdClass();
$_SESSION['admin']->id_admin = 21;
$_SESSION['admin']->rol_admin = 'editor';
$_SESSION['admin']->id_office_admin = 7;
$_SERVER['REQUEST_URI'] = '/pos';
ob_start();
include 'views/template.php';
file_put_contents('pos_output.html', ob_get_clean());
echo "Done";
?>
