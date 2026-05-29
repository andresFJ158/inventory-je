<?php
// Get all recipes
$urlRecipes = "relations?rel=recipes,products,admins&type=recipe,product,admin&linkTo=id_office_recipe&equalTo=".$_SESSION["admin"]->id_office_admin."&orderBy=id_recipe&orderMode=ASC";
$recRes = CurlController::request($urlRecipes, "GET", array());
$recipes = ($recRes->status == 200) ? $recRes->results : array();

// Get raw materials with their latest prices for real-time calculation
$urlMP = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$mpRes = CurlController::request($urlMP, "GET", array());
$materials = ($mpRes->status == 200) ? $mpRes->results : array();
$materialsData = [];
foreach($materials as $mp) {
    // Get the latest approved entry unit price for pricing
    $urlEntry = "raw_material_entries?linkTo=id_raw_material_entry,status_entry&equalTo=".$mp->id_raw_material.",aprobado&orderBy=id_entry&orderMode=DESC&startAt=0&endAt=1";
    $entryRes = CurlController::request($urlEntry, "GET", array());
    $price = ($entryRes->status == 200 && !empty($entryRes->results)) ? $entryRes->results[0]->unit_price_entry : 0;

    $materialsData[$mp->id_raw_material] = [
        'name' => $mp->name_raw_material,
        'unit' => $mp->unit_raw_material,
        'type' => isset($mp->measure_type) ? $mp->measure_type : 'unit',
        'price' => $price
    ];
}
?>

<div class="container-fluid py-3 p-lg-4" id="recipesList">
    <div class="row">
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-scroll text-success me-2"></i> Recetas de Laboratorio</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3 border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-secondary fs-5">Recetas registradas (<?php echo count($recipes); ?>)</span>
                        <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                            <button class="btn btn-primary btn-sm px-3 rounded-pill backColor" onclick="showRecipeForm()"><i class="fas fa-plus me-1"></i> Crear Nueva Receta</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-3 ms-auto flex-wrap">
                        <div class="input-group input-group-sm shadow-sm flex-nowrap" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0 text-muted" style="border-top-left-radius: 50rem; border-bottom-left-radius: 50rem;"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control border-start-0 shadow-none" id="searchItem" style="border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;" placeholder="Buscar receta...">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="recipesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Cantidad Base Lote</th>
                                    <th>Unidad</th>
                                    <th>Creado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($recipes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center p-0">
                                            <div class="empty-state">
                                                <i class="fas fa-scroll empty-state-icon"></i>
                                                <div class="empty-state-title">No hay recetas registradas</div>
                                                <div class="empty-state-description">Aún no has registrado ninguna fórmula o receta en este laboratorio.</div>
                                                <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                                                    <button class="btn btn-primary btn-sm rounded backColor px-4" onclick="showRecipeForm()">Crear Nueva Receta</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($recipes as $index => $recipe): ?>
                                    <tr>
                                        <td><?php echo $index + 1 ?></td>
                                        <td class="text-uppercase fw-bold"><?php echo $recipe->title_product ?></td>
                                        <td><?php echo $recipe->batch_size_recipe ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $recipe->unit_batch_recipe ?></span></td>
                                        <td><?php echo $recipe->name_admin ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info text-white px-3 rounded" onclick="viewRecipe(<?php echo $recipe->id_recipe ?>)" title="Ver"><i class="fas fa-eye me-1"></i> Ver</button>
                                            <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                                            <button class="btn btn-sm btn-warning text-dark px-3 rounded ms-1" onclick="editRecipe(<?php echo $recipe->id_recipe ?>)" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                                            <button class="btn btn-sm btn-danger text-white px-3 rounded ms-1" onclick="deleteRecipe(<?php echo $recipe->id_recipe ?>)" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                            <?php endif; ?>
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

<!-- Vista Formulario Receta -->
<div class="container-fluid py-3 p-lg-4 d-none" id="recipeFormContainer">
    <div class="row">
        <div class="col-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="m-0"><i class="fas fa-edit"></i> Creador de Receta</h5>
                <button class="btn btn-secondary btn-sm" onclick="hideRecipeForm()">Volver</button>
            </div>
            <hr>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Datos Generales</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="hidden" id="edit_id_recipe" value="">
                            <label>Producto a Producir (Nombre Nuevo)</label>
                            <input type="text" class="form-control rounded-3" id="name_product_recipe" placeholder="Ej: Vinagre de Manzana 1L">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Cantidad base de la receta</label>
                            <input type="number" step="0.01" class="form-control rounded-3" id="batch_size_recipe" value="1">
                            <small class="text-muted d-block mt-1">Cuánto estimado de producto a granel te dará como resultado esta receta.</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Unidad de medida (a granel)</label>
                            <input type="text" class="form-control rounded-3" id="unit_batch_recipe" placeholder="Ej: unidades">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingredientes -->
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted m-0">Materia Prima (Ingredientes)</h6>
                        <button class="btn btn-sm btn-outline-success rounded" onclick="addIngredient()"><i class="fas fa-plus"></i> Agregar</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="ingredientsTable">
                            <thead>
                                <tr>
                                    <th>Materia Prima</th>
                                    <th>Cantidad / Unidad base</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Mano de Obra -->
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted m-0">Mano de Obra</h6>
                        <button class="btn btn-sm btn-outline-primary rounded" onclick="addLabor()"><i class="fas fa-plus"></i> Agregar</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="laborTable">
                            <thead>
                                <tr>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="text-end mb-4">
                <button class="btn btn-success rounded btn-lg backColor px-5" onclick="saveRecipe()">Guardar Receta</button>
            </div>
        </div>
    </div>
</div>

<script>
var materialsData = <?php echo json_encode($materialsData); ?>;
var officeId = <?php echo $_SESSION["admin"]->id_office_admin; ?>;
var adminId = <?php echo $_SESSION["admin"]->id_admin; ?>;

function showRecipeForm() {
    $('#edit_id_recipe').val(''); // Reset edit mode
    $('#name_product_recipe').val('');
    $('#batch_size_recipe').val('1');
    $('#unit_batch_recipe').val('L');
    $('#ingredientsTable tbody').empty();
    $('#laborTable tbody').empty();
    $('#recipesList').addClass('d-none');
    $('#recipeFormContainer').removeClass('d-none');
}

function editRecipe(id) {
    fncSweetAlert("loading", "Cargando receta...", "");
    $.post("/ajax/pos.ajax.php", { getRecipeDataForEdit: "ok", id_recipe: id }, function(res) {
        fncSweetAlert("close", "", "");
        try {
            var data = JSON.parse(res);
            $('#edit_id_recipe').val(data.recipe.id_recipe);
            $('#name_product_recipe').val(data.recipe.title_product);
            $('#batch_size_recipe').val(data.recipe.batch_size_recipe);
            $('#unit_batch_recipe').val(data.recipe.unit_batch_recipe);
            
            $('#ingredientsTable tbody').empty();
            if(data.ingredients) {
                data.ingredients.forEach(i => {
                    addIngredient();
                    let lastRow = $('#ingredientsTable tbody tr').last();
                    let selectEl = lastRow.find('.ing-id');
                    selectEl.val(i.id_raw_material_ingredient);
                    ingChange(selectEl[0]);
                    lastRow.find('.ing-qty').val(parseFloat(i.qty_ingredient));
                });
            }

            $('#laborTable tbody').empty();
            if(data.labor) {
                data.labor.forEach(l => {
                    addLabor();
                    let lastRow = $('#laborTable tbody tr').last();
                    lastRow.find('.labor-desc').val(l.description_labor);
                    lastRow.find('.labor-type').val(l.type_labor);
                });
            }

            $('#recipesList').addClass('d-none');
            $('#recipeFormContainer').removeClass('d-none');
        } catch(e) {
            fncToastr("error", "Error cargando la receta.");
        }
    });
}

function deleteRecipe(id) {
    fncFormatInputs();
    fncSweetAlert("confirm", "¿Estás seguro de eliminar esta receta?", "").then(resp => {
        if(resp){
            $.post("/ajax/pos.ajax.php", { deleteRecipe: "ok", id_recipe: id }, function(res) {
                if(res.trim() == "ok") {
                    fncSweetAlert("success", "Receta eliminada", "/lab_recetas");
                } else {
                    fncToastr("error", "Error al eliminar");
                }
            });
        }
    });
}

function hideRecipeForm() {
    $('#recipeFormContainer').addClass('d-none');
    $('#recipesList').removeClass('d-none');
}

// INGREDIENTS
function addIngredient() {
    let options = '<option value="">Seleccionar...</option>';
    for(let id in materialsData) {
        options += `<option value="${id}">${materialsData[id].name} (${materialsData[id].unit})</option>`;
    }
    
    let html = `
    <tr class="ing-row">
        <td><select class="form-select form-select-sm ing-id" onchange="ingChange(this)">${options}</select></td>
        <td>
            <div class="input-group input-group-sm">
                <input type="number" step="0.001" class="form-control ing-qty" disabled placeholder="Elige MP">
                <span class="input-group-text ing-unit-addon">--</span>
            </div>
        </td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#ingredientsTable tbody').append(html);
}
function ingChange(select) {
    let id = $(select).val();
    let row = $(select).closest('tr');
    let inputQty = row.find('.ing-qty');
    let addon = row.find('.ing-unit-addon');

    if(id && materialsData[id]) {
        addon.text(materialsData[id].unit);
        inputQty.prop('disabled', false).attr('placeholder', '0.00');
        
        if (materialsData[id].type === 'unit') {
            inputQty.attr('step', '1');
        } else {
            inputQty.attr('step', '0.001');
        }
    } else {
        addon.text('--');
        inputQty.prop('disabled', true).val('').attr('placeholder', 'Elige MP');
    }
}

// LABOR
function addLabor() {
    let html = `
    <tr class="labor-row">
        <td><input type="text" class="form-control form-control-sm labor-desc"></td>
        <td>
            <select class="form-select form-select-sm labor-type">
                <option value="fixed">Fijo por Lote</option>
                <option value="hourly">Por Horas</option>
            </select>
        </td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#laborTable tbody').append(html);
}


function saveRecipe() {
    let name_product = $('#name_product_recipe').val();
    let batch_size = $('#batch_size_recipe').val();
    let unit_batch = $('#unit_batch_recipe').val();

    if(!name_product || !batch_size) {
        fncToastr("error", "Complete producto y cantidad base");
        return;
    }

    // Collect Data
    let ings = [];
    $('.ing-row').each(function() {
        let id = $(this).find('.ing-id').val();
        let qty = $(this).find('.ing-qty').val();
        if(id && qty) ings.push({ id_raw: id, qty: qty });
    });

    let labors = [];
    $('.labor-row').each(function() {
        let desc = $(this).find('.labor-desc').val();
        let type = $(this).find('.labor-type').val();
        if(desc) labors.push({ desc: desc, type: type });
    });

    let recipeData = {
        name_product: name_product,
        batch_size: batch_size,
        unit_batch: unit_batch,
        id_office: officeId,
        id_admin: adminId,
        ingredients: JSON.stringify(ings),
        labor: JSON.stringify(labors),
        token: localStorage.getItem("tokenAdmin")
    };

    let edit_id = $('#edit_id_recipe').val();
    if (edit_id) {
        recipeData.editRecipe = "ok";
        recipeData.id_recipe = edit_id;
    } else {
        recipeData.saveRecipe = "ok";
    }

    fncSweetAlert("loading", "Guardando Receta...", "");

    // Usaremos un endpoint AJAX en pos.ajax.php para guardar todo atómicamente
    $.ajax({
        url: "/ajax/pos.ajax.php",
        method: "POST",
        data: recipeData,
        success: function(response) {
            fncSweetAlert("close", "", "");
            if(response == "ok") {
                fncToastr("success", "Receta guardada exitosamente");
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                fncToastr("error", "Error al guardar receta");
                console.error(response);
            }
        },
        error: function(err) {
            fncSweetAlert("close", "", "");
            fncToastr("error", "Error de servidor");
        }
    });
}
</script>

<!-- Modal Detalles de Receta -->
<div class="modal fade" id="modalRecipeDetails" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
      <div class="modal-header backColor" style="border-radius: 1rem 1rem 0 0;">
        <h5 class="modal-title text-white">Detalles de Receta #<span id="view_id_recipe"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
            <div class="col-sm-6">
                <strong>Producto Final:</strong> <span id="view_name_product" class="text-primary fw-bold"></span>
            </div>
            <div class="col-sm-6 text-end">
                <strong>Rinde (Lote base):</strong> <span id="view_batch_size" class="fw-bold"></span> <span id="view_unit_batch" class="text-muted small"></span>
            </div>
        </div>

        <h6 class="border-bottom pb-2 text-secondary"><i class="fas fa-dolly-flatbed me-1"></i> Insumos (Fórmula Base)</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped border">
                <thead class="table-light">
                    <tr>
                        <th>Materia Prima</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Costo Ref.</th>
                        <th class="text-end">Subtotal Est.</th>
                    </tr>
                </thead>
                <tbody id="view_ingredients_tbody">
                </tbody>
            </table>
        </div>

        <h6 class="border-bottom pb-2 text-secondary"><i class="fas fa-users me-1"></i> Mano de Obra Requerida</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-striped border">
                <thead class="table-light">
                    <tr>
                        <th>Descripción</th>
                        <th class="text-end">Tipo de Asignación</th>
                    </tr>
                </thead>
                <tbody id="view_labor_tbody">
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewRecipe(id) {
    fncSweetAlert("loading", "Cargando receta...", "");
    $.post("/ajax/pos.ajax.php", { getRecipeDataForEdit: "ok", id_recipe: id }, function(res) {
        fncSweetAlert("close", "", "");
        try {
            var data = JSON.parse(res);
            $('#view_id_recipe').text(data.recipe.id_recipe);
            $('#view_name_product').text(data.recipe.title_product);
            $('#view_batch_size').text(data.recipe.batch_size_recipe);
            $('#view_unit_batch').text(data.recipe.unit_batch_recipe);
            
            let tbodyIng = '';
            let totalCost = 0;
            if(data.ingredients && data.ingredients.length > 0) {
                data.ingredients.forEach(i => {
                    let mp = materialsData[i.id_raw_material_ingredient];
                    let mpName = mp ? mp.name : 'Desconocido';
                    let mpUnit = mp ? mp.unit : '';
                    let mpPrice = mp ? parseFloat(mp.price) : 0;
                    let qty = parseFloat(i.qty_ingredient);
                    let subtotal = qty * mpPrice;
                    totalCost += subtotal;

                    tbodyIng += `<tr>
                        <td>${mpName}</td>
                        <td class="text-end">${qty} <span class="text-muted small">${mpUnit}</span></td>
                        <td class="text-end text-secondary">Bs ${mpPrice.toFixed(2)}</td>
                        <td class="text-end fw-bold text-success">Bs ${subtotal.toFixed(2)}</td>
                    </tr>`;
                });
                tbodyIng += `<tr class="table-light fw-bold border-top">
                    <td colspan="3" class="text-secondary">Costo de Insumos Total Estimado:</td>
                    <td class="text-end text-success">Bs ${totalCost.toFixed(2)}</td>
                </tr>`;
            } else {
                tbodyIng = '<tr><td colspan="4" class="text-center text-muted">No tiene insumos registrados</td></tr>';
            }
            $('#view_ingredients_tbody').html(tbodyIng);

            let tbodyLab = '';
            if(data.labor && data.labor.length > 0) {
                data.labor.forEach(l => {
                    let typeText = l.type_labor === 'fixed' ? 'Fijo por Lote' : 'Por Horas';
                    tbodyLab += `<tr>
                        <td>${l.description_labor}</td>
                        <td class="text-end">${typeText}</td>
                    </tr>`;
                });
            } else {
                tbodyLab = '<tr><td colspan="2" class="text-center text-muted">No tiene mano de obra registrada</td></tr>';
            }
            $('#view_labor_tbody').html(tbodyLab);

            $('#modalRecipeDetails').modal('show');
        } catch(e) {
            fncToastr("error", "Error al cargar la receta.");
        }
    });
}

// Search Filter Logic for Table
$('#searchItem').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $("#recipesTable tbody tr").filter(function() {
        if (!$(this).find('.empty-state').length) {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        }
    });
});
</script>

