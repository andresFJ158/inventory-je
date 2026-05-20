<?php
// Get all recipes
$urlRecipes = "relations?rel=recipes,products,admins&type=recipe,product,admin&linkTo=id_office_recipe&equalTo=".$_SESSION["admin"]->id_office_admin;
$recRes = CurlController::request($urlRecipes, "GET", array());
$recipes = ($recRes->status == 200) ? $recRes->results : array();

// Get products without a recipe, or just all products? 
// For simplicity, let's just get all products of the office to populate the create select
$urlProducts = "products?linkTo=id_office_product&equalTo=".$_SESSION["admin"]->id_office_admin;
$prodRes = CurlController::request($urlProducts, "GET", array());
$products = ($prodRes->status == 200) ? $prodRes->results : array();

// Get raw materials with their latest prices for real-time calculation
$urlMP = "raw_materials?linkTo=id_office_raw_material&equalTo=".$_SESSION["admin"]->id_office_admin;
$mpRes = CurlController::request($urlMP, "GET", array());
$materials = ($mpRes->status == 200) ? $mpRes->results : array();
$materialsData = [];
foreach($materials as $mp) {
    // Get last approved entry price
    $urlEntry = "raw_material_entries?linkTo=id_raw_material_entry,status_entry&equalTo=".$mp->id_raw_material.",aprobado&orderBy=id_entry&orderMode=DESC&startAt=0&endAt=1";
    $entryRes = CurlController::request($urlEntry, "GET", array());
    $lastPrice = 0;
    if($entryRes->status == 200 && !empty($entryRes->results)) {
        $lastPrice = $entryRes->results[0]->unit_price_entry;
    }
    $materialsData[$mp->id_raw_material] = [
        'name' => $mp->name_raw_material,
        'unit' => $mp->unit_raw_material,
        'price' => $lastPrice
    ];
}

// Get CIF types
$urlCIF = "indirect_cost_types?linkTo=id_office_indirect_type,status_indirect_type&equalTo=".$_SESSION["admin"]->id_office_admin.",1";
$cifRes = CurlController::request($urlCIF, "GET", array());
$cifs = ($cifRes->status == 200) ? $cifRes->results : array();
?>

<div class="container-fluid py-3 p-lg-4" id="recipesList">
    <div class="row">
        <div class="col-12 mb-3 position-relative">
            <div class="d-lg-flex justify-content-lg-between mt-2">
                <div class="text-capitalize h5 ps-2"><i class="fas fa-scroll"></i> Recetas de Laboratorio</div>
            </div>
        </div>

        <div class="col-12 mb-3">
            <div class="card rounded p-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <?php if ($_SESSION["admin"]->rol_admin != 'lab_worker'): ?>
                    <button class="btn btn-primary btn-sm px-3 rounded backColor" onclick="showRecipeForm()">Crear Nueva Receta</button>
                    <?php else: ?>
                    <span class="text-muted small">Solo vista (Permisos limitados)</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>Tamaño Lote</th>
                                    <th>Unidad</th>
                                    <th>Creado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recipes as $index => $recipe): ?>
                                <tr>
                                    <td><?php echo $index + 1 ?></td>
                                    <td><?php echo $recipe->name_product ?></td>
                                    <td><?php echo $recipe->batch_size_recipe ?></td>
                                    <td><?php echo $recipe->unit_batch_recipe ?></td>
                                    <td><?php echo $recipe->name_admin ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info text-white" onclick="viewRecipe(<?php echo $recipe->id_recipe ?>)"><i class="fas fa-eye"></i></button>
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
                            <label>Producto a Producir</label>
                            <select class="form-select" id="id_product_recipe">
                                <option value="">Seleccione Producto...</option>
                                <?php foreach($products as $prod): ?>
                                    <option value="<?php echo $prod->id_product ?>"><?php echo $prod->name_product ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Tamaño de Lote</label>
                            <input type="number" step="0.01" class="form-control" id="batch_size_recipe" value="1" onkeyup="calcTotals()">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Unidad del Lote</label>
                            <input type="text" class="form-control" id="unit_batch_recipe" placeholder="Ej: unidades">
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
                                    <th>Cantidad</th>
                                    <th>Costo Unit (Prev)</th>
                                    <th>Subtotal (Prev)</th>
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
                                    <th>Horas</th>
                                    <th>Bs/Hora</th>
                                    <th>Costo Fijo</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- CIF -->
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-muted m-0">Costos Indirectos (CIF)</h6>
                        <button class="btn btn-sm btn-outline-warning rounded" onclick="addCif()"><i class="fas fa-plus"></i> Agregar</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="cifsTable">
                            <thead>
                                <tr>
                                    <th>Tipo de Costo</th>
                                    <th>Monto por Lote (Bs)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Panel de Costos -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-light sticky-top" style="top:20px;">
                <div class="card-body">
                    <h5 class="text-center mb-4">Costo Proyectado</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total MP:</span>
                        <strong id="cost_mp">Bs 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Mano de Obra:</span>
                        <strong id="cost_mo">Bs 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total CIF:</span>
                        <strong id="cost_cif">Bs 0.00</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="h5">Costo x Lote:</span>
                        <strong class="h5 text-primary" id="cost_total">Bs 0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="h6">Costo Unitario:</span>
                        <strong class="h6 text-success" id="cost_unit">Bs 0.00</strong>
                    </div>

                    <button class="btn btn-success w-100 rounded btn-lg backColor" onclick="saveRecipe()">Guardar Receta</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var materialsData = <?php echo json_encode($materialsData); ?>;
var cifTypes = <?php echo json_encode($cifs); ?>;
var officeId = <?php echo $_SESSION["admin"]->id_office_admin; ?>;
var adminId = <?php echo $_SESSION["admin"]->id_admin; ?>;

function showRecipeForm() {
    $('#recipesList').addClass('d-none');
    $('#recipeFormContainer').removeClass('d-none');
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
        <td><input type="number" step="0.01" class="form-control form-control-sm ing-qty" onkeyup="calcTotals()"></td>
        <td class="ing-price">0.00</td>
        <td class="ing-subtotal text-primary fw-bold">0.00</td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove(); calcTotals();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#ingredientsTable tbody').append(html);
}
function ingChange(select) {
    let id = $(select).val();
    if(id && materialsData[id]) {
        let price = materialsData[id].price;
        $(select).closest('tr').find('.ing-price').text(parseFloat(price).toFixed(2));
    } else {
        $(select).closest('tr').find('.ing-price').text("0.00");
    }
    calcTotals();
}

// LABOR
function addLabor() {
    let html = `
    <tr class="labor-row">
        <td><input type="text" class="form-control form-control-sm labor-desc"></td>
        <td>
            <select class="form-select form-select-sm labor-type" onchange="laborTypeChange(this)">
                <option value="fixed">Fijo por Lote</option>
                <option value="hourly">Por Horas</option>
            </select>
        </td>
        <td><input type="number" step="0.01" class="form-control form-control-sm labor-hours" disabled onkeyup="calcTotals()"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm labor-rate" disabled onkeyup="calcTotals()"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm labor-fixed" onkeyup="calcTotals()"></td>
        <td class="labor-subtotal text-primary fw-bold">0.00</td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove(); calcTotals();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#laborTable tbody').append(html);
}
function laborTypeChange(select) {
    let type = $(select).val();
    let tr = $(select).closest('tr');
    if(type == 'hourly') {
        tr.find('.labor-hours').prop('disabled', false);
        tr.find('.labor-rate').prop('disabled', false);
        tr.find('.labor-fixed').prop('disabled', true).val('');
    } else {
        tr.find('.labor-hours').prop('disabled', true).val('');
        tr.find('.labor-rate').prop('disabled', true).val('');
        tr.find('.labor-fixed').prop('disabled', false);
    }
    calcTotals();
}

// CIF
function addCif() {
    let options = '<option value="">Seleccionar...</option>';
    cifTypes.forEach(c => {
        options += `<option value="${c.id_indirect_type}">${c.name_indirect_type}</option>`;
    });

    let html = `
    <tr class="cif-row">
        <td><select class="form-select form-select-sm cif-id">${options}</select></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm cif-amount" onkeyup="calcTotals()"></td>
        <td><button class="btn btn-sm text-danger" onclick="$(this).closest('tr').remove(); calcTotals();"><i class="fas fa-times"></i></button></td>
    </tr>`;
    $('#cifsTable tbody').append(html);
}

// CALCULATION
function calcTotals() {
    let total_mp = 0;
    $('.ing-row').each(function() {
        let qty = parseFloat($(this).find('.ing-qty').val()) || 0;
        let price = parseFloat($(this).find('.ing-price').text()) || 0;
        let sub = qty * price;
        $(this).find('.ing-subtotal').text(sub.toFixed(2));
        total_mp += sub;
    });

    let total_mo = 0;
    $('.labor-row').each(function() {
        let type = $(this).find('.labor-type').val();
        let sub = 0;
        if(type == 'hourly') {
            let h = parseFloat($(this).find('.labor-hours').val()) || 0;
            let r = parseFloat($(this).find('.labor-rate').val()) || 0;
            sub = h * r;
        } else {
            sub = parseFloat($(this).find('.labor-fixed').val()) || 0;
        }
        $(this).find('.labor-subtotal').text(sub.toFixed(2));
        total_mo += sub;
    });

    let total_cif = 0;
    $('.cif-row').each(function() {
        let amt = parseFloat($(this).find('.cif-amount').val()) || 0;
        total_cif += amt;
    });

    let cost_lote = total_mp + total_mo + total_cif;
    let batch_size = parseFloat($('#batch_size_recipe').val()) || 1;
    let cost_unit = cost_lote / batch_size;

    $('#cost_mp').text('Bs ' + total_mp.toFixed(2));
    $('#cost_mo').text('Bs ' + total_mo.toFixed(2));
    $('#cost_cif').text('Bs ' + total_cif.toFixed(2));
    $('#cost_total').text('Bs ' + cost_lote.toFixed(2));
    $('#cost_unit').text('Bs ' + cost_unit.toFixed(2));
}

function saveRecipe() {
    let id_product = $('#id_product_recipe').val();
    let batch_size = $('#batch_size_recipe').val();
    let unit_batch = $('#unit_batch_recipe').val();

    if(!id_product || !batch_size) {
        fncToastr("error", "Complete producto y tamaño de lote");
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
        let h = $(this).find('.labor-hours').val() || 0;
        let r = $(this).find('.labor-rate').val() || 0;
        let f = $(this).find('.labor-fixed').val() || 0;
        let sub = $(this).find('.labor-subtotal').text();
        if(desc) labors.push({ desc: desc, type: type, h: h, r: r, f: f, total: sub });
    });

    let cifs = [];
    $('.cif-row').each(function() {
        let id = $(this).find('.cif-id').val();
        let amt = $(this).find('.cif-amount').val();
        if(id && amt) cifs.push({ id_type: id, amount: amt });
    });

    let recipeData = {
        saveRecipe: "ok",
        id_product: id_product,
        batch_size: batch_size,
        unit_batch: unit_batch,
        id_office: officeId,
        id_admin: adminId,
        ingredients: JSON.stringify(ings),
        labor: JSON.stringify(labors),
        cifs: JSON.stringify(cifs),
        token: localStorage.getItem("tokenAdmin")
    };

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
