<?php
try {
    $db = new PDO('mysql:host=db;port=3306;dbname=u228744577_pos', 'root', 'root');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create incomes table
    $db->exec('CREATE TABLE IF NOT EXISTS incomes (
        id_income INT(11) NOT NULL AUTO_INCREMENT,
        concept_income TEXT DEFAULT NULL,
        amount_income DOUBLE DEFAULT 0,
        date_income TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        id_cash_income INT(11) DEFAULT 0,
        id_admin_income INT(11) DEFAULT 0,
        id_office_income INT(11) DEFAULT 0,
        date_created_income DATE DEFAULT NULL,
        date_updated_income TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id_income)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish_ci');
    echo "incomes table OK\n";

    // 2. Add id_cash_bill column to bills if not exists
    try {
        $db->exec('ALTER TABLE bills ADD COLUMN id_cash_bill INT(11) DEFAULT 0');
        echo "id_cash_bill column added to bills\n";
    } catch(Exception $e) {
        echo "id_cash_bill already exists in bills\n";
    }

    // 3. Add incomes module if not exists
    $stmtMod = $db->query("SELECT id_module FROM modules WHERE title_module = 'incomes'");
    if (!($rowMod = $stmtMod->fetch())) {
        $db->exec("INSERT INTO modules (type_module, title_module, suffix_module, editable_module, date_created_module) VALUES ('tables', 'incomes', 'income', 1, CURDATE())");
        $id_module = $db->lastInsertId();
        $cols = [
            ['concept_income', 'Concepto', 'text', ''],
            ['amount_income', 'Monto', 'money', ''],
            ['id_cash_income', 'Caja', 'text', ''],
            ['id_admin_income', 'Administrador', 'relations', 'admins'],
            ['id_office_income', 'Sucursal', 'relations', 'offices']
        ];
        foreach ($cols as $c) {
            $ins = $db->prepare('INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (?, ?, ?, ?, ?, CURDATE())');
            $ins->execute([$id_module, $c[0], $c[1], $c[2], $c[3]]);
        }
        echo "incomes module and columns seeded OK\n";
    } else {
        echo "incomes module already exists\n";
    }

    // 4. Add id_cash_bill to bills module columns if not exists
    $stmtBill = $db->query("SELECT id_module FROM modules WHERE title_module = 'bills'");
    if ($rowBill = $stmtBill->fetch()) {
        $id_module_bill = $rowBill['id_module'];
        $check = $db->prepare('SELECT id_column FROM columns WHERE title_column = ? AND id_module_column = ?');
        $check->execute(['id_cash_bill', $id_module_bill]);
        if (!$check->fetch()) {
            $ins = $db->prepare('INSERT INTO columns (id_module_column, title_column, alias_column, type_column, matrix_column, date_created_column) VALUES (?, ?, ?, ?, ?, CURDATE())');
            $ins->execute([$id_module_bill, 'id_cash_bill', 'Caja', 'text', '']);
            echo "id_cash_bill column added to bills module\n";
        } else {
            echo "id_cash_bill column already in bills module\n";
        }
    }

    echo "Migration complete!\n";
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
