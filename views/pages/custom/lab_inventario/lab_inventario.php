<?php
$url = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$method = "GET";
$fields = array();
$materials = CurlController::request($url, $method, $fields);

if ($materials->status == 200) {
    $materials = $materials->results;
    foreach($materials as $mp) {
        $urlEntry = "raw_material_entries?linkTo=id_raw_material_entry,status_entry&equalTo=".$mp->id_raw_material.",aprobado&orderBy=id_entry&orderMode=DESC&startAt=0&endAt=1";
        $entryRes = CurlController::request($urlEntry, "GET", array());
        $mp->last_price = ($entryRes->status == 200 && !empty($entryRes->results)) ? $entryRes->results[0]->unit_price_entry : 0;
    }
} else {
    $materials = array();
}
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-boxes text-success me-2"></i> Inventario de Materia Prima</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="inventoryTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Unidad</th>
                                    <th>Stock Actual</th>
                                    <th>Último Costo Unit.</th>
                                    <th>Valor Estimado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materials as $index => $material): 
                                    if ($material->stock_raw_material <= 0) continue;
                                    
                                    $badgeClass = 'bg-secondary';
                                    $tipoLabel = 'Desconocido';
                                    if(isset($material->measure_type)) {
                                        if($material->measure_type == 'weight') { $badgeClass = 'bg-warning text-dark'; $tipoLabel = '<i class="fas fa-weight-hanging me-1"></i> Peso'; }
                                        else if($material->measure_type == 'volume') { $badgeClass = 'bg-info text-dark'; $tipoLabel = '<i class="fas fa-flask me-1"></i> Volumen'; }
                                        else if($material->measure_type == 'unit') { $badgeClass = 'bg-success'; $tipoLabel = '<i class="fas fa-box me-1"></i> Unidad'; }
                                    }
                                    $estimatedValue = $material->stock_raw_material * $material->last_price;
                                ?>
                                <tr>
                                    <td><?php echo $index + 1 ?></td>
                                    <td class="text-uppercase fw-bold"><?php echo $material->name_raw_material ?></td>
                                    <td><span class="badge <?php echo $badgeClass ?>"><?php echo $tipoLabel ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $material->unit_raw_material ?></span></td>
                                    <td>
                                        <span class="badge fs-6 <?php echo $material->stock_raw_material > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?php echo number_format($material->stock_raw_material, 2) ?>
                                        </span>
                                    </td>
                                    <td>Bs <?php echo number_format($material->last_price, 2) ?></td>
                                    <td class="text-primary fw-bold">Bs <?php echo number_format($estimatedValue, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#inventoryTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json",
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        "order": [[0, "asc"]] // Ordenar por ID por defecto
    });
});
</script>

