<?php
require_once "ajax/pos.ajax.php";
try {
    $db = LocalConnection::connect();
    
    // Add incomes module
    $stmt = $db->query("SELECT id_module FROM modules WHERE title_module = 'incomes'");
    $id_module = 0;
    if ($row = $stmt->fetch()) {
        $id_module = $row['id_module'];
    } else {
        $db->exec("INSERT INTO modules (type_module, title_module, suffix_module, editable_module, date_created_module) VALUES ('tables', 'incomes', 'income', 1, CURDATE())");
        $id_module = $db->lastInsertId();
    }

    echo "Incomes module ID: $id_module\n";

    // Add incomes columns
    $columnsIncomes = [
        ['concept_income', 'Concepto', 'text', ''],
        ['amount_income', 'Monto', 'money', ''],
        ['date_income', 'Fecha', 'timestamp', ''],
        ['id_cash_income', 'Caja', 'text', ''],
        ['id_admin_income', 'Administrador', 'relations', 'admins'],
        ['id_office_income', 'Sucursal', 'relations', 'offices']
    ];

    foreach ($columnsIncomes as $col) {
        $check = $db->prepare("SELECT id_column FROM columns WHERE title_column = :title AND id_module_column = :mod");
        $check->execute([':title' => $col[0], ':mod' => $id_module]);
        if (!$check->fetch()) {
            $ins = $db->prepare("INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (:mod, :title, :alias, :type, :matrix, CURDATE())");
            $ins->execute([':mod' => $id_module, ':title' => $col[0], ':alias' => $col[1], ':type' => $col[2], ':matrix' => $col[3]]);
            echo "Inserted column {$col[0]} for incomes\n";
        }
    }

    // Add id_cash_bill to bills module
    $stmtBillMod = $db->query("SELECT id_module FROM modules WHERE title_module = 'bills'");
    if ($rowBill = $stmtBillMod->fetch()) {
        $id_module_bill = $rowBill['id_module'];
        $check = $db->prepare("SELECT id_column FROM columns WHERE title_column = 'id_cash_bill' AND id_module_column = :mod");
        $check->execute([':mod' => $id_module_bill]);
        if (!$check->fetch()) {
            $ins = $db->prepare("INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (:mod, 'id_cash_bill', 'Caja', 'text', '', CURDATE())");
            $ins->execute([':mod' => $id_module_bill]);
            echo "Inserted column id_cash_bill for bills\n";
        }
    }

    echo "Done migration.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
