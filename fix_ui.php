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
        
        // 1. Modals
        $content = str_replace('modal-content border-0 shadow rounded-4">', 'modal-content border-0 shadow rounded-4 overflow-hidden">', $content);
        
        // 2. Search bars
        $searchOld = '<div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden" style="max-width: 250px; background-color: #fff;">';
        $searchNew = '<div class="input-group input-group-sm shadow-sm" style="max-width: 250px;">';
        $content = str_replace($searchOld, $searchNew, $content);
        
        $spanOld = '<span class="input-group-text bg-white border-end-0 text-muted pe-1"><i class="fas fa-search"></i></span>';
        $spanNew = '<span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 50rem; border-bottom-left-radius: 50rem;"><i class="fas fa-search"></i></span>';
        $content = str_replace($spanOld, $spanNew, $content);
        
        $inputOld = 'class="form-control border-start-0 ps-1 shadow-none" id="searchItem"';
        $inputNew = 'class="form-control border-start-0 shadow-none" id="searchItem" style="border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;"';
        $content = str_replace($inputOld, $inputNew, $content);
        
        file_put_contents($file, $content);
        echo "Fixed $file\n";
    }
}
