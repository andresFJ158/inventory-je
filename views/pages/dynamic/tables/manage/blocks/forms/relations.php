<?php if ($module->columns[$i]->type_column === "relations"): ?>

<?php
require_once "controllers/install.controller.php";
$tables   = InstallController::getTables();
$idColumn = (int)$module->columns[$i]->id_column;
$matrix   = $module->columns[$i]->matrix_column; 
$titleCol = $module->columns[$i]->title_column;

// Valor actual solo para módulos que no son sucursales
$currentValue = null;
if (!empty($data)) {
	if (is_array($data) && array_key_exists($titleCol, $data)) {
		$currentValue = $data[$titleCol];
	} elseif (is_object($data) && property_exists($data, $titleCol)) {
		$currentValue = $data->{$titleCol};
	}
}
?>

<!-- Selector de tabla relacionada -->
<select
	class="form-select rounded mb-3 select2 changeRelations"
	data-id-column="<?php echo $idColumn; ?>"
	<?php echo !empty($matrix) ? 'disabled' : ''; ?>>

	<?php if (!empty($matrix)): ?>
		<option value="<?php echo htmlspecialchars($matrix, ENT_QUOTES, 'UTF-8'); ?>">
			<?php echo htmlspecialchars($matrix, ENT_QUOTES, 'UTF-8'); ?>
		</option>
	<?php else: ?>
		<option value="">Seleccione Tabla</option>
	<?php endif; ?>

	<?php foreach ($tables as $item): ?>
		<?php
		$safeItem = htmlspecialchars($item, ENT_QUOTES, 'UTF-8');
		$isSelected = ($matrix === $item) ? ' selected' : '';
		?>
		<option value="<?php echo $safeItem; ?>"<?php echo $isSelected; ?>>
			<?php echo $safeItem; ?>
		</option>
	<?php endforeach; ?>
</select>

<?php if (!empty($matrix)): ?>
	<input type="hidden" name="matrix_column" value="<?php echo htmlspecialchars($matrix, ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>


<!-- Selector del registro relacionado -->
<select
	class="form-select rounded select2 selectRelations"
	name="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>"
	id="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>"
	data-field-name="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>"
	data-matrix="<?php echo htmlspecialchars($matrix ?? '', ENT_QUOTES, 'UTF-8'); ?>"
	<?php if ($currentValue !== null && !empty($currentValue)): ?>
		data-default-value="<?php echo htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8'); ?>"
	<?php endif; ?>>

	<?php if (empty($matrix)): ?>
		<option value="">Primero seleccione una tabla</option>
	<?php else: ?>

		<?php if ($matrix === "offices"): ?>
			<?php
			// Detectar si es el campo de sucursales en productos y si estamos creando un nuevo registro
			$isProductOffice = ($module->title_module === "products" && 
			                    $titleCol === "id_office_product" && 
			                    empty($data) &&
			                    $matrix === "offices");
			// Permitir que cualquier administrador pueda crear productos para todas las sucursales
			$canUseAllOffices = ($isProductOffice && !empty($_SESSION['admin']));
			
			// Si hay un valor actual (editando), no seleccionar "Selecciona la sucursal"
			$hasCurrentValue = ($currentValue !== null && !empty($currentValue));
			?>
			
			<!-- Opción inicial: solo seleccionada si NO hay valor actual (creando nuevo) -->
			<option value="" <?php echo !$hasCurrentValue ? 'selected' : ''; ?>>Selecciona la sucursal</option>
			
			<?php if ($canUseAllOffices): ?>
				<!-- Opción para agregar a todas las sucursales (solo en creación de productos) -->
				<option value="all">Todas las Sucursales</option>
			<?php endif; ?>
		<?php endif; ?>

		<?php
		// Obtener registros de la tabla relacionada
		$url    = $matrix;
		$method = "GET";
		$fields = [];

		$resp = CurlController::request($url, $method, $fields);
		$rows = (!empty($resp) && isset($resp->status) && $resp->status == 200) ? ($resp->results ?? []) : [];

		foreach ($rows as $row) {
			$arr = (array)$row;
			$keys = array_keys($arr);
			if (count($keys) < 2) continue;

			$idVal   = (string)$arr[$keys[0]];
			$textVal = (string)$arr[$keys[1]];

			$safeId   = htmlspecialchars($idVal, ENT_QUOTES, 'UTF-8');
			$safeText = htmlspecialchars(urldecode($textVal), ENT_QUOTES, 'UTF-8');

			// Si es sucursal y hay valor actual, marcar como seleccionada si coincide
			// Si no es sucursal, marcar si coincide con el valor actual
			if ($matrix === "offices") {
				$selected = ($currentValue !== null && !empty($currentValue) && (string)$currentValue === $idVal) ? ' selected' : '';
			} else {
				$selected = ($currentValue !== null && (string)$currentValue === $idVal) ? ' selected' : '';
			}

			echo "<option value=\"{$safeId}\"{$selected}>{$safeId} - {$safeText}</option>";
		}
		?>

	<?php endif; ?>
</select>

<?php if (isset($canUseAllOffices) && $canUseAllOffices): ?>
	<small class="text-muted d-block mt-1">
		<i class="bi bi-info-circle me-1"></i>
		Selecciona "Todas las Sucursales" para crear el producto en todas las sucursales con un solo registro.
	</small>
<?php endif; ?>
<?php endif; ?>