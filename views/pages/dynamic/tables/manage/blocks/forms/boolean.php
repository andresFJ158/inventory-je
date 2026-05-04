<?php if ($module->columns[$i]->type_column == "boolean"): ?>

<?php
$colName = $module->columns[$i]->title_column;
$current = (!empty($data) && isset($data->{$colName})) ? (int)$data->{$colName} : null;
?>

<select 
	class="form-select rounded"
	name="<?php echo htmlspecialchars($colName, ENT_QUOTES, 'UTF-8'); ?>" 
	id="<?php echo htmlspecialchars($colName, ENT_QUOTES, 'UTF-8'); ?>"
>

	<option value="1" <?php echo ($current === 1) ? 'selected' : ''; ?>>True</option>
	<option value="0" <?php echo ($current === 0) ? 'selected' : ''; ?>>False</option>

</select>

<?php endif ?>
