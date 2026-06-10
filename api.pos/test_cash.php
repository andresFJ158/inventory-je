<?php
require_once "ajax/lib/LocalConnection.php";

$dbCheck = LocalConnection::connect();
$idOffice = 3;

$stmtCheck = $dbCheck->prepare("SELECT id_cash FROM cashs WHERE id_office_cash = :office AND status_cash = 1 LIMIT 1");
$stmtCheck->execute([':office' => $idOffice]);
$result = $stmtCheck->fetch();

echo "Result for office $idOffice: ";
var_dump($result);

$stmtRole = $dbCheck->prepare("SELECT rol_admin FROM admins WHERE id_admin = :seller LIMIT 1");
$stmtRole->execute([':seller' => 14]);
$sellerRole = $stmtRole->fetchColumn();
echo "Seller role: $sellerRole\n";
