<?php if ($module->columns[$i]->type_column == "boolean"): ?>

<?php
$colName = $module->columns[$i]->title_column;
$current = null;
if (!empty($data)) {
	$arr = is_array($data) ? $data : (array) $data;
	if (isset($arr[$colName])) {
		$current = (int) $arr[$colName];
	}
}
$isCajaCashStatus = ($module->title_module == "cashs"
	&& isset($routesArray[0])
	&& $routesArray[0] == "caja"
	&& $colName == "status_cash");
?>

<?php if ($isCajaCashStatus): ?>

	<p class="form-control-plaintext mb-0 py-2 border rounded px-3 bg-light small">
		<?php if ($current === null || $current === 1): ?>
			<span class="badge bg-secondary">Abierta</span>
		<?php else: ?>
			<span class="badge bg-success">Cerrada</span>
		<?php endif ?>
	</p>

<?php else: ?>

<select 
	class="form-select rounded"
	name="<?php echo htmlspecialchars($colName, ENT_QUOTES, 'UTF-8'); ?>" 
	id="<?php echo htmlspecialchars($colName, ENT_QUOTES, 'UTF-8'); ?>"
>

	<option value="1" <?php echo ($current === 1) ? 'selected' : ''; ?>>True</option>
	<option value="0" <?php echo ($current === 0) ? 'selected' : ''; ?>>False</option>

</select>

<?php endif ?>

<?php endif ?>
