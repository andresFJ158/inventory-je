<?php
$url = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$method = "GET";
$fields = array();
$materials = CurlController::request($url, $method, $fields);
if ($materials->status == 200) {
    $materials = $materials->results;
} else {
    $materials = array();
}
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-flask"></i> Catálogo de Materia Prima</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary btn-sm px-3 rounded backColor" onclick="openMaterialModal()">Agregar Materia Prima</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Unidad</th>
                                    <th>Descripción</th>
                                    <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                                        <th class="text-center">Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materials as $index => $material): 
                                    $badgeClass = 'bg-secondary';
                                    $tipoLabel = 'Desconocido';
                                    if(isset($material->measure_type)) {
                                        if($material->measure_type == 'weight') { $badgeClass = 'bg-warning text-dark'; $tipoLabel = 'ðŸŸ  Peso'; }
                                        else if($material->measure_type == 'volume') { $badgeClass = 'bg-info text-dark'; $tipoLabel = 'ðŸ”µ Volumen'; }
                                        else if($material->measure_type == 'unit') { $badgeClass = 'bg-success'; $tipoLabel = 'ðŸŸ¢ Unidad'; }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $index + 1 ?></td>
                                    <td class="text-uppercase"><?php echo $material->name_raw_material ?></td>
                                    <td><span class="badge <?php echo $badgeClass ?>"><?php echo $tipoLabel ?></span></td>
                                    <td><span class="badge bg-secondary"><?php echo $material->unit_raw_material ?></span></td>
                                    <td><?php echo $material->description_raw_material ?></td>
                                    <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning rounded text-dark" onclick="editMaterial(<?php echo htmlspecialchars(json_encode($material)) ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger rounded text-white" onclick="deleteMaterial(<?php echo $material->id_raw_material ?>, '<?php echo addslashes($material->name_raw_material) ?>')" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
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

<!-- Modal for New/Edit Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white" id="modalMaterialTitle">Registrar Materia Prima</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formMaterial">
            <input type="hidden" id="id_raw_material" value="">
            <input type="hidden" id="id_office_raw_material" value="<?php echo $_SESSION["admin"]->id_office_admin ?>">
            <input type="hidden" id="id_admin_raw_material" value="<?php echo $_SESSION["admin"]->id_admin ?>">
            
            <div class="mb-3">
                <label>Nombre de Materia Prima</label>
                <input type="text" class="form-control" id="name_raw_material" placeholder="Ej: Jabón Base" required>
            </div>
            
            <div class="mb-3">
                <label>Tipo de Medida</label>
                <div class="btn-group w-100" role="group" aria-label="Tipo de medida">
                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_weight" value="weight" onchange="updateUnitSelect()">
                    <label class="btn btn-outline-warning" for="type_weight">âš–ï¸ Peso</label>

                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_volume" value="volume" onchange="updateUnitSelect()">
                    <label class="btn btn-outline-info" for="type_volume">ðŸ§ª Volumen</label>

                    <input type="radio" class="btn-check measure-type-radio" name="measure_type" id="type_unit" value="unit" onchange="updateUnitSelect()" checked>
                    <label class="btn btn-outline-success" for="type_unit">ðŸ“¦ Unidad</label>
                </div>
            </div>

            <div class="mb-3">
                <label>Unidad de Medida</label>
                <select class="form-select" id="unit_raw_material" required>
                    <!-- Options populated by JS -->
                </select>
            </div>
            <div class="mb-3">
                <label>Descripción (Opcional)</label>
                <textarea class="form-control" id="description_raw_material" rows="3"></textarea>
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
</script>

