<?php
require_once "models/connection.php";
$cols = ['name_admin', 'surname_admin', 'email_admin', 'password_admin', 'rol_admin', 'id_office_admin', 'id_warehouse_admin', 'id_inventory_admin', 'pct_commission_admin', 'status_admin', 'permissions_admin'];
$res = Connection::getColumnsData('admins', $cols);
if(empty($res)) {
    echo "FAILED: Columns do not match database.\n";
} else {
    echo "SUCCESS: Columns match.\n";
}
