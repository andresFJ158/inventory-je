<?php if ($module->columns[$i]->type_column == "int" || $module->columns[$i]->type_column == "order"  || $module->columns[$i]->type_column == "stock"): ?>

 	<input 
	type="number" 
	class="form-control rounded"
	id="<?php echo $module->columns[$i]->title_column ?>"
	name="<?php echo $module->columns[$i]->title_column ?>"
	step="1"
	inputmode="numeric"
	placeholder="Ingresa un valor entero"
	value="<?php if (!empty($data)): ?><?php echo urldecode($data[$module->columns[$i]->title_column]) ?><?php endif ?>">
	<div class="invalid-feedback">Ingresa un numero entero valido.</div>
 	
<?php endif ?>

