<?php if ($module->columns[$i]->type_column == "email"): ?>

 	<input 
	type="email" 
	class="form-control rounded"
	onchange="validateJS(event, 'email')"
	id="<?php echo $module->columns[$i]->title_column ?>"
	name="<?php echo $module->columns[$i]->title_column ?>"
	placeholder="correo@ejemplo.com"
	value="<?php if (!empty($data)): ?><?php echo htmlspecialchars(urldecode($data[$module->columns[$i]->title_column])) ?><?php endif ?>">
	<div class="invalid-feedback">Ingresa un correo electronico valido.</div>
 	
<?php endif ?>

