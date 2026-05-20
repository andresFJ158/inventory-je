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
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Unidad</th>
                                    <th>Descripción</th>
                                    <th>Stock Actual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($materials as $index => $material): ?>
                                <tr>
                                    <td><?php echo $index + 1 ?></td>
                                    <td class="text-uppercase"><?php echo $material->name_raw_material ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $material->unit_raw_material ?></span></td>
                                    <td><?php echo $material->description_raw_material ?></td>
                                    <td>
                                        <span class="badge <?php echo $material->stock_raw_material > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?php echo $material->stock_raw_material ?>
                                        </span>
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

<!-- Modal for New Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Registrar Materia Prima</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="formMaterial">
            <input type="hidden" id="id_office_raw_material" value="<?php echo $_SESSION["admin"]->id_office_admin ?>">
            <input type="hidden" id="id_admin_raw_material" value="<?php echo $_SESSION["admin"]->id_admin ?>">
            
            <div class="mb-3">
                <label>Nombre de Materia Prima</label>
                <input type="text" class="form-control" id="name_raw_material" placeholder="Ej: Jabón Base" required>
            </div>
            <div class="mb-3">
                <label>Unidad de Medida</label>
                <input type="text" class="form-control" id="unit_raw_material" placeholder="Ej: kg, g, l, ml, und" required>
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
function openMaterialModal() {
    $('#formMaterial')[0].reset();
    $('#modalMaterial').modal('show');
}

function saveMaterial() {
    var name = $('#name_raw_material').val();
    var unit = $('#unit_raw_material').val();
    var desc = $('#description_raw_material').val();
    var id_office = $('#id_office_raw_material').val();
    var id_admin = $('#id_admin_raw_material').val();

    if(!name || !unit) {
        fncToastr("error", "Complete los campos obligatorios");
        return;
    }

    var fields = {
        name_raw_material: name,
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
</script>
