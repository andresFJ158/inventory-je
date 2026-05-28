<?php
session_start();
$_SESSION['admin'] = new stdClass();
$_SESSION['admin']->id_admin = 21; // Santiago Rivero
$_SESSION['admin']->rol_admin = 'editor';
$_SESSION['admin']->id_office_admin = 7; // Ventura Mall

$_POST['idOffice'] = 7;
$_POST['startAt'] = 0;
$_POST['limit'] = 10;
$_POST['category'] = 0;
$_POST['search'] = null;

require 'ajax/pos.ajax.php';
$ajax = new PosControllerAjax();
$ajax->idOffice = $_POST['idOffice'];
$ajax->startAt = $_POST['startAt'];
$ajax->limit = $_POST['limit'];
$ajax->category = $_POST['category'];
$ajax->search = $_POST['search'];
$ajax->getProducts();
?>
