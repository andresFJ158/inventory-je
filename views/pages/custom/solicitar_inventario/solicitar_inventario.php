<?php
$id_office = $_SESSION["admin"]->id_office_admin;
$id_admin = $_SESSION["admin"]->id_admin;

try {
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $dbName = getenv("DB_NAME") ?: "u228744577_pos";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") ?: "";
    $port = getenv("DB_PORT") ?: "3306";
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $db->exec("set names utf8");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmtWH = $db->prepare("SELECT id_warehouse, title_warehouse FROM warehouses ORDER BY id_warehouse DESC");
    $stmtWH->execute();
    $warehouses = $stmtWH->fetchAll(PDO::FETCH_CLASS);
} catch (Exception $e) {
    $warehouses = array();
}
$products = array();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-clipboard-list"></i> Solicitar Inventario</div>
                <div class="pe-0">
                    <ul class="nav justify-content-lg-end">
                        <li class="nav-item"><a class="nav-link py-0 px-0 text-dark" href="/">Inicio</a></li>
                        <li class="nav-item ps-3">/</li>
                        <li class="nav-item"><a class="nav-link py-0 disabled text-capitalize" href="#">Solicitar Inventario</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Formulario de Solicitud -->
        <div class="col-12 col-lg-5 mb-3">
            <div class="card rounded border-0 shadow-sm">
                <div class="card-header backColor">
                    <h6 class="mb-0 text-white"><i class="fas fa-plus-circle"></i> Nueva Solicitud</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Almacén *</label>
                        <select class="form-select" id="requestWarehouseId" required>
                            <option value="">-- Seleccionar almacén --</option>
                            <?php foreach($warehouses as $wh): ?>
                                <option value="<?php echo $wh->id_warehouse ?>">
                                    <?php echo urldecode($wh->title_warehouse) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Producto *</label>
                        <select class="form-select" id="requestProductId" disabled required>
                            <option value="">-- Seleccionar producto --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cantidad *</label>
                        <input type="number" class="form-control" id="requestQty" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="requestNotes" rows="2" placeholder="Justificación de la solicitud..."></textarea>
                    </div>
                    <button class="btn backColor w-100" id="btnSendRequest">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitud
                    </button>
                </div>
            </div>
        </div>

        <!-- Mis Solicitudes -->
        <div class="col-12 col-lg-7 mb-3">
            <div class="card rounded border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-list"></i> Mis Solicitudes</h6>
                </div>
                <div class="card-body">
                    <div id="myRequestsList">
                        <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var idOffice = <?php echo $id_office ?>;
var idAdmin = <?php echo $id_admin ?>;

$(document).ready(function() {
    loadMyRequests();

    // Load products when warehouse changes
    $('#requestWarehouseId').on('change', function(){
        var warehouseId = $(this).val();
        var productSelect = $('#requestProductId');
        productSelect.html('<option value="">-- Seleccionar producto --</option>');
        productSelect.prop('disabled', true);
        $('#requestQty').val('').removeAttr('max');

        if(warehouseId){
            $.ajax({
                url: "/ajax/pos.ajax.php",
                method: "POST",
                data: { getWarehouseProducts: true, id_warehouse: warehouseId },
                dataType: "json",
                success: function(response){
                    if(response && response.length > 0){
                        response.forEach(function(prod){
                            productSelect.append('<option value="' + prod.id_product + '" data-stock="' + prod.stock + '">' + decodeURIComponent(prod.title_product.replace(/\+/g, ' ')) + ' (Stock: ' + prod.stock + ')</option>');
                        });
                        productSelect.prop('disabled', false);
                    } else {
                        toastr.warning("Este almacén no tiene productos con stock disponible.");
                    }
                }
            });
        }
    });

    // Update max qty when product changes
    $('#requestProductId').on('change', function(){
        var stock = $(this).find(':selected').data('stock') || 0;
        $('#requestQty').attr('max', stock).val('');
    });

    // Send request
    $('#btnSendRequest').on('click', function(){
        var warehouseId = $('#requestWarehouseId').val();
        var productId = $('#requestProductId').val();
        var qty = parseInt($('#requestQty').val());
        var notes = $('#requestNotes').val();

        if(!warehouseId){ toastr.error("Selecciona un almacén"); return; }
        if(!productId){ toastr.error("Selecciona un producto"); return; }
        if(!qty || qty <= 0){ toastr.error("Ingresa una cantidad válida"); return; }

        Swal.fire({
            title: '¿Enviar solicitud?',
            text: 'Se enviará la solicitud al despachador para su aprobación',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, enviar',
            cancelButtonText: 'Cancelar'
        }).then(function(result){
            if(result.isConfirmed){
                $.ajax({
                    url: "/ajax/pos.ajax.php",
                    method: "POST",
                    data: {
                        createInventoryRequest: true,
                        id_product: productId,
                        qty: qty,
                        notes: notes,
                        id_admin: idAdmin,
                        id_office: idOffice,
                        id_warehouse: warehouseId
                    },
                    success: function(res){
                        if(res.trim() == "ok"){
                            toastr.success("Solicitud enviada correctamente");
                            $('#requestWarehouseId').val('');
                            $('#requestProductId').html('<option value="">-- Seleccionar producto --</option>').prop('disabled', true);
                            $('#requestQty').val('');
                            $('#requestNotes').val('');
                            loadMyRequests();
                        } else {
                            toastr.error(res);
                        }
                    }
                });
            }
        });
    });
});

function loadMyRequests(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getMyRequests: true, id_admin: idAdmin },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#myRequestsList').html('<div class="text-center py-4 text-muted">No tienes solicitudes registradas</div>');
                return;
            }
            var html = '<div class="table-responsive"><table class="table table-bordered table-striped" id="myRequestsTable"><thead><tr><th>Fecha</th><th>Almacén</th><th>Producto</th><th>Solicitado</th><th>Despachado</th><th>Estado</th><th>Notas</th></tr></thead><tbody>';
            data.forEach(function(r){
                var statusBadge = '';
                if(r.status_request == 'pendiente') statusBadge = '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendiente</span>';
                else if(r.status_request == 'despachada') statusBadge = '<span class="badge bg-success"><i class="fas fa-check"></i> Despachada</span>';
                else if(r.status_request == 'rechazada') statusBadge = '<span class="badge bg-danger"><i class="fas fa-times"></i> Rechazada</span>';
                
                html += '<tr>';
                html += '<td>' + r.date_created_request + '</td>';
                html += '<td>' + decodeURIComponent(r.title_warehouse.replace(/\+/g, ' ')) + '</td>';
                html += '<td>' + decodeURIComponent(r.title_product.replace(/\+/g, ' ')) + '</td>';
                html += '<td><span class="badge bg-info">' + r.qty_request + '</span></td>';
                html += '<td>' + (r.qty_dispatched_request || '-') + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td>' + (r.notes_dispatcher_request || r.notes_request || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            $('#myRequestsList').html(html);
        }
    });
}
</script>
