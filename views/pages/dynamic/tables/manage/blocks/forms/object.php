<?php if ($module->columns[$i]->type_column == "object"): ?>

<?php
// Detectar si es el campo de permisos de admins
$isPermitsAdmin = ($module->title_module === "admins" && $module->columns[$i]->title_column === "permissions_admin");

// Variables para el selector de permisos (solo se usan si $isPermitsAdmin es true)
$currentPermits = array();
$allModules = array();
$currentPermitsJson = "{}";

if ($isPermitsAdmin) {

	if (!empty($data) && !empty($data["permissions_admin"])) {
		$decoded = json_decode(urldecode($data["permissions_admin"]), true);
		if (is_array($decoded)) {
			$currentPermits = array_keys($decoded);
		}
	}

	$urlMods = "pages?select=url_page,title_page&orderBy=order_page&orderMode=ASC";
	$modsRes = CurlController::request($urlMods, "GET", array());
	if (isset($modsRes->status) && $modsRes->status == 200) {
		foreach ($modsRes->results as $mod) {
			$allModules[] = array("title" => $mod->url_page, "alias" => $mod->title_page);
		}
	}

	if (!empty($currentPermits)) {
		$obj2 = array();
		foreach ($currentPermits as $p) { $obj2[$p] = "on"; }
		$currentPermitsJson = json_encode($obj2);
	}
}
?>

<?php if ($isPermitsAdmin): ?>

	<input type="hidden" 
		name="<?php echo $module->columns[$i]->title_column ?>" 
		id="<?php echo $module->columns[$i]->title_column ?>"
		value="<?php echo htmlspecialchars($currentPermitsJson, ENT_QUOTES, 'UTF-8') ?>">

	<div class="mb-2">
		<small class="text-muted">Haz clic en un módulo para <strong>agregar</strong> o <strong>quitar</strong> el permiso:</small>
	</div>

	<div id="permits-container" class="d-flex flex-wrap gap-2">
		<?php foreach ($allModules as $mod): ?>
			<?php $isSelected = in_array($mod["title"], $currentPermits); ?>
			<span 
				class="badge rounded-pill px-3 py-2 permit-badge <?php echo $isSelected ? 'bg-success text-white' : 'bg-light text-dark border' ?>"
				style="cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;"
				data-module="<?php echo htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8') ?>"
				data-alias="<?php echo htmlspecialchars($mod['alias'], ENT_QUOTES, 'UTF-8') ?>"
				onclick="togglePermit(this)"
				title="<?php echo htmlspecialchars($mod['alias'], ENT_QUOTES, 'UTF-8') ?>">
				<?php if ($isSelected): ?>
					<i class="bi bi-check-circle-fill me-1"></i>
				<?php else: ?>
					<i class="bi bi-circle me-1"></i>
				<?php endif ?>
				<?php echo htmlspecialchars($mod["alias"], ENT_QUOTES, "UTF-8") ?>
			</span>
		<?php endforeach ?>
	</div>

	<script>
	function togglePermit(span) {
		var moduleName = span.getAttribute("data-module");
		var aliasName = span.getAttribute("data-alias");
		var hiddenInput = document.getElementById("permissions_admin");
		var obj = {};
		try { obj = JSON.parse(hiddenInput.value); } catch(e) { obj = {}; }

		if (obj.hasOwnProperty(moduleName)) {
			delete obj[moduleName];
			span.className = "badge rounded-pill px-3 py-2 permit-badge bg-light text-dark border";
			span.style.cssText = "cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;";
			span.innerHTML = '<i class="bi bi-circle me-1"><\/i>' + aliasName;
		} else {
			obj[moduleName] = "on";
			span.className = "badge rounded-pill px-3 py-2 permit-badge bg-success text-white";
			span.style.cssText = "cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;";
			span.innerHTML = '<i class="bi bi-check-circle-fill me-1"><\/i>' + aliasName;
		}

		hiddenInput.value = JSON.stringify(obj);
	}
	</script>

<?php else: ?>

	<div class="itemsObject">

		<?php if (!empty($data) && $data[$module->columns[$i]->title_column] != null): ?>

			<?php $arrayObj = new ArrayObject(json_decode(urldecode($data[$module->columns[$i]->title_column]))); ?>

			<?php if (!empty($arrayObj) && $arrayObj->count() > 0): ?>

				<?php foreach ($arrayObj as $key => $value): ?>

					<div class="row row-cols-1 row-cols-sm-2 itemObject">
						
						<div class="col">
							<div class="form-floating mb-3">
								<input 
								type="text"
								class="form-control rounded propertyObject <?php echo $module->columns[$i]->title_column ?>"
								onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')"
								value="<?php echo $key ?>">
								<label>Propiedad</label>
							</div>
						</div>

						<div class="col">
							<div class="form-floating mb-3">
								<input 
								type="text"
								class="form-control rounded position-relative valueObject <?php echo $module->columns[$i]->title_column ?>"
								onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')"
								value="<?php echo htmlspecialchars($value) ?>">
								<label>Valor</label>
								<button type="button" class="btn btn-sm position-absolute" style="top:0; right:0;" onclick="removeObject('<?php echo $module->columns[$i]->title_column ?>','_<?php echo $key ?>',event)">
									<i class="bi bi-x"></i>
								</button>
							</div>
						</div>

					</div>	
					
				<?php endforeach ?>

			<?php else: ?>

				<div class="row row-cols-1 row-cols-sm-2 itemObject">
					<div class="col">
						<div class="form-floating mb-3">
							<input type="text" class="form-control rounded propertyObject <?php echo $module->columns[$i]->title_column ?>" onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')">
							<label>Propiedad</label>
						</div>
					</div>
					<div class="col">
						<div class="form-floating mb-3">
							<input type="text" class="form-control rounded position-relative valueObject <?php echo $module->columns[$i]->title_column ?>" onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')">
							<label>Valor</label>
							<button type="button" class="btn btn-sm position-absolute" style="top:0; right:0;" onclick="removeObject('<?php echo $module->columns[$i]->title_column ?>','_0',event)">
								<i class="bi bi-x"></i>
							</button>
						</div>
					</div>
				</div>	

			<?php endif ?>

		<?php else: ?>
		
			<div class="row row-cols-1 row-cols-sm-2 itemObject">
				<div class="col">
					<div class="form-floating mb-3">
						<input type="text" class="form-control rounded propertyObject <?php echo $module->columns[$i]->title_column ?>" onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')">
						<label>Propiedad</label>
					</div>
				</div>
				<div class="col">
					<div class="form-floating mb-3">
						<input type="text" class="form-control rounded position-relative valueObject <?php echo $module->columns[$i]->title_column ?>" onchange="changeItemObject('<?php echo $module->columns[$i]->title_column ?>')">
						<label>Valor</label>
						<button type="button" class="btn btn-sm position-absolute" style="top:0; right:0;" onclick="removeObject('<?php echo $module->columns[$i]->title_column ?>','_0',event)">
							<i class="bi bi-x"></i>
						</button>
					</div>
				</div>
			</div>	

		<?php endif ?>

	</div>

	<button type="button" class="btn btn-sm btn-default backColor rounded addObject"><small>Add Item</small></button>

	<?php if (!empty($data)): ?>
		<input type="hidden" name="<?php echo $module->columns[$i]->title_column ?>" id="<?php echo $module->columns[$i]->title_column ?>" value='<?php echo urldecode($data[$module->columns[$i]->title_column]) ?>'>
	<?php else: ?>
		<input type="hidden" name="<?php echo $module->columns[$i]->title_column ?>" id="<?php echo $module->columns[$i]->title_column ?>" value='{}'>
	<?php endif ?>

<?php endif ?>

<?php endif ?>