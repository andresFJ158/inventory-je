<?php
$id_office = $_SESSION["admin"]->id_office_admin;

try {
    $host = getenv("DB_HOST") ?: "127.0.0.1";
    $dbName = getenv("DB_NAME") ?: "u228744577_pos";
    $user = getenv("DB_USER") ?: "root";
    $pass = getenv("DB_PASS") ?: "";
    $port = getenv("DB_PORT") ?: "3306";
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $db->exec("set names utf8");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If despachador has an assigned warehouse, use its synced id_office_warehouse
    if ($_SESSION["admin"]->rol_admin == 'despachador' && isset($_SESSION["admin"]->id_warehouse_admin) && $_SESSION["admin"]->id_warehouse_admin > 0) {
        $stmtWh = $db->prepare("SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1");
        $stmtWh->execute([':wh' => $_SESSION["admin"]->id_warehouse_admin]);
        $whOffice = $stmtWh->fetchColumn();
        if ($whOffice) {
            $id_office = (int)$whOffice;
        }
    }

    $stmt = $db->prepare("
        SELECT p.*, pi.stock_inventory as stock_product
        FROM products p
        INNER JOIN product_inventory pi ON pi.id_product_inventory = p.id_product
        WHERE pi.id_office_inventory = :office AND pi.status_inventory = 1
        ORDER BY p.id_product DESC
    ");
    $stmt->execute([':office' => $id_office]);
    $products = $stmt->fetchAll(PDO::FETCH_CLASS);
} catch (Exception $e) {
    $products = array();
}

// Fetch all admins (for sub-warehouse assignment across all offices)
$urlAdmins = "admins";
$method = "GET";
$fields = array();
$adminsRes = CurlController::request($urlAdmins, $method, $fields);
$admins = ($adminsRes->status == 200) ? $adminsRes->results : array();

// Fetch all offices for mapping office names
$urlOffices = "offices";
$officesRes = CurlController::request($urlOffices, "GET", array());
$officesList = ($officesRes->status == 200) ? $officesRes->results : array();
$officesMap = array();
foreach($officesList as $off) {
    $officesMap[$off->id_office] = urldecode($off->title_office);
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
                <div class="text-capitalize h5 ps-2"><i class="fas fa-warehouse"></i> Almacén Principal</div>
                <div class="pe-0">
                    <ul class="nav justify-content-lg-end">
                        <li class="nav-item"><a class="nav-link py-0 px-0 text-dark" href="/">Inicio</a></li>
                        <li class="nav-item ps-3">/</li>
                        <li class="nav-item"><a class="nav-link py-0 disabled text-capitalize" href="#">Almacén</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="col-12 mb-3">
            <ul class="nav nav-tabs" id="almacenTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stockPanel" type="button" role="tab">
                        <i class="fas fa-boxes"></i> Inventario Principal
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sub-tab" data-bs-toggle="tab" data-bs-target="#subPanel" type="button" role="tab">
                        <i class="fas fa-users"></i> Sub-Almacenes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="moves-tab" data-bs-toggle="tab" data-bs-target="#movesPanel" type="button" role="tab">
                        <i class="fas fa-exchange-alt"></i> Movimientos
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content col-12" id="almacenTabContent">
            <!-- TAB 1: Inventario Principal -->
            <div class="tab-pane fade show active" id="stockPanel" role="tabpanel">
                <div class="card rounded p-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="almacenTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>SKU</th>
                                        <th>Unidad</th>
                                        <th>Stock Total</th>
                                        <th>Asignado</th>
                                        <th>Disponible</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $index => $prod): 
                                        $stockTotal = (int)$prod->stock_product;
                                    ?>
                                    <tr data-id="<?php echo $prod->id_product ?>" data-stock="<?php echo $stockTotal ?>">
                                        <td><?php echo $index + 1 ?></td>
                                        <td class="fw-bold"><?php echo urldecode($prod->title_product) ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $prod->sku_product ?></span></td>
                                        <td><?php echo $prod->unit_product ?></td>
                                        <td class="stock-total-cell" data-product="<?php echo $prod->id_product ?>">
                                            <span class="badge fs-6 <?php echo $stockTotal > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                <?php echo $stockTotal ?>
                                            </span>
                                        </td>
                                        <td class="assigned-qty" data-product="<?php echo $prod->id_product ?>">
                                            <span class="badge fs-6 bg-info">...</span>
                                        </td>
                                        <td class="available-qty" data-product="<?php echo $prod->id_product ?>">
                                            <span class="badge fs-6 bg-primary">...</span>
                                        </td>
                                        <td>
                                            <?php if($stockTotal > 0): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Sin Stock</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btnAssign" 
                                                data-id="<?php echo $prod->id_product ?>"
                                                data-name="<?php echo urldecode($prod->title_product) ?>"
                                                data-stock="<?php echo $stockTotal ?>"
                                                <?php echo $stockTotal <= 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-share"></i> Asignar
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Sub-Almacenes -->
            <div class="tab-pane fade" id="subPanel" role="tabpanel">
                <div class="card rounded p-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <h6><i class="fas fa-users"></i> Sub-Almacenes de la Sucursal</h6>
                        </div>
                        <div id="subWarehousesList">
                            <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Movimientos -->
            <div class="tab-pane fade" id="movesPanel" role="tabpanel">
                <div class="card rounded p-3 border-0 shadow-sm">
                    <div class="card-body">
                        <h6><i class="fas fa-exchange-alt"></i> Últimos Movimientos</h6>
                        <div id="movementsList">
                            <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar a Sub-Almacén -->
<div class="modal fade" id="modalAssign" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header backColor">
                <h5 class="modal-title text-white"><i class="fas fa-share"></i> Asignar a Sub-Almacén</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignProductId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Producto</label>
                    <input type="text" class="form-control" id="assignProductName" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Stock Disponible en Almacén</label>
                    <input type="text" class="form-control" id="assignProductStock" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Usuario Destino *</label>
                    <select class="form-select" id="assignUserId" required>
                        <option value="">-- Seleccionar usuario --</option>
                        <?php foreach($admins as $adm): ?>
                            <?php if($adm->rol_admin != 'superadmin'): ?>
                            <?php $officeName = isset($officesMap[$adm->id_office_admin]) ? $officesMap[$adm->id_office_admin] : "Sin Sucursal"; ?>
                            <option value="<?php echo $adm->id_admin ?>"><?php echo urldecode($adm->name_admin) ?> (<?php echo $adm->rol_admin ?> - <?php echo htmlspecialchars($officeName, ENT_QUOTES, 'UTF-8') ?>)</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Cantidad *</label>
                    <input type="number" class="form-control" id="assignQty" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notas (opcional)</label>
                    <textarea class="form-control" id="assignNotes" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn backColor" id="btnConfirmAssign">
                    <i class="fas fa-check"></i> Confirmar Asignación
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var idOffice = <?php echo $id_office ?>;
var token = "<?php echo $_SESSION["admin"]->token_admin ?>";
var currentAdminId = <?php echo $_SESSION["admin"]->id_admin ?>;

$(document).ready(function() {
    // Init DataTable (idioma inline para evitar CORS con CDN externo)
    $('#almacenTable').DataTable({
        "language": {
            "decimal":        "",
            "emptyTable":     "No hay datos disponibles",
            "info":           "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty":      "Mostrando 0 a 0 de 0 registros",
            "infoFiltered":   "(filtrado de _MAX_ registros totales)",
            "infoPostFix":    "",
            "thousands":      ",",
            "lengthMenu":     "Mostrar _MENU_ registros",
            "loadingRecords": "Cargando...",
            "processing":     "Procesando...",
            "search":         "Buscar:",
            "zeroRecords":    "No se encontraron resultados",
            "paginate": {
                "first":      "Primero",
                "last":       "Último",
                "next":       "Siguiente",
                "previous":   "Anterior"
            },
            "aria": {
                "sortAscending":  ": activar para ordenar ascendente",
                "sortDescending": ": activar para ordenar descendente"
            }
        },
        "order": [[4, "desc"]]
    });

    // Load assigned quantities
    loadAssignedQuantities();
    
    // Load sub-warehouses on tab click
    $('#sub-tab').on('shown.bs.tab', function(){ loadSubWarehouses(); });
    $('#moves-tab').on('shown.bs.tab', function(){ loadMovements(); });

    // Open assign modal
    $(document).on('click', '.btnAssign', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        var stock = $(this).data('stock');
        var availableEl = $(`.available-qty[data-product="${id}"] span`);
        var available = parseInt(availableEl.text()) || stock;

        $('#assignProductId').val(id);
        $('#assignProductName').val(name);
        $('#assignProductStock').val(available);
        $('#assignQty').attr('max', available).val(1);
        $('#assignUserId').val('');
        $('#assignNotes').val('');
        $('#modalAssign').modal('show');
    });

    // Confirm assignment
    $('#btnConfirmAssign').on('click', function(){
        var productId = $('#assignProductId').val();
        var userId = $('#assignUserId').val();
        var qty = parseInt($('#assignQty').val());
        var notes = $('#assignNotes').val();
        var available = parseInt($('#assignProductStock').val());

        if(!userId){ toastr.error("Selecciona un usuario"); return; }
        if(!qty || qty <= 0){ toastr.error("Cantidad inválida"); return; }
        if(qty > available){ toastr.error("Cantidad excede el stock disponible"); return; }

        Swal.fire({
            title: '¿Confirmar asignación?',
            text: `Se asignarán ${qty} unidades al usuario seleccionado`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar'
        }).then(function(result){
            if(result.isConfirmed){
                $.ajax({
                    url: "/ajax/pos.ajax.php",
                    method: "POST",
                    data: {
                        assignToSubWarehouse: true,
                        id_product: productId,
                        id_admin_dest: userId,
                        qty: qty,
                        notes: notes,
                        id_office: idOffice,
                        id_dispatched_by: currentAdminId
                    },
                    success: function(res){
                        if(res.trim() == "ok"){
                            toastr.success("Producto asignado correctamente");
                            $('#modalAssign').modal('hide');
                            setTimeout(function(){ location.reload(); }, 1000);
                        } else {
                            toastr.error(res);
                        }
                    }
                });
            }
        });
    });
});

function loadAssignedQuantities(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getAssignedByOffice: true, id_office: idOffice, id_dispatcher: currentAdminId },
        dataType: "json",
        success: function(data){
            // data = [{id_product: X, total_assigned: Y}, ...]
            if(!data || !Array.isArray(data)) data = [];
            
            var assignedMap = {};
            data.forEach(function(item){
                assignedMap[item.id_product] = parseInt(item.total_assigned) || 0;
            });

            $('#almacenTable tbody tr').each(function(){
                var prodId = $(this).data('id');
                var mainStock = parseInt($(this).data('stock')) || 0;
                var assigned = assignedMap[prodId] || 0;
                var totalStock = mainStock + assigned;
                var available = mainStock;

                $(this).find('.stock-total-cell').html('<span class="badge fs-6 ' + (totalStock > 0 ? 'bg-success' : 'bg-danger') + '">' + totalStock + '</span>');
                $(this).find('.assigned-qty').html('<span class="badge fs-6 bg-info">' + assigned + '</span>');
                $(this).find('.available-qty').html('<span class="badge fs-6 bg-primary">' + available + '</span>');
                
                // Update assign button
                if(available <= 0){
                    $(this).find('.btnAssign').prop('disabled', true);
                }
            });
        }
    });
}

function loadSubWarehouses(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getSubWarehousesDetail: true, id_office: idOffice },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#subWarehousesList').html('<div class="text-center py-4 text-muted">No hay sub-almacenes registrados</div>');
                return;
            }
            var html = '';
            data.forEach(function(sw){
                var officeName = sw.title_office ? decodeURIComponent(sw.title_office) : 'Sin Sucursal';
                html += '<div class="card mb-3 border shadow-sm">';
                html += '<div class="card-header d-flex justify-content-between align-items-center">';
                html += '<strong><i class="fas fa-user"></i> ' + sw.name_admin + ' (' + officeName + ')</strong>';
                html += '<span class="badge bg-success">' + sw.name_sub_warehouse + '</span>';
                html += '</div>';
                html += '<div class="card-body p-0">';
                if(sw.products && sw.products.length > 0){
                    html += '<table class="table table-sm mb-0"><thead><tr><th>Producto</th><th>Stock Sub-Almacén</th></tr></thead><tbody>';
                    sw.products.forEach(function(p){
                        html += '<tr><td>' + decodeURIComponent((p.title_product || '').replace(/\+/g, ' ')) + '</td><td><span class="badge fs-6 ' + (p.stock > 0 ? 'bg-success' : 'bg-danger') + '">' + p.stock + '</span></td></tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<div class="text-center py-3 text-muted">Sin productos asignados</div>';
                }
                html += '</div></div>';
            });
            $('#subWarehousesList').html(html);
        }
    });
}

function loadMovements(){
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getWarehouseMovements: true, id_office: idOffice, id_dispatcher: currentAdminId },
        dataType: "json",
        success: function(data){
            if(!data || data.length == 0){
                $('#movementsList').html('<div class="text-center py-4 text-muted">No hay movimientos registrados</div>');
                return;
            }
            var html = '<table class="table table-sm table-striped"><thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Cantidad</th><th>Destino</th><th>Sucursal</th><th>Despachador</th><th>Notas</th></tr></thead><tbody>';
            data.forEach(function(m){
                var typeBadge = m.type_assignment == 'despacho' ? '<span class="badge bg-primary">Despacho</span>' : 
                               m.type_assignment == 'devolucion' ? '<span class="badge bg-warning text-dark">Devolución</span>' : 
                               '<span class="badge bg-danger">Venta</span>';
                var destOffice = m.office_name ? decodeURIComponent(m.office_name) : '-';
                html += '<tr>';
                html += '<td>' + m.date_created_assignment + '</td>';
                html += '<td>' + typeBadge + '</td>';
                html += '<td>' + decodeURIComponent((m.title_product || '').replace(/\+/g, ' ')) + '</td>';
                html += '<td>' + m.qty_assignment + '</td>';
                html += '<td>' + m.name_admin + '</td>';
                html += '<td>' + destOffice + '</td>';
                html += '<td>' + m.dispatcher_name + '</td>';
                html += '<td>' + (m.notes_assignment || '-') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            $('#movementsList').html(html);
        }
    });
}
</script>
