<?php
$url = "indirect_cost_types?linkTo=id_office_indirect_type&equalTo=".$_SESSION["admin"]->id_office_admin;
$method = "GET";
$fields = array();
$res = CurlController::request($url, $method, $fields);
$cifs = ($res->status == 200) ? $res->results : array();
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-money-bill-wave"></i> Costos Indirectos de Fabricación (CIF)</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary btn-sm px-3 rounded backColor" onclick="openCifModal()">Agregar CIF</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="cifTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre del Costo</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cifs as $index => $cif): ?>
                                <tr>
                                    <td><?php echo $index + 1 ?></td>
                                    <td class="text-uppercase"><?php echo $cif->name_indirect_type ?></td>
                                    <td><?php echo $cif->description_indirect_type ?></td>
                                    <td>
                                        <span class="badge <?php echo $cif->status_indirect_type == 1 ? 'bg-success' : 'bg-danger' ?>">
                                            <?php echo $cif->status_indirect_type == 1 ? 'Activo' : 'Inactivo' ?>
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

<!-- Modal para Nuevo CIF -->
<div class="modal fade" id="modalCif" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Registrar Tipo de CIF</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCif">
            <input type="hidden" id="id_office_indirect_type" value="<?php echo $_SESSION["admin"]->id_office_admin ?>">
            
            <div class="mb-3">
                <label>Nombre del Costo (Ej: Energía Eléctrica, Agua, Envases)</label>
                <input type="text" class="form-control" id="name_indirect_type" required>
            </div>
            <div class="mb-3">
                <label>Descripción (Opcional)</label>
                <textarea class="form-control" id="description_indirect_type" rows="3"></textarea>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary backColor" onclick="saveCif()">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
function openCifModal() {
    $('#formCif')[0].reset();
    $('#modalCif').modal('show');
}

function saveCif() {
    var name = $('#name_indirect_type').val();
    var desc = $('#description_indirect_type').val();
    var id_office = $('#id_office_indirect_type').val();

    if(!name) {
        fncToastr("error", "El nombre es obligatorio");
        return;
    }

    var fields = {
        name_indirect_type: name,
        description_indirect_type: desc,
        id_office_indirect_type: id_office,
        status_indirect_type: 1
    };

    var payload = new URLSearchParams();
    payload.append("apiProxy", "ok");
    payload.append("url", "indirect_cost_types?token=" + localStorage.getItem("tokenAdmin") + "&table=admins&suffix=admin");
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
                    fncToastr("success", "CIF registrado");
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
</script>
