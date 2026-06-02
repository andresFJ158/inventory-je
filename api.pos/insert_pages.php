<?php
require_once __DIR__."/models/connection.php";
$db = Connection::connect();

$pages = [
    ['url_page' => 'lab_materiales', 'title_page' => 'Catálogo M.P.', 'icon_page' => 'fas fa-flask', 'type_page' => 'custom', 'order_page' => 10],
    ['url_page' => 'lab_entradas', 'title_page' => 'Entradas M.P.', 'icon_page' => 'fas fa-truck-loading', 'type_page' => 'custom', 'order_page' => 11],
    ['url_page' => 'lab_cif', 'title_page' => 'Costos Indirectos', 'icon_page' => 'fas fa-money-bill-wave', 'type_page' => 'custom', 'order_page' => 12],
    ['url_page' => 'lab_recetas', 'title_page' => 'Recetas', 'icon_page' => 'fas fa-scroll', 'type_page' => 'custom', 'order_page' => 13],
    ['url_page' => 'lab_produccion', 'title_page' => 'Producción', 'icon_page' => 'fas fa-industry', 'type_page' => 'custom', 'order_page' => 14],
];

foreach ($pages as $page) {
    $stmt = $db->prepare("SELECT id_page FROM pages WHERE url_page = :url");
    $stmt->execute([':url' => $page['url_page']]);
    if (!$stmt->fetch()) {
        $insert = $db->prepare("INSERT INTO pages (url_page, title_page, icon_page, type_page, order_page, date_created_page) VALUES (:url, :title, :icon, :type, :order, NOW())");
        $insert->execute([
            ':url' => $page['url_page'],
            ':title' => $page['title_page'],
            ':icon' => $page['icon_page'],
            ':type' => $page['type_page'],
            ':order' => $page['order_page']
        ]);
        echo "Página '{$page['title_page']}' registrada.\n";
    } else {
        echo "Página '{$page['title_page']}' ya existe.\n";
    }
}
