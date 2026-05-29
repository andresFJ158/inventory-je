<?php
$files = [
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_entradas/lab_entradas.php',
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_produccion/lab_produccion.php',
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_calidad/lab_calidad.php',
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_inventario_final/lab_inventario_final.php',
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_materiales/lab_materiales.php',
    'c:/Users/sebas/Desktop/UniTech/views/pages/custom/lab_recetas/lab_recetas.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $searchOld = '<div class="input-group input-group-sm shadow-sm" style="max-width: 250px;">';
        $searchNew = '<div class="input-group input-group-sm shadow-sm flex-nowrap" style="max-width: 250px;">';
        $content = str_replace($searchOld, $searchNew, $content);
        
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
