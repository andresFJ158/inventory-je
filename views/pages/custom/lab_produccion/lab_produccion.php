<?php
$role = $_SESSION["admin"]->rol_admin;

// Get all productions
$urlProd = "relations?rel=productions,recipes,products&type=production,recipe,product&linkTo=id_office_production&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_production&orderMode=DESC";
$prodRes = CurlController::request($urlProd, "GET", array());
$productions = ($prodRes->status == 200) ? $prodRes->results : array();

// Get recipes for the "New Production" modal
$urlRecipes = "relations?rel=recipes,products&type=recipe,product&linkTo=id_office_recipe&equalTo=".$_SESSION["admin"]->id_office_admin;
$recRes = CurlController::request($urlRecipes, "GET", array());
$recipes = ($recRes->status == 200) ? $recRes->results : array();
?>

<div class="container-fluid py-3 p-lg-4">
    <div class="row">
        <!-- Breadcrumbs -->
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-industry"></i> Producción de Laboratorio</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <button class="btn btn-primary btn-sm px-3 rounded backColor" onclick="openProductionModal()">Nueva Producción</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th># Orden</th>
                                    <th>Producto</th>
                                    <th>Lotes a Producir</th>
                                    <th>Unidades a Producir</th>
                                    <th>Fecha Inicio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($productions as $index => $prod): ?>
                                <?php $unidades = $prod->batches_production * $prod->batch_size_recipe; ?>
                                <tr>
                                    <td><?php echo $prod->id_production ?></td>
                                    <td><?php echo $prod->name_product ?></td>
                                    <td><?php echo $prod->batches_production ?></td>
                                    <td><?php echo $unidades ?> <span class="small text-muted"><?php echo $prod->unit_batch_recipe ?></span></td>
                                    <td><?php echo $prod->date_created_production ?></td>
                                    <td>
                                        <?php if($prod->status_production == 'en_proceso'): ?>
                                            <span class="badge bg-warning text-dark">En Proceso</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Completado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($prod->status_production == 'en_proceso' && ($role == 'lab_worker' || $role == 'lab_admin' || $role == 'superadmin' || $role == 'admin')): ?>
                                            <button class="btn btn-sm btn-success rounded" onclick="completeProduction(<?php echo $prod->id_production ?>, <?php echo $prod->id_recipe_production ?>, <?php echo $prod->batches_production ?>, <?php echo $prod->id_product_recipe ?>)">
                                                Finalizar Producción
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

<!-- Modal Nueva Producción -->
<div class="modal fade" id="modalProduction" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Iniciar Producción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formProduction">
            <div class="mb-3">
                <label>Seleccionar Receta (Producto)</label>
                <select class="form-select" id="id_recipe_production" required>
                    <option value="">Seleccione...</option>
                    <?php foreach($recipes as $rec): ?>
                        <option value="<?php echo $rec->id_recipe ?>"><?php echo $rec->name_product ?> (Lote: <?php echo $rec->batch_size_recipe ?> <?php echo $rec->unit_batch_recipe ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Cantidad de Lotes a Producir</label>
                <input type="number" step="0.01" class="form-control" id="batches_production" value="1" required>
                <small class="text-muted">Se multiplicarán los ingredientes de la receta por este factor.</small>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary backColor" onclick="saveProduction()">Iniciar</button>
      </div>
    </div>
  </div>
</div>

<script>
var officeId = <?php echo $_SESSION["admin"]->id_office_admin; ?>;
var adminId = <?php echo $_SESSION["admin"]->id_admin; ?>;

function openProductionModal() {
    $('#formProduction')[0].reset();
    $('#modalProduction').modal('show');
}

function saveProduction() {
    var id_rec = $('#id_recipe_production').val();
    var batches = $('#batches_production').val();

    if(!id_rec || !batches || batches <= 0) {
        fncToastr("error", "Datos inválidos");
        return;
    }

    var fields = {
        id_recipe_production: id_rec,
        batches_production: batches,
        status_production: "en_proceso",
        id_office_production: officeId,
        id_admin_production: adminId,
        total_cost_production: 0
    };

    var payload = new URLSearchParams();
    payload.append("apiProxy", "ok");
    payload.append("url", "productions?token=" + localStorage.getItem("tokenAdmin") + "&table=admins&suffix=admin");
    payload.append("method", "POST");
    payload.append("fields", JSON.stringify(fields));

    fncSweetAlert("loading", "Iniciando Producción...", "");

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
                    fncToastr("success", "Producción iniciada");
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    fncToastr("error", "Error al iniciar producción");
                }
            } catch(e) {
                fncToastr("error", "Respuesta inválida");
            }
        },
        error: function() {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error de comunicación");
        }
    });
}

function completeProduction(id_production, id_recipe, batches, id_product) {
    fncSweetAlert("confirm", "¿Está seguro de finalizar esta producción?", "Esto descontará el stock de materias primas y calculará los costos finales.").then(resp => {
        if(resp) {
            fncSweetAlert("loading", "Procesando Finalización...", "");
            
            var data = new FormData();
            data.append("completeProduction", "ok");
            data.append("id_production", id_production);
            data.append("id_recipe", id_recipe);
            data.append("batches", batches);
            data.append("id_product", id_product);
            data.append("id_office", officeId);
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
                    if(response.trim() == "ok") {
                        fncToastr("success", "Producción completada y stock actualizado.");
                        setTimeout(() => { location.reload(); }, 1500);
                    } else if(response.includes("stock_insuficiente")) {
                        let mp_name = response.split("|")[1];
                        fncToastr("error", "Stock insuficiente de: " + mp_name);
                    } else {
                        fncToastr("error", "Error al finalizar la producción.");
                        console.error(response);
                    }
                },
                error: function() {
                    fncSweetAlert("close", "", "");
                    fncToastr("error", "Error de servidor");
                }
            });
        }
    });
}
</script>
