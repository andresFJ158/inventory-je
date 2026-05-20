<?php
$role = $_SESSION["admin"]->rol_admin;

// Obtener todas las materias primas para el select
$urlMP = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$mpRes = CurlController::request($urlMP, "GET", array());
$materials = ($mpRes->status == 200) ? $mpRes->results : array();

// Obtener entradas
$urlEntradas = "raw_material_entries?linkTo=status_entry&equalTo=pendiente"; 
// Join con raw_materials para ver el nombre
$urlEntradas = "relations?rel=raw_material_entries,raw_materials,admins&type=entry,raw_material,admin&linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_entry&orderMode=DESC";
$entRes = CurlController::request($urlEntradas, "GET", array());
$entries = ($entRes->status == 200) ? $entRes->results : array();

?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-truck-loading"></i> Entradas de Materia Prima</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary btn-sm px-3 rounded backColor" onclick="openEntryModal()">Registrar Entrada</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Materia Prima</th>
                                    <th>Cantidad</th>
                                    <th>Lote Prov.</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($entries as $index => $entry): ?>
                                <tr>
                                    <td><?php echo $entry->id_entry ?></td>
                                    <td class="text-uppercase"><?php echo $entry->name_raw_material ?></td>
                                    <td><?php echo $entry->qty_entry ?> <span class="small text-muted"><?php echo $entry->unit_raw_material ?></span></td>
                                    <td><?php echo $entry->lot_number_entry ?></td>
                                    <td><?php echo $entry->supplier_entry ?></td>
                                    <td><?php echo $entry->date_entry ?></td>
                                    <td>
                                        <?php if($entry->status_entry == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aprobado</span><br>
                                            <small class="text-muted">Bs <?php echo $entry->unit_price_entry ?> c/u</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($entry->status_entry == 'pendiente' && ($role == 'lab_admin' || $role == 'superadmin' || $role == 'admin')): ?>
                                            <button class="btn btn-sm btn-success rounded" onclick="openApproveModal(<?php echo $entry->id_entry ?>, '<?php echo $entry->name_raw_material ?>', <?php echo $entry->qty_entry ?>, '<?php echo $entry->unit_raw_material ?>', <?php echo $entry->id_raw_material_entry ?>)">
                                                Aprobar y Costear
                                            </button>
                                        <?php endif; ?>
                                    </td>
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

<!-- Modal para Registrar Entrada -->
<div class="modal fade" id="modalEntry" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Registrar Entrada MP</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEntry">
            <input type="hidden" id="id_admin_entry" value="<?php echo $_SESSION["admin"]->id_admin ?>">
            
            <div class="mb-3">
                <label>Materia Prima</label>
                <select class="form-select" id="id_raw_material_entry" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($materials as $mp): ?>
                        <option value="<?php echo $mp->id_raw_material ?>"><?php echo $mp->name_raw_material ?> (<?php echo $mp->unit_raw_material ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Cantidad Recibida</label>
                <input type="number" step="0.01" class="form-control" id="qty_entry" required>
            </div>
            <div class="mb-3">
                <label>Número de Lote (Proveedor)</label>
                <input type="text" class="form-control" id="lot_number_entry">
            </div>
            <div class="mb-3">
                <label>Proveedor</label>
                <input type="text" class="form-control" id="supplier_entry">
            </div>
            <div class="mb-3">
                <label>Fecha de Llegada</label>
                <input type="date" class="form-control" id="date_entry" value="<?php echo date('Y-m-d') ?>" required>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary backColor" onclick="saveEntry()">Registrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Aprobar y Costear (Solo Admin) -->
<div class="modal fade" id="modalApprove" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Aprobar y Costear Entrada</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info">
            <strong>Producto:</strong> <span id="approveName"></span><br>
            <strong>Cantidad:</strong> <span id="approveQty"></span> <span id="approveUnit"></span>
        </div>
        <form id="formApprove">
            <input type="hidden" id="approve_id_entry">
            <input type="hidden" id="approve_id_raw_material">
            <input type="hidden" id="approve_qty">
            
            <div class="mb-3">
                <label>Precio Unitario (Bs por unidad)</label>
                <input type="number" step="0.01" class="form-control" id="approve_unit_price" required onkeyup="calcTotalApprove()">
            </div>
            <div class="mb-3">
                <label>Costo Total (Calculado)</label>
                <input type="text" class="form-control" id="approve_total" readonly>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" onclick="approveEntry()">Aprobar Entrada</button>
      </div>
    </div>
  </div>
</div>

<script>
function openEntryModal() {
    $('#formEntry')[0].reset();
    $('#modalEntry').modal('show');
}

function saveEntry() {
    var id_mp = $('#id_raw_material_entry').val();
    var qty = $('#qty_entry').val();
    var lot = $('#lot_number_entry').val();
    var supplier = $('#supplier_entry').val();
    var date = $('#date_entry').val();
    var id_admin = $('#id_admin_entry').val();

    if(!id_mp || !qty || !date) {
        fncToastr("error", "Complete los campos obligatorios");
        return;
    }

    var fields = {
        id_raw_material_entry: id_mp,
        qty_entry: qty,
        lot_number_entry: lot,
        supplier_entry: supplier,
        date_entry: date,
        id_admin_entry: id_admin,
        status_entry: "pendiente"
    };

    var payload = new URLSearchParams();
    payload.append("apiProxy", "ok");
    payload.append("url", "raw_material_entries?token=" + localStorage.getItem("tokenAdmin") + "&table=admins&suffix=admin");
    payload.append("method", "POST");
    payload.append("fields", JSON.stringify(fields));

    fncSweetAlert("loading", "Guardando...", "");

    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: payload.toString(),
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        success: function(response) {
            fncSweetAlert("close", "", "");
            try {
                var res = typeof response === "string" ? JSON.parse(response) : response;
                if(res.status == 200) {
                    fncToastr("success", "Entrada registrada");
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    fncToastr("error", "Error al guardar");
                }
            } catch(e) {
                fncToastr("error", "Respuesta inválida");
            }
        },
        error: function(err) {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error de comunicación con el servidor");
        }
    });
}

function openApproveModal(id, name, qty, unit, id_raw_material) {
    $('#approve_id_entry').val(id);
    $('#approve_id_raw_material').val(id_raw_material);
    $('#approve_qty').val(qty);
    $('#approveName').text(name);
    $('#approveQty').text(qty);
    $('#approveUnit').text(unit);
    $('#approve_unit_price').val('');
    $('#approve_total').val('');
    $('#modalApprove').modal('show');
}

function calcTotalApprove() {
    var qty = parseFloat($('#approve_qty').val());
    var price = parseFloat($('#approve_unit_price').val());
    if(!isNaN(qty) && !isNaN(price)) {
        $('#approve_total').val((qty * price).toFixed(2));
    } else {
        $('#approve_total').val('');
    }
}

function approveEntry() {
    var id_entry = $('#approve_id_entry').val();
    var id_raw_material = $('#approve_id_raw_material').val();
    var qty = parseFloat($('#approve_qty').val());
    var price = parseFloat($('#approve_unit_price').val());
    var total = parseFloat($('#approve_total').val());
    var id_admin = $('#id_admin_entry').val();

    if(isNaN(price) || price <= 0) {
        fncToastr("error", "Ingrese un precio válido");
        return;
    }

    fncSweetAlert("loading", "Aprobando y actualizando stock...", "");

    // 1. Actualizar entrada
    var today = new Date().toISOString().split('T')[0];
    
    var fields = {
        unit_price_entry: price,
        total_cost_entry: total,
        status_entry: "aprobado",
        id_approved_by_entry: id_admin,
        date_approved_entry: today
    };
    
    var payload = new URLSearchParams();
    payload.append("apiProxy", "ok");
    payload.append("url", "raw_material_entries?id=" + id_entry + "&nameId=id_entry&token=" + localStorage.getItem("tokenAdmin"));
    payload.append("method", "PUT");
    payload.append("fields", JSON.stringify(fields));

    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: payload.toString(),
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        success: function(response) {
            try {
                var res = typeof response === "string" ? JSON.parse(response) : response;
                if(res.status == 200) {
                    // 2. Actualizar stock en raw_materials
                    updateStockInBackend(id_raw_material, qty);
                } else {
                    fncSweetAlert("close", "", "");
                    fncToastr("error", "Error al aprobar la entrada");
                }
            } catch(e) {
                fncSweetAlert("close", "", "");
                fncToastr("error", "Respuesta inválida");
            }
        },
        error: function(err) {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error de comunicación con el servidor");
        }
    });
}

function updateStockInBackend(id_raw_material, qty) {
    // Usaremos un endpoint AJAX en pos.ajax.php para ser atómicos o hacer fetch
    var data = new FormData();
    data.append("updateLabStock", "ok");
    data.append("id_raw_material", id_raw_material);
    data.append("qty", qty);
    data.append("token", localStorage.getItem("tokenAdmin"));

    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: data,
        contentType: false,
        cache: false,
        processData: false,
        success: function(response) {
            fncSweetAlert("close", "", "");
            fncToastr("success", "Entrada aprobada y stock actualizado");
            setTimeout(() => { location.reload(); }, 1000);
        },
        error: function() {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error al actualizar stock");
        }
    });
}
</script>
