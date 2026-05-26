<?php
$role = $_SESSION["admin"]->rol_admin;

// Get all productions
$urlProd = "relations?rel=productions,recipes,products&type=production,recipe,product&linkTo=id_office_production&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_production&orderMode=DESC";
$prodRes = CurlController::request($urlProd, "GET", array());
$productions = ($prodRes->status == 200) ? $prodRes->results : array();

// Get raw materials with their latest prices for packaging
$urlMP = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$mpRes = CurlController::request($urlMP, "GET", array());
$materials = ($mpRes->status == 200) ? $mpRes->results : array();
$materialsData = [];
foreach($materials as $mp) {
    $materialsData[$mp->id_raw_material] = [
        'name' => $mp->name_raw_material,
        'unit' => $mp->unit_raw_material
    ];
}

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
                                    <th>ID Orden</th>
                                    <th>Producto</th>
                                    <th>Factor de Escala</th>
                                    <th>Cantidad Total (a granel)</th>
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
                                    <td><?php echo $prod->title_product ?></td>
                                    <td><?php echo $prod->batches_production ?></td>
                                    <td><?php echo $unidades ?> <span class="small text-muted"><?php echo $prod->unit_batch_recipe ?></span></td>
                                    <td><?php echo $prod->date_created_production ?></td>
                                    <td>
                                        <?php if($prod->status_production == 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif($prod->status_production == 'en_proceso'): ?>
                                            <span class="badge bg-info text-white">En Proceso</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Completado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info rounded text-white" onclick="viewProductionDetails(<?php echo $prod->id_production ?>)" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if($prod->status_production == 'pendiente' && ($role == 'lab_worker' || $role == 'lab_admin' || $role == 'superadmin' || $role == 'admin')): ?>
                                            <button class="btn btn-sm btn-primary rounded" onclick="startProduction(<?php echo $prod->id_production ?>)">
                                                Iniciar
                                            </button>
                                        <?php endif; ?>
                                        <?php if($prod->status_production == 'en_proceso' && ($role == 'lab_worker' || $role == 'lab_admin' || $role == 'superadmin' || $role == 'admin')): ?>
                                            <button class="btn btn-sm btn-primary rounded" style="background:#6f42c1; border:none;" onclick="showPackagingModal(<?php echo $prod->id_production ?>, <?php echo $prod->id_recipe_production ?>, <?php echo $prod->batches_production ?>, <?php echo $prod->id_product_production ?>, <?php echo $unidades ?>, '<?php echo $prod->unit_batch_recipe ?>', '<?php echo addslashes($prod->title_product) ?>')">
                                                Pasar a Envasado
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
                <select class="form-select" id="id_recipe_production" required onchange="updateUnitsHint()">
                    <option value="" data-batch="1" data-unit="">Seleccione...</option>
                    <?php foreach($recipes as $rec): ?>
                        <option value="<?php echo $rec->id_recipe ?>" data-product="<?php echo $rec->id_product_recipe ?>" data-batch="<?php echo $rec->batch_size_recipe ?>" data-unit="<?php echo $rec->unit_batch_recipe ?>"><?php echo $rec->title_product ?> (Lote: <?php echo $rec->batch_size_recipe ?> <?php echo $rec->unit_batch_recipe ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Cantidad total a producir (a granel)</label>
                <div class="input-group">
                    <input type="number" step="0.01" class="form-control" id="units_production" required onkeyup="updateUnitsHint()">
                    <span class="input-group-text" id="units_addon">--</span>
                </div>
                <small class="text-muted" id="units_hint">Se multiplicarán los ingredientes de la receta por el factor necesario.</small>
            </div>
            <div class="mb-3">
                <label>Costo Indirecto Total (CIF en Bs)</label>
                <input type="number" step="0.01" class="form-control" id="cif_production" value="0.00" required>
            </div>
            
            <hr>
            <h6>Insumos Requeridos</h6>
            <div id="ingredients_production_container">
                <small class="text-muted">Seleccione una receta y cantidad para ver los insumos.</small>
            </div>

            <hr>
            <h6>Costos de Mano de Obra</h6>
            <div id="labor_production_container">
                <small class="text-muted">Seleccione una receta para ver la mano de obra requerida.</small>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <strong>Total Mano de Obra:</strong>
                <strong id="total_labor_cost" class="text-primary">Bs 0.00</strong>
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
var materialsData = <?php echo json_encode($materialsData ?? []); ?>;

let currentBulkQty = 0;
let currentBulkUnit = '';
let currentRecipeName = '';

function openProductionModal() {
    $('#formProduction')[0].reset();
    $('#units_addon').text('--');
    $('#units_hint').text('Se multiplicarán los ingredientes de la receta por el factor necesario.');
        renderIngredientsTable(0);
    $('#modalProduction').modal('show');
}

function updateUnitsHint() {
    var selected = $('#id_recipe_production').find(':selected');
    var base_batch = parseFloat(selected.data('batch')) || 1;
    var unit = selected.data('unit') || '--';
    
    if (selected.val()) {
        $('#units_addon').text(unit);
    } else {
        $('#units_addon').text('--');
    }
    
    var units = parseFloat($('#units_production').val()) || 0;
    var batches = units / base_batch;
    
    if (units > 0 && selected.val()) {
        renderIngredientsTable(batches);
        $('#units_hint').html(`Receta base: ${base_batch} ${unit}. Factor de escala: <strong>${batches.toFixed(2)}x</strong>. Se usarán ${batches.toFixed(2)}x los ingredientes.`);
    } else {
        $('#units_hint').text('Se multiplicarán los ingredientes de la receta por el factor necesario.');
        renderIngredientsTable(0);
    }
}

$('#id_recipe_production').on('change', function() {
    loadRecipeLabor($(this).val());
    loadRecipeIngredients($(this).val());
});

function loadRecipeLabor(id_recipe) {
    if(!id_recipe) {
        $('#labor_production_container').html('<small class="text-muted">Seleccione una receta para ver la mano de obra requerida.</small>');
        $('#total_labor_cost').text('Bs 0.00').data('val', 0);
        return;
    }
    
    $('#labor_production_container').html('<small class="text-muted">Cargando...</small>');
    
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getRecipeLabor: "ok", id_recipe: id_recipe },
        dataType: "json",
        success: function(res) {
            let html = '';
            if(res.length === 0) {
                html = '<small class="text-muted">Esta receta no tiene mano de obra asignada.</small>';
            } else {
                html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Descripción</th><th>Costo Total Real (Bs)</th></tr></thead><tbody>';
                res.forEach(lab => {
                    let typeBadge = lab.type_labor == 'hourly' ? '<span class="badge bg-info ms-1">Por hora</span>' : '<span class="badge bg-secondary ms-1">Fijo</span>';
                    html += `<tr>
                        <td>${lab.description_labor} ${typeBadge}</td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm labor-cost-input" value="0.00" onkeyup="calcProductionLabor()"></td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
            }
            $('#labor_production_container').html(html);
            calcProductionLabor();
        }
    });
}

function calcProductionLabor() {
    let total = 0;
    $('.labor-cost-input').each(function() {
        total += parseFloat($(this).val()) || 0;
    });
    $('#total_labor_cost').text('Bs ' + total.toFixed(2));
    $('#total_labor_cost').data('val', total);
}


var currentIngredients = [];

function loadRecipeIngredients(id_recipe) {
    if(!id_recipe) {
        currentIngredients = [];
        renderIngredientsTable(0);
        return;
    }
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: { getRecipeIngredients: "ok", id_recipe: id_recipe },
        dataType: "json",
        success: function(res) {
            currentIngredients = res;
            var selected = $('#id_recipe_production').find(':selected');
            var base_batch = parseFloat(selected.data('batch')) || 1;
            var units = parseFloat($('#units_production').val()) || 0;
            var batches = units > 0 ? units / base_batch : 0;
            renderIngredientsTable(batches);
        }
    });
}

function renderIngredientsTable(factor) {
    if (currentIngredients.length === 0) {
        $('#ingredients_production_container').html('<small class="text-muted">Seleccione una receta para ver los insumos.</small>');
        return;
    }
    if (factor <= 0) {
        $('#ingredients_production_container').html('<small class="text-muted">Ingrese una cantidad a producir para ver las cantidades de insumos proyectadas.</small>');
        return;
    }

    let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Insumo</th><th>Cantidad Requerida</th></tr></thead><tbody>';
    currentIngredients.forEach(ing => {
        let reqQty = (parseFloat(ing.qty) * factor).toFixed(2);
        html += `<tr>
            <td>${ing.name}</td>
            <td><strong>${reqQty}</strong> <span class="small">${ing.unit}</span></td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    $('#ingredients_production_container').html(html);
}

function startProduction(id_production) {
    fncSweetAlert("confirm", "¿Iniciar Producción?", "La orden cambiará a estado En Proceso.").then(resp => {
        if(resp) {
            fncSweetAlert("loading", "Iniciando...", "");
            $.post("/ajax/pos.ajax.php", { startProduction: "ok", id_production: id_production }, function(res) {
                if(res.trim() == "ok") {
                    fncToastr("success", "Producción iniciada");
                    setTimeout(() => { location.reload(); }, 1000);
                } else {
                    fncToastr("error", "Error al iniciar");
                }
            });
        }
    });
}

function saveProduction() {
    var id_rec = $('#id_recipe_production').val();
    var units = parseFloat($('#units_production').val());
    var cif = parseFloat($('#cif_production').val()) || 0;
    
    var selected = $('#id_recipe_production').find(':selected');
    var base_batch = parseFloat(selected.data('batch')) || 1;
    var id_product = selected.data('product');
    var batches = units / base_batch;

    if(!id_rec || isNaN(units) || units <= 0) {
        fncToastr("error", "Datos inválidos");
        return;
    }

    var mo = parseFloat($('#total_labor_cost').data('val')) || 0;
    
    var formData = new FormData();
    formData.append("saveProduction", "ok");
    formData.append("id_recipe", id_rec);
    formData.append("id_product", id_product);
    formData.append("batches", batches);
    formData.append("total_qty", units);
    formData.append("cif", cif);
    formData.append("mo", mo);
    formData.append("id_office", officeId);
    formData.append("id_admin", adminId);

    fncSweetAlert("loading", "Guardando Producción...", "");

    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            fncSweetAlert("close", "", "");
            if(response.trim() == "ok") {
                fncToastr("success", "Producción creada (Pendiente)");
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                fncToastr("error", "Error al guardar");
                console.error(response);
            }
        },
        error: function() {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error de comunicación");
        }
    });
}

</script>

<!-- Modal Detalles de Producción -->
<div class="modal fade" id="modalProductionDetails" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header backColor">
        <h5 class="modal-title text-white">Detalles de Producción #<span id="det_id"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
            <div class="col-sm-6">
                <strong>Producto:</strong> <span id="det_product"></span><br>
                <strong>Estado:</strong> <span id="det_status"></span><br>
                <strong>Fecha Inicio:</strong> <span id="det_start"></span>
            </div>
            <div class="col-sm-6 text-end">
                <strong>Lotes (Factor):</strong> <span id="det_factor"></span><br>
                <strong>Cantidad Producida:</strong> <span id="det_qty"></span><br>
                <strong>Costo Unitario:</strong> <span id="det_unit_cost" class="text-primary fw-bold"></span>
            </div>
        </div>

        <h6 class="border-bottom pb-2">Insumos Consumidos</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped border">
                <thead class="table-light">
                    <tr>
                        <th>Materia Prima</th>
                        <th class="text-end">Cantidad Usada</th>
                        <th class="text-end">Costo Unit. (Ref)</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="det_materials_tbody">
                    <!-- Dynamic rows -->
                </tbody>
            </table>
        </div>

        <div class="row">
            <div class="col-sm-6 offset-sm-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Total Insumos:</strong></td>
                        <td class="text-end" id="det_total_mat"></td>
                    </tr>
                    <tr>
                        <td><strong>Costo M.O. (Elaboración):</strong></td>
                        <td class="text-end" id="det_mo"></td>
                    </tr>
                    <tr>
                        <td><strong>Costo M.O. (Envasado):</strong></td>
                        <td class="text-end text-primary" id="det_pkg_mo"></td>
                    </tr>
                    <tr>
                        <td><strong>CIF (Elaboración):</strong></td>
                        <td class="text-end" id="det_cif"></td>
                    </tr>
                    <tr>
                        <td><strong>CIF (Envasado):</strong></td>
                        <td class="text-end text-primary" id="det_pkg_cif"></td>
                    </tr>
                    <tr class="border-top">
                        <td class="h6"><strong>Costo Total Lote:</strong></td>
                        <td class="text-end h6 text-primary fw-bold" id="det_total"></td>
                    </tr>
                </table>
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewProductionDetails(id_production) {
    fncSweetAlert("loading", "Cargando detalles...", "");
    $.post("/ajax/pos.ajax.php", { getProductionDetails: "ok", id_production: id_production }, function(res) {
        fncSweetAlert("close", "", "");
        try {
            var data = JSON.parse(res);
            var p = data.production;
            var mats = data.materials;

            $('#det_id').text(p.id_production);
            $('#det_product').text(p.title_product);
            $('#det_status').text(p.status_production.toUpperCase());
            $('#det_start').text(p.start_date_production || p.date_created_production);
            $('#det_factor').text(p.batches_production + 'x');
            $('#det_qty').text(p.total_qty_production + ' ' + (p.unit_product || ''));

            let real_cif = parseFloat(p.real_indirect_cost) || parseFloat(p.proj_indirect_cost) || 0;
            let real_mo = parseFloat(p.real_labor_cost) || parseFloat(p.proj_labor_cost) || 0;
            let total_mat = 0;

            let tbody = '';
            if(mats && mats.length > 0) {
                mats.forEach(m => {
                    let qty = parseFloat(m.qty_used_mat_cost);
                    let price = parseFloat(m.unit_price_at_production);
                    let sub = parseFloat(m.total_cost_mat_cost);
                    total_mat += sub;
                    tbody += `<tr>
                        <td>${m.name_raw_material}</td>
                        <td class="text-end">${qty.toFixed(2)} <span class="small text-muted">${m.unit_raw_material}</span></td>
                        <td class="text-end">Bs ${price.toFixed(2)}</td>
                        <td class="text-end">Bs ${sub.toFixed(2)}</td>
                    </tr>`;
                });
            } else {
                tbody = '<tr><td colspan="4" class="text-center text-muted py-3">Aún no se ha consumido stock o no hay registro.</td></tr>';
                total_mat = parseFloat(p.proj_materials_cost) || 0;
            }
            $('#det_materials_tbody').html(tbody);

            let total = parseFloat(p.real_total_cost) || (total_mat + real_mo + real_cif);
            
            // If real_total_cost is provided but total_qty is not enough to figure out unit cost, calculate it safely
            let qty_final = parseFloat(p.total_qty_production);
            let unit_cost = parseFloat(p.real_unit_cost) || (qty_final > 0 ? (total / qty_final) : 0);

            let pkg_mo = parseFloat(p.pkg_labor_cost) || 0;
            let pkg_cif = parseFloat(p.pkg_indirect_cost) || 0;

            $('#det_total_mat').text('Bs ' + total_mat.toFixed(2));
            $('#det_mo').text('Bs ' + real_mo.toFixed(2));
            $('#det_pkg_mo').text('Bs ' + pkg_mo.toFixed(2));
            $('#det_cif').text('Bs ' + real_cif.toFixed(2));
            $('#det_pkg_cif').text('Bs ' + pkg_cif.toFixed(2));
            $('#det_total').text('Bs ' + total.toFixed(2));
            $('#det_unit_cost').text('Bs ' + unit_cost.toFixed(2));

            $('#modalProductionDetails').modal('show');
        } catch(e) {
            console.error(e);
            fncToastr("error", "Error al parsear detalles");
        }
    });
}
</script>

<!-- Modal Envasado y Finalización -->
<div class="modal fade" id="modalPackaging" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header text-white" style="background:#6f42c1">
        <h5 class="modal-title">Fase de Envasado y Finalización</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="pkg_id_prod">
        <input type="hidden" id="pkg_id_recipe">
        <input type="hidden" id="pkg_batches">
        <input type="hidden" id="pkg_id_product">

        <div class="row bg-light p-3 rounded mb-3 border">
            <div class="col-md-6">
                <strong>Total Producido (Granel):</strong> 
                <span id="pkg_total_qty_display" class="fs-5 text-primary fw-bold"></span>
            </div>
            <div class="col-md-6 text-end">
                <strong>Envases Calculados:</strong> 
                <span id="pkg_calculated_envases" class="fs-5 text-success fw-bold">0</span> Unidades
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label>Tipo de Empaque</label>
                <select class="form-select" id="pkg_envase_type" onchange="calcEnvases()">
                    <option value="botellas">Botellas</option>
                    <option value="frascos">Frascos</option>
                    <option value="bolsas">Bolsas</option>
                    <option value="cajas">Cajas</option>
                    <option value="galones">Galones</option>
                    <option value="unidades">Unidades Sueltas</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>Capacidad del Envase</label>
                <div class="input-group">
                    <input type="number" step="1" class="form-control" id="pkg_volume" placeholder="Ej: 500" onkeyup="calcEnvases()">
                    <select class="form-select" id="pkg_unit" style="max-width:80px;" onchange="calcEnvases()">
                        <option value="L">L</option>
                        <option value="ml">ml</option>
                        <option value="kg">kg</option>
                        <option value="g">g</option>
                        <option value="und">und</option>
                    </select>
                </div>
                <small class="text-muted">Granel: <span id="pkg_bulk_unit_label" class="fw-bold"></span></small>
            </div>
            <div class="col-md-4">
                <label>Nombre a Inventario</label>
                <input type="text" class="form-control" id="pkg_final_name" placeholder="Ej: Vinagre 500ml">
                <small class="text-muted">Generado automáticamente</small>
            </div>
        </div>

        <h6 class="border-bottom pb-2">Materiales de Envasado</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm" id="pkgMaterialsTable">
                <thead>
                    <tr>
                        <th>Insumo (Inventario MP)</th>
                        <th>Cantidad</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPkgMaterial()">+ Añadir Insumo</button>
        </div>

        <div class="row border-top pt-3">
            <div class="col-md-6 mb-3">
                <label>Mano de Obra Extra (Envasado)</label>
                <div class="input-group">
                    <span class="input-group-text">Bs</span>
                    <input type="number" step="0.01" class="form-control" id="pkg_extra_mo" value="0">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label>Costos Indirectos Extra (Energía, etc.)</label>
                <div class="input-group">
                    <span class="input-group-text">Bs</span>
                    <input type="number" step="0.01" class="form-control" id="pkg_extra_cif" value="0">
                </div>
            </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn text-white" style="background:#6f42c1" onclick="submitPackaging()">Completar Producción</button>
      </div>
    </div>
  </div>
</div>

<script>
function showPackagingModal(id_production, id_recipe, batches, id_product, total_qty, bulk_unit, recipe_name) {
    currentRecipeName = recipe_name || '';
    $('#pkg_id_prod').val(id_production);
    $('#pkg_id_recipe').val(id_recipe);
    $('#pkg_batches').val(batches);
    $('#pkg_id_product').val(id_product);
    
    currentBulkQty = parseFloat(total_qty) || 0;
    currentBulkUnit = bulk_unit || '';

    $('#pkg_total_qty_display').text(currentBulkQty + ' ' + currentBulkUnit);
    $('#pkg_bulk_unit_label').text(currentBulkUnit);
    
    // Auto-select compatible unit
    if(currentBulkUnit === 'L') {
        $('#pkg_unit').html('<option value="ml">ml</option><option value="L">L</option>');
    } else if(currentBulkUnit === 'kg') {
        $('#pkg_unit').html('<option value="g">g</option><option value="kg">kg</option>');
    } else {
        $('#pkg_unit').html('<option value="und">und</option>');
    }

    $('#pkg_volume').val('');
    $('#pkg_final_name').val('');
    calcEnvases();

    $('#pkgMaterialsTable tbody').empty();
    $('#pkg_extra_mo').val('0');
    $('#pkg_extra_cif').val('0');

    $('#modalPackaging').modal('show');
}

function calcEnvases() {
    let vol = parseFloat($('#pkg_volume').val()) || 0;
    let env_unit = $('#pkg_unit').val();
    let envases = 0;
    
    if(vol > 0) {
        // Convert to base unit of bulk if needed
        let vol_in_base = vol;
        if(currentBulkUnit === 'L' && env_unit === 'ml') {
            vol_in_base = vol / 1000.0;
        } else if(currentBulkUnit === 'kg' && env_unit === 'g') {
            vol_in_base = vol / 1000.0;
        } else if(currentBulkUnit === 'ml' && env_unit === 'L') {
            vol_in_base = vol * 1000.0;
        } else if(currentBulkUnit === 'g' && env_unit === 'kg') {
            vol_in_base = vol * 1000.0;
        }

        envases = Math.floor(currentBulkQty / vol_in_base);
    }
    $('#pkg_calculated_envases').text(envases);
    $('#pkg_calculated_envases').data('val', envases);

    // Auto-generate name
    if(vol > 0 && currentRecipeName) {
        // Strip 'a granel' or similar if present, just keep base name
        let baseName = currentRecipeName.replace(/a granel/ig, '').trim();
        $('#pkg_final_name').val(baseName + ' ' + vol + env_unit);
    }
}

function addPkgMaterial() {
    let options = '<option value="">Seleccionar...</option>';
    for(let id in materialsData) {
        options += `<option value="${id}">${materialsData[id].name} (${materialsData[id].unit})</option>`;
    }
    let envases = $('#pkg_calculated_envases').data('val') || 0;
    let html = `
    <tr class="pkg-row">
        <td><select class="form-select form-select-sm pkg-id">${options}</select></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm pkg-qty" placeholder="Cant." value="${envases}"></td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#pkgMaterialsTable tbody').append(html);
}

function submitPackaging() {
    let extra_mats = [];
    let valid = true;
    $('.pkg-row').each(function() {
        let id = $(this).find('.pkg-id').val();
        let qty = parseFloat($(this).find('.pkg-qty').val());
        if(id && qty > 0) {
            extra_mats.push({ id_raw: id, qty: qty });
        } else if (id || qty) {
            valid = false;
        }
    });

    if(!valid) {
        fncToastr("error", "Complete correctamente los insumos de envasado");
        return;
    }

    let final_name = $('#pkg_final_name').val().trim();
    let final_qty = $('#pkg_calculated_envases').data('val') || 0;
    
    if(!final_name || final_qty <= 0) {
        fncToastr("error", "Ingrese el volumen por envase y el nombre del producto final.");
        return;
    }

    let payload = {
        completeProduction: "ok",
        id_production: $('#pkg_id_prod').val(),
        id_recipe: $('#pkg_id_recipe').val(),
        batches: $('#pkg_batches').val(),
        id_product: $('#pkg_id_product').val(),
        extra_mats: JSON.stringify(extra_mats),
        extra_mo: $('#pkg_extra_mo').val(),
        extra_cif: $('#pkg_extra_cif').val(),
        pkg_final_qty: final_qty,
        pkg_final_name: final_name,
        pkg_envase_type: $('#pkg_envase_type').val(),
        id_office: typeof officeId !== 'undefined' ? officeId : 1
    };

    fncSweetAlert("loading", "Procesando envasado y cerrando producción...", "");

    $.post("/ajax/pos.ajax.php", payload, function(res) {
        if(res.trim() == "ok") {
            fncSweetAlert("success", "Producción Completada con Éxito", "/lab_produccion");
        } else if(res.includes("stock_insuficiente")) {
            fncSweetAlert("close", "", "");
            let parts = res.split("|");
            let itemName = parts[1] || "Materia Prima";
            fncSweetAlert("error", "Stock Insuficiente", `No hay suficiente inventario de: ${itemName}`);
        } else {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error al completar");
        }
    });
}
</script>
