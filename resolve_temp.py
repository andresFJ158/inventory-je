import re
import sys

def fix_cash_details():
    with open('views/modules/modals/cash-details.php', 'r', encoding='utf-8') as f:
        c = f.read()

    # We know HEAD has the new overrideHtml logic, shiwasmi has the dash.
    c = re.sub(
        r'<<<<<<< HEAD\r?\n(.*?)(</span>.*?)</div>`\r?\n\s*}\)\.join\(\'\'\)\r?\n\s*: \'<span.*?>.*?</span>\';\r?\n=======\r?\n.*?>—</span>\';\r?\n>>>>>>> shiwasmi',
        r'\1\2</div>`\n\t\t\t\t\t\t}).join(\'\')\n\t\t\t\t\t\t: \'<span class=\"text-muted small\">—</span>\';',
        c, flags=re.DOTALL
    )
    if '<<<<<<<' in c:
        c = re.sub(r'<<<<<<< HEAD\r?\n(.*?)=======\r?\n(.*?)>>>>>>> shiwasmi\r?\n?', r'\1', c, flags=re.DOTALL)

    with open('views/modules/modals/cash-details.php', 'w', encoding='utf-8') as f:
        f.write(c)

def fix_lab():
    with open('views/pages/custom/lab_inventario_final/lab_inventario_final.php', 'r', encoding='utf-8') as f:
        c2 = f.read()

    replacement = '''$id_office = $_SESSION["admin"]->id_office_admin;

// Fetch products for this office via product_inventory JOIN products
try {
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $dbName = getenv("DB_NAME") ?: "u228744577_pos";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") ?: "";
    $port = getenv("DB_PORT") ?: "3306";
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $db->exec("set names utf8");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("
        SELECT p.*, pi.stock_inventory as stock_product
        FROM products p
        INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product
        WHERE pi.id_office_inventory = :office AND pi.status_inventory = 1 AND p.is_compound_product = 1 AND p.status_product = 1
        ORDER BY p.id_product DESC
    ");
    $stmt->execute([':office' => $id_office]);
    $products = $stmt->fetchAll(PDO::FETCH_CLASS);
} catch (Exception $e) {
    $products = array();
}

// Obtener IDs de productos envasados que pasaron QC
$urlProd = "productions?select=id_packaged_product&linkTo=status_production,id_office_production&equalTo=completado,".$_SESSION["admin"]->id_office_admin;
$prodRes = CurlController::request($urlProd, "GET", array());
$packagedIds = array();
if ($prodRes->status == 200) {
    foreach($prodRes->results as $p) {
        if (!empty($p->id_packaged_product)) {
            $packagedIds[] = $p->id_packaged_product;
        }
    }
}
$packagedIds = array_unique($packagedIds);'''

    c2 = re.sub(r'<<<<<<< HEAD\r?\n.*?=======\r?\n.*?>>>>>>> shiwasmi', replacement, c2, flags=re.DOTALL)
    with open('views/pages/custom/lab_inventario_final/lab_inventario_final.php', 'w', encoding='utf-8') as f:
        f.write(c2)

def dump_pos_conflicts():
    with open('ajax/pos.ajax.php', 'r', encoding='utf-8') as f:
        c3 = f.read()
    
    conflicts = list(re.finditer(r'<<<<<<< HEAD\r?\n(.*?)=======\r?\n(.*?)>>>>>>> shiwasmi\r?\n?', c3, re.DOTALL))
    with open('pos_conflicts.txt', 'w', encoding='utf-8') as f:
        f.write(f'Total conflicts: {len(conflicts)}\n\n')
        for i, match in enumerate(conflicts):
            f.write(f'--- Conflict {i+1} ---\n')
            f.write('HEAD:\n' + match.group(1).strip() + '\n')
            f.write('SHIWASMI:\n' + match.group(2).strip() + '\n\n')

fix_cash_details()
fix_lab()
dump_pos_conflicts()
