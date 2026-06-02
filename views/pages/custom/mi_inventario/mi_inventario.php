<?php
$id_office = $_SESSION["admin"]->id_office_admin;
$id_admin = $_SESSION["admin"]->id_admin;
$role = $_SESSION["admin"]->rol_admin;

require_once "controllers/install.controller.php";
$db = InstallController::connect();

// hasSubWarehouse = true si el rol es vendedor O si el usuario tiene un sub-almacén asignado (cajero con sub-almacén)
$hasSubWarehouse = false;
if ($role === 'vendedor') {
    $hasSubWarehouse = true;
} else {
    // Verificar si existe un sub-almacén para este usuario
    try {
        $stmtHas = $db->prepare("SELECT COUNT(*) FROM sub_warehouses WHERE id_office_sub_warehouse = :office LIMIT 1");
        $stmtHas->execute([':office' => $id_office]);
        $hasSubWarehouse = (int)$stmtHas->fetchColumn() > 0;
    } catch (Exception $e) {
        $hasSubWarehouse = false;
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-box-open"></i> <?php echo (!$hasSubWarehouse) ? 'Inventario Almacén' : 'Mi Inventario'; ?></div>
                <div class="pe-0">
                    <ul class="nav justify-content-lg-end">
                        <li class="nav-item"><a class="nav-link py-0 px-0 text-dark" href="/">Inicio</a></li>
                        <li class="nav-item ps-3">/</li>
                        <li class="nav-item"><a class="nav-link py-0 disabled text-capitalize" href="#"><?php echo (!$hasSubWarehouse) ? 'Inventario Almacén' : 'Mi Inventario'; ?></a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Mi Inventario -->
        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <h6 class="mb-0"><i class="fas fa-boxes"></i> <?php echo (!$hasSubWarehouse) ? 'Productos en Almacén' : 'Productos en mi Sub-Almacén'; ?></h6>
                        <?php if($hasSubWarehouse): ?>
                        <a href="/solicitar_inventario" class="btn btn-sm backColor">
                            <i class="fas fa-plus-circle"></i> Solicitar más inventario
                        </a>
                        <?php endif; ?>
                    </div>
                    <div id="myInventoryContent">
                        <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando inventario...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de movimientos del sub-almacén -->
        <?php if($hasSubWarehouse): ?>
        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-history"></i> Últimos Movimientos de mi Sub-Almacén</h6>
                    <div id="myMovementsContent">
                        <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var idAdmin = <?php echo $id_admin ?>;
var idOffice = <?php echo $id_office ?>;
var userRole = "<?php echo $role ?>";
var hasSubWarehouse = <?php echo $hasSubWarehouse ? 'true' : 'false' ?>;

$(document).ready(function() {
    loadMyInventory();
    if(hasSubWarehouse){
        loadMyMovements();
    }
});

function loadMyInventory(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getSubWarehouseStock: true, id_admin: idAdmin, id_office: idOffice, role: userRole },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#myInventoryContent').html('<div class="text-center py-4 text-muted"><i class="fas fa-box-open fa-2x mb-2"></i><br>No tienes productos asignados.<br><a href="/solicitar_inventario" class="btn btn-sm backColor mt-2">Solicitar inventario</a></div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-bordered table-striped" id="myInvTable"><thead><tr><th>#</th><th>Producto</th><th>SKU</th><th>Unidad</th><th>Cantidad Disponible</th><th>Estado</th></tr></thead><tbody>';
            data.forEach(function(item, i){
                var stockClass = item.stock > 0 ? 'bg-success' : 'bg-danger';
                var statusText = item.stock > 0 ? 'Disponible' : 'Agotado';
                html += '<tr>';
                html += '<td>' + (i+1) + '</td>';
                html += '<td class="fw-bold">' + decodeURIComponent((item.title_product || '').replace(/\+/g, ' ')) + '</td>';
                html += '<td><span class="badge bg-secondary">' + (item.sku_product || '-') + '</span></td>';
                html += '<td>' + (item.unit_product || '-') + '</td>';
                html += '<td><span class="badge fs-6 ' + stockClass + '">' + item.stock + '</span></td>';
                html += '<td><span class="badge ' + stockClass + '">' + statusText + '</span></td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#myInventoryContent').html(html);

            // Init DataTable after rendering (idioma inline, sin CDN)
            setTimeout(function(){
                $('#myInvTable').DataTable({
                    "language": {
                        "emptyTable": "No hay datos",
                        "info": "Mostrando _START_ a _END_ de _TOTAL_",
                        "infoEmpty": "Mostrando 0 a 0 de 0",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "search": "Buscar:",
                        "zeroRecords": "Sin resultados",
                        "paginate": { "first": "Primero", "last": "Último", "next": "Siguiente", "previous": "Anterior" }
                    },
                    "order": [[4, "desc"]]
                });
            }, 100);
        }
    });
}

function loadMyMovements(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getMyWarehouseMovements: true, id_admin: idAdmin, id_office: idOffice },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#myMovementsContent').html('<div class="text-center py-4 text-muted">Sin movimientos registrados</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Cantidad</th><th>Notas</th></tr></thead><tbody>';
            data.forEach(function(m){
                var typeBadge = m.type_assignment == 'despacho' ? '<span class="badge bg-success">Recibido</span>' :
                               m.type_assignment == 'venta' ? '<span class="badge bg-danger">Venta</span>' :
                               '<span class="badge bg-warning text-dark">Devolución</span>';
                html += '<tr>';
                html += '<td>' + m.date_created_assignment + '</td>';
                html += '<td>' + typeBadge + '</td>';
                html += '<td>' + decodeURIComponent((m.title_product || '').replace(/\+/g, ' ')) + '</td>';
                html += '<td>' + m.qty_assignment + '</td>';
                html += '<td>' + (m.notes_assignment || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#myMovementsContent').html(html);
        }
    });
}
</script>
