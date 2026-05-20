<?php if ($module->columns[$i]->type_column == "array"): ?>

	<input 
	type="text"
	class="form-control rounded tags-input"
	data-role="tagsinput"
	id="<?php echo $module->columns[$i]->title_column ?>"
	name="<?php echo $module->columns[$i]->title_column ?>"
	value="<?php if (!empty($data)): ?><?php echo urldecode($data[$module->columns[$i]->title_column]) ?><?php endif ?>">
	
	<?php if ($module->columns[$i]->title_column == "permits_admin"): ?>
		
		<?php 
			$urlModules = "modules?select=title_module";
			$method = "GET";
			$fields = array();
			$modulesData = CurlController::request($urlModules, $method, $fields);
			$availablePermits = array();
			if($modulesData->status == 200){
				foreach($modulesData->results as $mod){
					$availablePermits[] = $mod->title_module;
				}
			}
			// Agregamos algunos que podrían no ser módulos directos pero útiles
			if(!in_array("pos", $availablePermits)) $availablePermits[] = "pos";
			if(!in_array("reports", $availablePermits)) $availablePermits[] = "reports";
		?>
		
		<div class="mt-2" id="suggested-permits">
			<small class="text-muted d-block mb-1">Permisos sugeridos (haz clic para agregar):</small>
			<?php foreach ($availablePermits as $permit): ?>
				<span class="badge bg-secondary cursor-pointer me-1 mb-1 badge-permit" style="cursor: pointer;" onclick="addPermit('<?php echo $permit; ?>')"><?php echo $permit; ?></span>
			<?php endforeach; ?>
		</div>

		<script>
		function addPermit(permit) {
			// El input original
			var inputId = '<?php echo $module->columns[$i]->title_column ?>';
			// Utilizando la librería tagsinput de bootstrap
			$('#' + inputId).tagsinput('add', permit);
		}
		</script>

	<?php endif ?>

<?php endif ?>


