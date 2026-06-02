<?php if ($module->columns[$i]->type_column == "datetime"): ?>

	<?php
	$isCajaDateEnd = ($module->title_module == "cashs"
		&& isset($routesArray[0])
		&& $routesArray[0] == "caja"
		&& $module->columns[$i]->title_column == "date_end_cash");
	$dtClass = $isCajaDateEnd ? "form-control rounded-start bg-light" : "form-control rounded-start datetimepicker";
	?>

	<div class="input-group">
		
		<input 
		type="text" 
		class="<?php echo $dtClass; ?>" 
		placeholder="YYYY-mm-dd HH:mm"
		id="<?php echo $module->columns[$i]->title_column ?>"  
		name="<?php echo $module->columns[$i]->title_column ?>"
		value="<?php if (!empty($data)): ?><?php echo urldecode($data[$module->columns[$i]->title_column]) ?><?php endif ?>"
		<?php echo $isCajaDateEnd ? 'readonly tabindex="-1"' : ''; ?>
		>

		<div class="input-group-text rounded-end">
			<i class="bi bi-calendar-week"></i>
		</div>

	</div>

<?php endif ?>