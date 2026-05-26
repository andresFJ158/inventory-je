<?php
$role = $_SESSION["admin"]->rol_admin;
$officeId = $_SESSION["admin"]->id_office_admin;
$adminId  = $_SESSION["admin"]->id_admin;
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumb -->
        <div class="col-12 mb-3">
            <div class="d-lg-flex justify-content-lg-between mt-2 align-items-center">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-check-double"></i> Control de Calidad</div>
                <span class="badge fs-6 px-3 py-2" id="pendingBadgeHeader" style="background:#6f42c1"></span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="col-12 mb-3">
            <ul class="nav nav-tabs" id="qcTabs">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-pending-link" data-bs-toggle="tab" href="#tab-pending">
                        <i class="fas fa-clock me-1"></i> Pendientes
                        <span class="badge bg-warning text-dark ms-1" id="pendingCount">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-history-link" data-bs-toggle="tab" href="#tab-history">
                        <i class="fas fa-history me-1"></i> Historial
                    </a>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white shadow-sm">

                <!-- ===== TAB PENDIENTES ===== -->
                <div class="tab-pane fade show active" id="tab-pending">
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered table-striped align-middle" id="pendingTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID Orden</th>
                                    <th>Producto</th>
                                    <th>Receta</th>
                                    <th class="text-end">Cant. Producida</th>
                                    <th>Fecha Envasado</th>
                                    <th class="text-center">Accion</th>
                                </tr>
                            </thead>
                            <tbody id="pendingTbody">
                                <tr><td colspan="6" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm text-primary"></div> Cargando...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ===== TAB HISTORIAL ===== -->
                <div class="tab-pane fade" id="tab-history">
                    <div class="row mb-3 mt-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterResult">
                                <option value="">Todos los resultados</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="aprobado_con_obs">Aprobado con observaciones</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="loadHistory()"><i class="fas fa-sync-alt"></i> Actualizar</button>
                        </div>
                    </div>
                    <!-- Stats cards -->
                    <div class="row mb-3" id="statsRow">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="fs-3 fw-bold text-primary" id="stat-total">—</div>
                                <small class="text-muted">Total evaluados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="fs-3 fw-bold text-success" id="stat-approved">—</div>
                                <small class="text-muted">Aprobados</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="fs-3 fw-bold text-warning" id="stat-obs">—</div>
                                <small class="text-muted">Con observaciones</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="fs-3 fw-bold text-danger" id="stat-rejected">—</div>
                                <small class="text-muted">Rechazados</small>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="historyTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID QC</th>
                                    <th>ID Orden</th>
                                    <th>Producto</th>
                                    <th>Inspector</th>
                                    <th class="text-end">Aprobadas</th>
                                    <th class="text-end">Rechazadas</th>
                                    <th>Resultado</th>
                                    <th>Observaciones</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="historyTbody">
                                <tr><td colspan="9" class="text-center text-muted py-4">Cargando historial...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL DE EVALUACION DE CALIDAD ===== -->
<div class="modal fade" id="modalQC" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header text-white" style="background:#6f42c1">
        <h5 class="modal-title"><i class="fas fa-check-double me-2"></i>Control de Calidad — Lote <span id="qc_order_id"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Info del lote -->
        <div class="alert alert-secondary py-2 mb-3">
            <div class="row">
                <div class="col-md-6">
                    <strong>Producto:</strong> <span id="qc_product_name"></span><br>
                    <strong>Receta:</strong> <span id="qc_recipe_name"></span>
                </div>
                <div class="col-md-6">
                    <strong>Cantidad total producida:</strong> <span id="qc_total_qty"></span> <span id="qc_unit"></span><br>
                    <strong>Fecha envasado:</strong> <span id="qc_date"></span>
                </div>
            </div>
        </div>

        <!-- Resultado -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Resultado del Control</label>
            <div class="d-flex gap-3 flex-wrap">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="result_qc" id="res_aprobado" value="aprobado" required>
                    <label class="form-check-label text-success fw-semibold" for="res_aprobado"><i class="fas fa-check-circle me-1"></i>Aprobado</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="result_qc" id="res_obs" value="aprobado_con_obs">
                    <label class="form-check-label text-warning fw-semibold" for="res_obs"><i class="fas fa-exclamation-circle me-1"></i>Aprobado con observaciones</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="result_qc" id="res_rechazado" value="rechazado">
                    <label class="form-check-label text-danger fw-semibold" for="res_rechazado"><i class="fas fa-times-circle me-1"></i>Rechazado</label>
                </div>
            </div>
        </div>

        <!-- Cantidades -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-success">Cantidad Aprobada</label>
                <div class="input-group">
                    <input type="number" min="0" step="1" class="form-control" id="qc_qty_approved" oninput="syncQtyFields('approved')">
                    <span class="input-group-text" id="qc_unit_label_a">und</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold text-danger">Cantidad Rechazada (merma)</label>
                <div class="input-group">
                    <input type="number" min="0" step="1" class="form-control" id="qc_qty_rejected" oninput="syncQtyFields('rejected')">
                    <span class="input-group-text" id="qc_unit_label_r">und</span>
                </div>
                <div class="form-text text-danger d-none" id="qty_error">La suma supera la cantidad total producida.</div>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Observaciones / Detalle del problema <span class="text-muted fw-normal">(opcional si aprobado)</span></label>
            <textarea class="form-control" id="qc_notes" rows="4" placeholder="Describa aqui cualquier problema, defecto o nota relevante del lote..."></textarea>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" id="qc_id_production">
        <input type="hidden" id="qc_total_qty_val">

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn text-white" style="background:#6f42c1" onclick="submitQC()">
            <i class="fas fa-check-double me-1"></i>Registrar Control de Calidad
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ===== MODAL DETALLE OBSERVACIONES ===== -->
<div class="modal fade" id="modalNotes" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><p id="modal_notes_text" class="text-muted"></p></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div>
  </div>
</div>

<script>
var officeId = <?php echo $officeId; ?>;
var adminId  = <?php echo $adminId; ?>;
var currentQCTotal = 0;

// ========================
// CARGAR PENDIENTES
// ========================
function loadPending() {
    $.post("/ajax/pos.ajax.php", { getPendingQC: "ok", id_office: officeId }, function(res) {
        try {
            let data = JSON.parse(res);
            let html = '';
            let count = data.length;
            $('#pendingCount').text(count);
            $('#pendingBadgeHeader').text(count > 0 ? count + ' lotes pendientes de revisión' : 'Sin lotes pendientes').toggle(count > 0);

            if (count === 0) {
                html = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success fs-4 d-block mb-2"></i>No hay lotes pendientes de control de calidad.</td></tr>';
            } else {
                data.forEach(function(p) {
                    let d = new Date(p.date_updated_production);
                    let fecha = d.toLocaleDateString('es-BO') + ' ' + d.toLocaleTimeString('es-BO', {hour:'2-digit', minute:'2-digit'});
                    html += `<tr>
                        <td><strong>#${p.id_production}</strong></td>
                        <td>${p.title_product}</td>
                        <td class="text-muted small">${p.name_recipe}</td>
                        <td class="text-end fw-bold">${parseFloat(p.total_qty_production).toLocaleString()} <span class="text-muted">${p.unit_product}</span></td>
                        <td>${fecha}</td>
                        <td class="text-center">
                            <button class="btn btn-sm text-white" style="background:#6f42c1"
                                onclick="openQCModal(${p.id_production}, '${escapeJs(p.title_product)}', '${escapeJs(p.name_recipe)}', ${p.total_qty_production}, '${p.unit_product}', '${p.date_updated_production}')">
                                <i class="fas fa-clipboard-check me-1"></i>Evaluar
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#pendingTbody').html(html);
        } catch(e) {
            $('#pendingTbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar datos.</td></tr>');
        }
    });
}

// ========================
// ABRIR MODAL QC
// ========================
function openQCModal(id_prod, product, recipe, total_qty, unit, date_str) {
    currentQCTotal = parseFloat(total_qty);
    $('#qc_id_production').val(id_prod);
    $('#qc_total_qty_val').val(total_qty);
    $('#qc_order_id').text('#' + id_prod);
    $('#qc_product_name').text(product);
    $('#qc_recipe_name').text(recipe);
    $('#qc_total_qty').text(parseFloat(total_qty).toLocaleString());
    $('#qc_unit').text(unit);
    $('#qc_unit_label_a, #qc_unit_label_r').text(unit);

    let d = new Date(date_str);
    $('#qc_date').text(d.toLocaleDateString('es-BO') + ' ' + d.toLocaleTimeString('es-BO', {hour:'2-digit', minute:'2-digit'}));

    // Reset form
    $('input[name="result_qc"]').prop('checked', false);
    $('#qc_qty_approved').val(total_qty);
    $('#qc_qty_rejected').val(0);
    $('#qc_notes').val('');
    $('#qty_error').addClass('d-none');

    $('#modalQC').modal('show');
}

// ========================
// SINCRONIZAR CANTIDADES
// ========================
function syncQtyFields(changed) {
    let total = currentQCTotal;
    let approved = parseFloat($('#qc_qty_approved').val()) || 0;
    let rejected  = parseFloat($('#qc_qty_rejected').val()) || 0;

    if (changed === 'approved') {
        let newRejected = Math.max(0, total - approved);
        $('#qc_qty_rejected').val(newRejected);
    } else {
        let newApproved = Math.max(0, total - rejected);
        $('#qc_qty_approved').val(newApproved);
    }

    // Validar suma
    approved = parseFloat($('#qc_qty_approved').val()) || 0;
    rejected  = parseFloat($('#qc_qty_rejected').val()) || 0;
    if (approved + rejected > total + 0.01) {
        $('#qty_error').removeClass('d-none');
    } else {
        $('#qty_error').addClass('d-none');
    }
}

// ========================
// ENVIAR QC
// ========================
function submitQC() {
    let result = $('input[name="result_qc"]:checked').val();
    if (!result) { fncToastr("warning", "Selecciona un resultado antes de continuar."); return; }

    let approved  = parseFloat($('#qc_qty_approved').val()) || 0;
    let rejected  = parseFloat($('#qc_qty_rejected').val()) || 0;
    let notes     = $('#qc_notes').val().trim();
    let id_prod   = $('#qc_id_production').val();

    if (approved + rejected > currentQCTotal + 0.01) {
        fncToastr("error", "La suma de cantidades supera el total producido."); return;
    }
    if (result === 'rechazado' && !notes) {
        fncToastr("warning", "Describí el problema en el campo de observaciones."); return;
    }

    let resultLabel = result === 'aprobado' ? 'Aprobado' : (result === 'rechazado' ? 'Rechazado' : 'Aprobado con observaciones');

    fncSweetAlert("confirm",
        "¿Confirmar control de calidad?",
        `Resultado: <strong>${resultLabel}</strong><br>Aprobadas: ${approved} | Rechazadas: ${rejected}`
    ).then(function(ok) {
        if (!ok) return;
        fncSweetAlert("loading", "Registrando control de calidad...", "");

        $.post("/ajax/pos.ajax.php", {
            submitQualityCheck: "ok",
            id_production:  id_prod,
            id_admin:       adminId,
            id_office:      officeId,
            result_qc:      result,
            qty_approved:   approved,
            qty_rejected:   rejected,
            notes_qc:       notes
        }, function(res) {
            fncSweetAlert("close", "", "");
            try {
                let data = JSON.parse(res);
                if (data.status === 'ok') {
                    let msg = data.result === 'completado'
                        ? "Lote aprobado y disponible en inventario."
                        : "Lote rechazado. La merma fue descontada del stock.";
                    fncToastr("success", msg);
                    $('#modalQC').modal('hide');
                    loadPending();
                    loadHistory();
                } else {
                    fncToastr("error", "Error al registrar el QC.");
                    console.error(res);
                }
            } catch(e) {
                if (res.startsWith('error|')) {
                    fncToastr("error", "Error: " + res.split('|')[1]);
                } else {
                    fncToastr("error", "Error inesperado.");
                }
                console.error(res);
            }
        });
    });
}

// ========================
// CARGAR HISTORIAL
// ========================
function loadHistory() {
    $('#historyTbody').html('<tr><td colspan="9" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm"></div> Cargando...</td></tr>');
    $.post("/ajax/pos.ajax.php", { getQCHistory: "ok", id_office: officeId }, function(res) {
        try {
            let data = JSON.parse(res);
            let filterVal = $('#filterResult').val();
            if (filterVal) data = data.filter(d => d.result_qc === filterVal);

            // Stats
            let total = data.length;
            let aprobados = data.filter(d => d.result_qc === 'aprobado').length;
            let obs = data.filter(d => d.result_qc === 'aprobado_con_obs').length;
            let rechazados = data.filter(d => d.result_qc === 'rechazado').length;
            $('#stat-total').text(total);
            $('#stat-approved').text(aprobados);
            $('#stat-obs').text(obs);
            $('#stat-rejected').text(rechazados);

            let html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="9" class="text-center text-muted py-4">No hay registros de control de calidad aun.</td></tr>';
            } else {
                data.forEach(function(qc) {
                    let badge = '';
                    if (qc.result_qc === 'aprobado') badge = '<span class="badge bg-success">Aprobado</span>';
                    else if (qc.result_qc === 'aprobado_con_obs') badge = '<span class="badge bg-warning text-dark">Con observaciones</span>';
                    else badge = '<span class="badge bg-danger">Rechazado</span>';

                    let notesHtml = qc.notes_qc
                        ? `<button class="btn btn-link btn-sm p-0" onclick="showNotes('${escapeJs(qc.notes_qc)}')"><i class="fas fa-eye"></i> Ver</button>`
                        : '<span class="text-muted">—</span>';

                    html += `<tr>
                        <td>#${qc.id_qc}</td>
                        <td>#${qc.id_production_qc}</td>
                        <td>${qc.title_product}</td>
                        <td>${qc.inspector_name || '—'}</td>
                        <td class="text-end text-success fw-bold">${parseFloat(qc.qty_approved_qc).toLocaleString()} <small>${qc.unit_product}</small></td>
                        <td class="text-end text-danger">${parseFloat(qc.qty_rejected_qc).toLocaleString()} <small>${qc.unit_product}</small></td>
                        <td>${badge}</td>
                        <td>${notesHtml}</td>
                        <td>${qc.date_created_qc}</td>
                    </tr>`;
                });
            }
            $('#historyTbody').html(html);
        } catch(e) {
            $('#historyTbody').html('<tr><td colspan="9" class="text-center text-danger">Error al cargar historial.</td></tr>');
        }
    });
}

// ========================
// VER NOTAS
// ========================
function showNotes(text) {
    $('#modal_notes_text').text(text);
    $('#modalNotes').modal('show');
}

// ========================
// UTIL ESCAPE JS
// ========================
function escapeJs(str) {
    return String(str).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'\\"').replace(/\n/g,' ');
}

// ========================
// INIT
// ========================
$(document).ready(function() {
    loadPending();
    $('#tab-history-link').on('shown.bs.tab', function() { loadHistory(); });
    $('#filterResult').on('change', function() { loadHistory(); });
});
</script>
