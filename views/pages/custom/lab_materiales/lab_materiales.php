<?php
$role = $_SESSION["admin"]->rol_admin;

$url = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_raw_material&orderMode=ASC";
$method = "GET";
$fields = array();
$materials = CurlController::request($url, $method, $fields);
if ($materials->status == 200) {
    $materials = $materials->results;
} else {
    $materials = array();
}

// Calculate KPIs
$totalMaterials = count($materials);
$withStock = 0;
$withoutStock = 0;
foreach($materials as $m) {
    $st = isset($m->stock_raw_material) ? floatval($m->stock_raw_material) : 0;
    if($st > 0) $withStock++;
    else $withoutStock++;
}
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="sk sk-drop text-success me-2"></i> Catálogo de Materia Prima</div>
            </div>
        </div>

        <!-- KPI Metric Cards -->
        <div class="col-12 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card rounded p-3 border-0 shadow-sm kpi-card kpi-info bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Registrados</span>
                                <h3 class="fw-bold mb-0 text-info mt-1"><?php echo $totalMaterials; ?></h3>
                            </div>
                             <div class="p-3 rounded-circle text-info fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(0, 172, 193, 0.1);">
                                <i class="sk sk-drop"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card rounded p-3 border-0 shadow-sm kpi-card kpi-success bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Con Stock</span>
                                <h3 class="fw-bold mb-0 text-success mt-1"><?php echo $withStock; ?></h3>
                            </div>
                             <div class="p-3 rounded-circle text-success fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(67, 160, 71, 0.1);">
                                <i class="sk sk-shield-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card rounded p-3 border-0 shadow-sm kpi-card kpi-danger bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-bold">Sin Stock</span>
                                <h3 class="fw-bold mb-0 text-danger mt-1"><?php echo $withoutStock; ?></h3>
                            </div>
                             <div class="p-3 rounded-circle text-danger fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(229, 57, 53, 0.1);">
                                <i class="sk sk-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-secondary fs-5">Insumos en Catálogo (<?php echo count($materials); ?>)</span>
                        <?php if ($role != 'lab_worker'): ?>
                            <button class="btn btn-primary btn-sm px-3 rounded-pill backColor" onclick="openMaterialModal()"><i class="sk sk-plus me-1"></i> Agregar Materia Prima</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                        <div class="input-group input-group-sm shadow-sm flex-nowrap" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 50rem; border-bottom-left-radius: 50rem;"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control border-start-0 shadow-none" id="searchItem" style="border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;" placeholder="Buscar materia prima...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Stock Actual</th>
                                    <th>Unidad</th>
                                    <th>Descripción</th>
                                    <?php if ($role != 'lab_worker'): ?>
                                        <th class="text-center">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($materials)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center p-0">
                                            <div class="empty-state">
                                                <i class="sk sk-drop empty-state-icon"></i>
                                                <div class="empty-state-title">No hay materias primas registradas</div>
                                                <div class="empty-state-description">Aún no has registrado ninguna materia prima en tu catálogo.</div>
                                                <?php if ($role != 'lab_worker'): ?>
                                                    <button class="btn btn-primary btn-sm rounded backColor px-4" onclick="openMaterialModal()">Agregar Materia Prima</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($materials as $index => $material): 
                                        $badgeClass = 'bg-secondary';
                                        $tipoLabel = 'Desconocido';
                                        if(isset($material->measure_type)) {
                                            if($material->measure_type == 'weight') { $badgeClass = 'bg-warning text-dark'; $tipoLabel = '<i class="fas fa-weight-hanging me-1"></i> Peso'; }
                                            else if($material->measure_type == 'volume') { $badgeClass = 'bg-info text-dark'; $tipoLabel = '<i class="sk sk-drop me-1"></i> Volumen'; }
                                            else if($material->measure_type == 'unit') { $badgeClass = 'bg-success'; $tipoLabel = '<i class="sk sk-box me-1"></i> Unidad'; }
                                        }
                                        $st = isset($material->stock_raw_material) ? floatval($material->stock_raw_material) : 0;
                                        $stockBadgeClass = $st > 0 ? 'badge bg-success' : 'badge bg-danger';
                                    ?>
                                    <tr>
                                        <td><?php echo $index + 1 ?></td>
                                        <td class="text-uppercase fw-bold"><?php echo $material->name_raw_material ?></td>
                                        <td><span class="badge <?php echo $badgeClass ?>"><?php echo $tipoLabel ?></span></td>
                                        <td><span class="<?php echo $stockBadgeClass ?>"><?php echo number_format($st, 2) ?></span></td>
                                        <td><span class="badge bg-secondary"><?php echo $material->unit_raw_material ?></span></td>
                                        <td><?php echo $material->description_raw_material ?></td>
                                        <?php if ($role != 'lab_worker'): ?>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-warning rounded text-dark px-3" onclick="editMaterial(<?php echo htmlspecialchars(json_encode($material)) ?>)" title="Editar">
                                                <i class="sk sk-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger rounded text-white px-3 ms-1" onclick="deleteMaterial(<?php echo $material->id_raw_material ?>, '<?php echo addslashes($material->name_raw_material) ?>')" title="Eliminar">
                                                <i class="sk sk-bin"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
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

<!-- Modal for New/Edit Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header backColor" style="border-radius: 1rem 1rem 0 0;">
        <h5 class="modal-title text-white" id="modalMaterialTitle">Registrar Materia Prima</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formMaterial">
            <input type="hidden" id="id_raw_material" value="">
            <input type="hidden" id="id_office_raw_material" value="<?php echo $_SESSION["admin"]->id_office_admin ?>">
            <input type="hidden" id="id_admin_raw_material" value="<?php echo $_SESSION["admin"]->id_admin ?>">
            
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Nombre de Materia Prima</label>
                <input type="text" class="form-control rounded-3" id="name_raw_material" placeholder="Ej: Jabón Base" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label d-block fw-bold text-secondary">Tipo de Medida</label>
                <div class="d-flex gap-2 mt-2">
                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_weight" value="weight" onchange="updateUnitSelect()">
                    <label class="btn btn-outline-warning w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center" for="type_weight" style="border-width: 2px;">
                        <i class="fas fa-weight-hanging mb-2" style="font-size: 1.8rem;"></i>
                        <span class="fw-bold">Peso</span>
                    </label>

                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_volume" value="volume" onchange="updateUnitSelect()">
                    <label class="btn btn-outline-info w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center" for="type_volume" style="border-width: 2px;">
                        <i class="sk sk-drop mb-2" style="font-size: 1.8rem;"></i>
                        <span class="fw-bold">Volumen</span>
                    </label>

                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_unit" value="unit" onchange="updateUnitSelect()" checked>
                    <label class="btn btn-outline-success w-100 py-3 rounded-3 d-flex flex-column align-items-center justify-content-center" for="type_unit" style="border-width: 2px;">
                        <i class="sk sk-box mb-2" style="font-size: 1.8rem;"></i>
                        <span class="fw-bold">Unidad</span>
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Unidad de Medida</label>
                <select class="form-select rounded-3" id="unit_raw_material" required>
                    <!-- Options populated by JS -->
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">Descripción (Opcional)</label>
                <textarea class="form-control rounded-3" id="description_raw_material" rows="3"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary backColor" onclick="saveMaterial()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
const unitOptions = {
    'weight': ['kg', 'g'],
    'volume': ['L', 'ml'],
    'unit': ['und']
};

function updateUnitSelect() {
    var type = $('input[name="measure_type"]:checked').val();
    var select = $('#unit_raw_material');
    select.empty();
    
    if(unitOptions[type]) {
        unitOptions[type].forEach(function(unit) {
            select.append(`<option value="${unit}">${unit}</option>`);
        });
    }
}

// Call on load
$(document).ready(function() {
    updateUnitSelect();
});

function openMaterialModal() {
    $('#formMaterial')[0].reset();
    $('#id_raw_material').val('');
    $('#modalMaterialTitle').text('Registrar Materia Prima');
    updateUnitSelect();
    $('#modalMaterial').modal('show');
}

function editMaterial(material) {
    $('#formMaterial')[0].reset();
    $('#id_raw_material').val(material.id_raw_material);
    $('#modalMaterialTitle').text('Editar Materia Prima');
    
    $('#name_raw_material').val(material.name_raw_material);
    $('#description_raw_material').val(material.description_raw_material);
    
    // Seleccionar tipo de medida radio button
    $(`input[name="measure_type"][value="${material.measure_type}"]`).prop('checked', true);
    
    // Actualizar unidades y seleccionar la actual
    updateUnitSelect();
    $('#unit_raw_material').val(material.unit_raw_material);
    
    $('#modalMaterial').modal('show');
}

function saveMaterial() {
    var id = $('#id_raw_material').val();
    var name = $('#name_raw_material').val();
    var unit = $('#unit_raw_material').val();
    var measure_type = $('input[name="measure_type"]:checked').val();
    var desc = $('#description_raw_material').val();
    var id_office = $('#id_office_raw_material').val();
    var id_admin = $('#id_admin_raw_material').val();

    if(!name || !unit) {
        fncToastr("error", "Complete los campos obligatorios");
        return;
    }

    if (id) {
        // Modo: Editar
        fncSweetAlert("loading", "Actualizando...", "");
        $.post("/ajax/pos.ajax.php", {
            editRawMaterial: "ok",
            id_raw_material: id,
            name_raw_material: name,
            measure_type: measure_type,
            unit_raw_material: unit,
            description_raw_material: desc
        }, function(response) {
            fncSweetAlert("close", "", "");
            if (response.trim() === "ok") {
                fncToastr("success", "Materia prima actualizada con éxito");
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                let parts = response.split("|");
                let errorMsg = parts[1] || "Error al actualizar";
                fncToastr("error", errorMsg);
            }
        });
    } else {
        // Modo: Crear
        var fields = {
            name_raw_material: name,
            measure_type: measure_type,
            unit_raw_material: unit,
            description_raw_material: desc,
            id_office_raw_material: id_office,
            id_admin_raw_material: id_admin,
            stock_raw_material: 0
        };

        var payload = new URLSearchParams();
        payload.append("apiProxy", "ok");
        payload.append("url", "raw_materials?token=" + localStorage.getItem("tokenAdmin") + "&table=admins&suffix=admin");
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
                        fncToastr("success", "Materia prima registrada");
                        setTimeout(() => { location.reload(); }, 1000);
                    } else {
                        fncToastr("error", "Error al guardar");
                    }
                } catch(e) {
                    fncToastr("error", "Respuesta inválida del servidor");
                }
            },
            error: function(err) {
                fncSweetAlert("close", "", "");
                fncToastr("error", "Error de comunicación con el servidor");
            }
        });
    }
}

function deleteMaterial(id, name) {
    fncSweetAlert("confirm", "¿Eliminar Materia Prima?", `¿Está seguro de eliminar "${name}"? Esta acción no se puede deshacer.`).then(resp => {
        if(resp) {
            fncSweetAlert("loading", "Eliminando...", "");
            $.post("/ajax/pos.ajax.php", { deleteRawMaterial: "ok", id_raw_material: id }, function(response) {
                fncSweetAlert("close", "", "");
                if(response.trim() === "ok") {
                    fncToastr("success", "Materia prima eliminada con éxito");
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    let parts = response.split("|");
                    let errorMsg = parts[1] || "Error al eliminar";
                    fncSweetAlert("error", "No se puede eliminar", errorMsg);
                }
            });
        }
    });
}

// Search Filter Logic for Table
$('#searchItem').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $("#materialsTable tbody tr").filter(function() {
        if (!$(this).find('.empty-state').length) {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        }
    });
});
</script>

