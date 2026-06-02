<?php
$id_office = $_SESSION["admin"]->id_office_admin;
$id_admin = $_SESSION["admin"]->id_admin;
$role = $_SESSION["admin"]->rol_admin;

if ($role == 'despachador' && isset($_SESSION["admin"]->id_warehouse_admin) && $_SESSION["admin"]->id_warehouse_admin > 0) {
    try {
        $host = getenv("DB_HOST") ?: "127.0.0.1";
        $dbName = getenv("DB_NAME") ?: "u228744577_pos";
        $user = getenv("DB_USER") ?: "root";
        $pass = getenv("DB_PASS") ?: "";
        $port = getenv("DB_PORT") ?: "3306";
        $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
        $db->exec("set names utf8");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmtWh = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
        $stmtWh->execute([':wh' => $_SESSION["admin"]->id_warehouse_admin]);
        $whOffice = $stmtWh->fetchColumn();
        if ($whOffice) {
            $id_office = (int)$whOffice;
        }
    } catch(Exception $e) {}
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
                <div class="text-capitalize h5 ps-2"><i class="fas fa-truck"></i> Centro de Despachos</div>
                <div class="pe-0">
                    <ul class="nav justify-content-lg-end">
                        <li class="nav-item"><a class="nav-link py-0 px-0 text-dark" href="/">Inicio</a></li>
                        <li class="nav-item ps-3">/</li>
                        <li class="nav-item"><a class="nav-link py-0 disabled text-capitalize" href="#">Despachos</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="col-12 mb-3">
            <ul class="nav nav-tabs" id="despachosTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pendingPanel" type="button" role="tab">
                        <i class="fas fa-clock"></i> Solicitudes Pendientes <span class="badge bg-danger" id="pendingCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#historyPanel" type="button" role="tab">
                        <i class="fas fa-history"></i> Historial
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content col-12" id="despachosTabContent">
            <!-- TAB 1: Solicitudes Pendientes -->
            <div class="tab-pane fade show active" id="pendingPanel" role="tabpanel">
                <div class="card rounded p-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div id="pendingRequestsList">
                            <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando solicitudes...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Historial -->
            <div class="tab-pane fade" id="historyPanel" role="tabpanel">
                <div class="card rounded p-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div id="historyList">
                            <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando historial...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Despachar -->
<div class="modal fade" id="modalDispatch" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header backColor">
                <h5 class="modal-title text-white"><i class="fas fa-truck"></i> Despachar Solicitud</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dispatchRequestId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Solicitante</label>
                    <input type="text" class="form-control" id="dispatchRequester" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Producto</label>
                    <input type="text" class="form-control" id="dispatchProduct" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Cantidad Solicitada</label>
                    <input type="text" class="form-control" id="dispatchQtyRequested" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Stock Disponible en Almacén</label>
                    <input type="text" class="form-control" id="dispatchAvailableStock" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Cantidad a Despachar *</label>
                    <input type="number" class="form-control" id="dispatchQty" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas del Despachador</label>
                    <textarea class="form-control" id="dispatchNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn backColor" id="btnConfirmDispatch">
                    <i class="fas fa-check"></i> Confirmar Despacho
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var idOffice = <?php echo $id_office ?>;
var currentAdminId = <?php echo $id_admin ?>;
var idWarehouseAdmin = <?php echo $_SESSION["admin"]->id_warehouse_admin ?? 0 ?>;

$(document).ready(function() {
    loadPendingRequests();
    $('#history-tab').on('shown.bs.tab', function(){ loadHistory(); });

    // Open dispatch modal
    $(document).on('click', '.btnDispatch', function(){
        var data = $(this).data();
        $('#dispatchRequestId').val(data.id);
        $('#dispatchRequester').val(data.requester);
        $('#dispatchProduct').val(data.product);
        $('#dispatchQtyRequested').val(data.qty);
        $('#dispatchAvailableStock').val(data.available);
        $('#dispatchQty').val(data.qty).attr('max', data.available);
        $('#dispatchNotes').val('');
        $('#modalDispatch').modal('show');
    });

    // Reject request
    $(document).on('click', '.btnReject', function(){
        var requestId = $(this).data('id');
        Swal.fire({
            title: 'Rechazar Solicitud',
            input: 'textarea',
            inputLabel: 'Motivo del rechazo',
            inputPlaceholder: 'Escribe el motivo...',
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancelar'
        }).then(function(result){
            if(result.isConfirmed){
                $.ajax({
                    url: "/ajax/pos.ajax.php",
                    method: "POST",
                    data: {
                        rejectRequest: true,
                        id_request: requestId,
                        notes_dispatcher: result.value || '',
                        id_dispatched_by: currentAdminId
                    },
                    success: function(res){
                        if(res.trim() == "ok"){
                            toastr.success("Solicitud rechazada");
                            loadPendingRequests();
                        } else {
                            toastr.error(res);
                        }
                    }
                });
            }
        });
    });

    // Confirm dispatch
    $('#btnConfirmDispatch').on('click', function(){
        var requestId = $('#dispatchRequestId').val();
        var qty = parseInt($('#dispatchQty').val());
        var notes = $('#dispatchNotes').val();
        var available = parseInt($('#dispatchAvailableStock').val());

        if(!qty || qty <= 0){ toastr.error("Cantidad inválida"); return; }
        if(qty > available){ toastr.error("Cantidad excede el stock disponible"); return; }

        $.ajax({
            url: "/ajax/pos.ajax.php",
            method: "POST",
            data: {
                dispatchRequest: true,
                id_request: requestId,
                qty_dispatch: qty,
                notes_dispatcher: notes,
                id_dispatched_by: currentAdminId,
                id_office: idOffice
            },
            success: function(res){
                if(res.trim() == "ok"){
                    toastr.success("Solicitud despachada correctamente");
                    $('#modalDispatch').modal('hide');
                    loadPendingRequests();
                } else {
                    toastr.error(res);
                }
            }
        });
    });
});

function loadPendingRequests(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getPendingRequests: true, id_office: idOffice, id_warehouse: idWarehouseAdmin },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#pendingRequestsList').html('<div class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2"></i><br>No hay solicitudes pendientes</div>');
                $('#pendingCount').text('0');
                return;
            }
            $('#pendingCount').text(data.length);
            var html = '<table class="table table-bordered table-striped"><thead><tr><th>Fecha</th><th>Solicitante</th><th>Producto</th><th>Cantidad</th><th>Stock Disponible</th><th>Notas</th><th>Acciones</th></tr></thead><tbody>';
            data.forEach(function(r){
                var decodedProduct = decodeURIComponent((r.title_product || '').replace(/\+/g, ' '));
                html += '<tr>';
                html += '<td>' + r.date_created_request + '</td>';
                html += '<td><strong>' + r.name_admin + '</strong></td>';
                html += '<td>' + decodedProduct + '</td>';
                html += '<td><span class="badge bg-info fs-6">' + r.qty_request + '</span></td>';
                html += '<td><span class="badge fs-6 ' + (r.available_stock > 0 ? 'bg-success' : 'bg-danger') + '">' + r.available_stock + '</span></td>';
                html += '<td>' + (r.notes_request || '-') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-sm btn-success btnDispatch me-1" data-id="' + r.id_request + '" data-requester="' + r.name_admin + '" data-product="' + decodedProduct + '" data-qty="' + r.qty_request + '" data-available="' + r.available_stock + '"><i class="fas fa-check"></i> Despachar</button>';
                html += '<button class="btn btn-sm btn-danger btnReject" data-id="' + r.id_request + '"><i class="fas fa-times"></i> Rechazar</button>';
                html += '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $('#pendingRequestsList').html(html);
        }
    });
}

function loadHistory(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getRequestHistory: true, id_office: idOffice, id_warehouse: idWarehouseAdmin },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#historyList').html('<div class="text-center py-4 text-muted">No hay historial de solicitudes</div>');
                return;
            }
            var html = '<table class="table table-bordered table-striped"><thead><tr><th>Fecha</th><th>Solicitante</th><th>Producto</th><th>Solicitado</th><th>Despachado</th><th>Estado</th><th>Notas</th></tr></thead><tbody>';
            data.forEach(function(r){
                var statusBadge = r.status_request == 'despachada' ? '<span class="badge bg-success">Despachada</span>' :
                                  r.status_request == 'rechazada' ? '<span class="badge bg-danger">Rechazada</span>' :
                                  '<span class="badge bg-warning text-dark">Pendiente</span>';
                html += '<tr>';
                html += '<td>' + r.date_created_request + '</td>';
                html += '<td>' + r.name_admin + '</td>';
                html += '<td>' + decodeURIComponent((r.title_product || '').replace(/\+/g, ' ')) + '</td>';
                html += '<td>' + r.qty_request + '</td>';
                html += '<td>' + (r.qty_dispatched_request || '-') + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td>' + (r.notes_dispatcher_request || r.notes_request || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $('#historyList').html(html);
        }
    });
}
</script>
