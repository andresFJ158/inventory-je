<?php
$role = $_SESSION["admin"]->rol_admin;

// Obtener todas las materias primas para el select
$urlMP = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$mpRes = CurlController::request($urlMP, "GET", array());
$materials = ($mpRes->status == 200) ? $mpRes->results : array();

// Obtener entradas
$urlEntradas = "relations?rel=raw_material_entries,raw_materials,admins&type=entry,raw_material,admin&linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_entry&orderMode=ASC";
$entRes = CurlController::request($urlEntradas, "GET", array());
$entries = ($entRes->status == 200) ? $entRes->results : array();

// Calcular cantidad de pendientes
$pendingCount = 0;
foreach($entries as $e) {
    if($e->status_entry == 'pendiente') $pendingCount++;
}
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-truck-loading text-success me-2"></i> Entradas de Materia Prima</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-secondary fs-5">Entradas registradas (<?php echo count($entries); ?>)</span>
                        <?php /* Permitir a lab_worker registrar */ ?>
                        <button class="btn btn-primary btn-sm px-3 rounded-pill backColor" onclick="openEntryModal()"><i class="fas fa-plus me-1"></i> Registrar Entrada</button>
                    </div>
                    <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                        <div class="btn-group btn-group-sm rounded-pill shadow-sm" role="group">
                            <button type="button" class="btn btn-outline-success active" id="btnFilterAll" onclick="filterEntries('todas')">Todas</button>
                            <button type="button" class="btn btn-outline-success" id="btnFilterPending" onclick="filterEntries('pendiente')">
                                Pendientes
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-1"><?php echo $pendingCount; ?></span>
                                <?php endif; ?>
                            </button>
                        </div>
                        <div class="input-group input-group-sm shadow-sm flex-nowrap" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 50rem; border-bottom-left-radius: 50rem;"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control border-start-0 shadow-none" id="searchItem" placeholder="Buscar entrada..." style="border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Materia Prima</th>
                                    <th>Cantidad</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Estado / Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($entries)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-0">
                                            <div class="empty-state">
                                                <i class="fas fa-truck-loading empty-state-icon"></i>
                                                <div class="empty-state-title">No hay entradas de materia prima</div>
                                                <div class="empty-state-description">Aún no se han registrado entradas de insumos en este laboratorio.</div>
                                                <button class="btn btn-primary btn-sm rounded backColor px-4" onclick="openEntryModal()">Registrar Entrada</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($entries as $index => $entry): ?>
                                    <tr>
                                        <td><?php echo $entry->id_entry ?></td>
                                        <td class="text-uppercase fw-bold"><?php echo $entry->name_raw_material ?></td>
                                        <td><?php echo $entry->qty_entry ?> <span class="small text-muted"><?php echo $entry->unit_raw_material ?></span></td>
                                        <td><?php echo $entry->supplier_entry ?></td>
                                        <td><?php echo date('d M Y, H:i', strtotime($entry->date_entry)) ?></td>
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-start">
                                                <?php if($entry->status_entry == 'pendiente'): ?>
                                                    <span class="badge bg-warning text-dark badge-pending-status"><i class="fas fa-clock me-1"></i>Pendiente</span>
                                                    <?php if($role == 'lab_admin' || $role == 'superadmin' || $role == 'admin'): ?>
                                                        <button class="btn btn-sm btn-success rounded px-3 mt-1" onclick="openApproveModal(<?php echo $entry->id_entry ?>, '<?php echo $entry->name_raw_material ?>', <?php echo $entry->qty_entry ?>, '<?php echo $entry->unit_raw_material ?>', <?php echo $entry->id_raw_material_entry ?>)">
                                                            Aprobar y Costear
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Aprobado</span>
                                                    <small class="text-secondary fw-semibold">Bs <?php echo number_format($entry->unit_price_entry, 2) ?> / <?php echo $entry->unit_raw_material ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header backColor" style="border-radius: 1rem 1rem 0 0;">
        <h5 class="modal-title text-white">Registrar Entrada MP</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEntry">
            <input type="hidden" id="id_admin_entry" value="<?php echo $_SESSION["admin"]->id_admin ?>">
            
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Materia Prima</label>
                <select class="form-select rounded-3" id="id_raw_material_entry" required onchange="updateEntryInput()">
                    <option value="" data-type="" data-unit="">Seleccione...</option>
                    <?php foreach($materials as $mp): ?>
                        <option value="<?php echo $mp->id_raw_material ?>" data-type="<?php echo isset($mp->measure_type) ? $mp->measure_type : 'unit' ?>" data-unit="<?php echo $mp->unit_raw_material ?>"><?php echo $mp->name_raw_material ?> (<?php echo $mp->unit_raw_material ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Cantidad Recibida</label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control rounded-start-3" id="qty_entry" required disabled placeholder="Seleccione materia prima primero">
                    <span class="input-group-text rounded-end-3" id="entry_unit_addon">--</span>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Número de Lote (Proveedor)</label>
                <input type="text" class="form-control rounded-3" id="lot_number_entry">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Proveedor</label>
                <input type="text" class="form-control rounded-3" id="supplier_entry">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Fecha de Llegada</label>
                <input type="date" class="form-control rounded-3" id="date_entry" value="<?php echo date('Y-m-d') ?>" required>
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
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header bg-success text-white" style="border-radius: 1rem 1rem 0 0;">
        <h5 class="modal-title">Aprobar y Costear Entrada</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-secondary py-2 mb-3">
            <strong>Producto:</strong> <span id="approveName" class="fw-bold text-uppercase"></span><br>
            <strong>Cantidad:</strong> <span id="approveQty" class="fw-bold"></span> <span id="approveUnit"></span>
        </div>
        <form id="formApprove">
            <input type="hidden" id="approve_id_entry">
            <input type="hidden" id="approve_id_raw_material">
            <input type="hidden" id="approve_qty">
            
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary" id="approve_unit_price_label">Precio Unitario (Bs por unidad)</label>
                <input type="number" step="0.01" class="form-control rounded-3" id="approve_unit_price" required onkeyup="calcTotalApprove()">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Costo Total (Calculado)</label>
                <input type="text" class="form-control rounded-3" id="approve_total" readonly>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success rounded px-3" onclick="approveEntry()">Aprobar Entrada</button>
      </div>
    </div>
  </div>
</div>

<script>
function updateEntryInput() {
    var selected = $('#id_raw_material_entry').find(':selected');
    var type = selected.data('type');
    var unit = selected.data('unit');
    var input = $('#qty_entry');
    var addon = $('#entry_unit_addon');
    
    if(!type) {
        input.prop('disabled', true).val('').attr('placeholder', 'Seleccione materia prima primero');
        addon.text('--');
        return;
    }
    
    input.prop('disabled', false).attr('placeholder', '0.00');
    addon.text(unit);
    
    if(type === 'unit') {
        input.attr('step', '1');
    } else {
        input.attr('step', '0.001');
    }
}

function openEntryModal() {
    $('#formEntry')[0].reset();
    updateEntryInput();
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
    $('#approve_unit_price_label').text('Precio Unitario (Bs por ' + unit + ')');
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

    $.post("/ajax/pos.ajax.php", {
        approveRawMaterialEntry: "ok",
        id_entry: id_entry,
        id_raw_material: id_raw_material,
        qty: qty,
        price: price,
        total: total,
        id_admin: id_admin
    }, function(res) {
        fncSweetAlert("close", "", "");
        if(res.trim() == "ok") {
            fncToastr("success", "Entrada aprobada y stock actualizado");
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            fncToastr("error", res.split("|")[1] || "Error al aprobar la entrada");
        }
    });
}

// Filter entries logic
function filterEntries(type) {
    if (type === 'pendiente') {
        $('#btnFilterPending').addClass('active');
        $('#btnFilterAll').removeClass('active');
        $('table tbody tr').each(function() {
            if ($(this).find('.empty-state').length > 0) return;
            let isPending = $(this).find('.badge-pending-status').length > 0;
            $(this).toggle(isPending);
        });
    } else {
        $('#btnFilterPending').removeClass('active');
        $('#btnFilterAll').addClass('active');
        $('table tbody tr').show();
    }
}

// Search Filter Logic for Table
$('#searchItem').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $("table tbody tr").filter(function() {
        if (!$(this).find('.empty-state').length) {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        }
    });
});
</script>

