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

<?php
$isAutoAdmin = (($module->title_module === "cashs" && $titleCol === "id_admin_cash") || ($module->title_module === "bills" && $titleCol === "id_admin_bill"));

// Auto-asignar sucursal: para caja, gastos y ventas, los usuarios NO superadmin no pueden seleccionar otra sucursal
$isAutoOffice = false;
$autoOfficeTables = ["cashs" => "id_office_cash", "bills" => "id_office_bill", "orders" => "id_office_order", "clients" => "id_office_client", "products" => "id_office_product", "purchases" => "id_office_purchase"];
if (isset($autoOfficeTables[$module->title_module]) && $titleCol === $autoOfficeTables[$module->title_module]) {
	$rolAdmin = isset($_SESSION['admin']->rol_admin) ? $_SESSION['admin']->rol_admin : '';
	$isAutoOffice = ($rolAdmin !== 'superadmin');
}
?>

<?php if ($isAutoOffice): ?>
	<?php
	// Obtener la sucursal del usuario y mostrarla sin permitir cambio
	$adminOfficeId = isset($_SESSION['admin']->id_office_admin) ? (int)$_SESSION['admin']->id_office_admin : 0;
	$adminOfficeName = '';
	// Buscar nombre de la sucursal
	$respOffice = CurlController::request('offices?linkTo=id_office&equalTo=' . $adminOfficeId, 'GET', []);
	if (isset($respOffice->status) && $respOffice->status == 200 && !empty($respOffice->results)) {
		$officeRow = (array)$respOffice->results[0];
		$officeKeys = array_keys($officeRow);
		$adminOfficeName = count($officeKeys) >= 2 ? urldecode($officeRow[$officeKeys[1]]) : '';
	}
	?>
	<input type="text" class="form-control rounded mb-3 bg-light" value="<?php echo htmlspecialchars($adminOfficeId . ' - ' . $adminOfficeName, ENT_QUOTES, 'UTF-8'); ?>" disabled>
	<input type="hidden" name="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo $adminOfficeId; ?>">

<?php elseif ($isAutoAdmin): ?>
	<?php
	// Obtener registros de la tabla relacionada (administradores)
	$url    = $matrix;
	$method = "GET";
	$fields = [];

	$resp = CurlController::request($url, $method, $fields);
	$rows = (!empty($resp) && isset($resp->status) && $resp->status == 200) ? ($resp->results ?? []) : [];

	$targetId = ($currentValue !== null) ? (string)$currentValue : (string)$_SESSION["admin"]->id_admin;
	$selectedEmail = '';
	foreach ($rows as $row) {
		$arr = (array)$row;
		$keys = array_keys($arr);
		if (count($keys) >= 2 && (string)$arr[$keys[0]] === $targetId) {
			$selectedEmail = urldecode((string)$arr[$keys[1]]);
			break;
		}
	}
	if (empty($selectedEmail) && !empty($_SESSION["admin"])) {
		$selectedEmail = $_SESSION["admin"]->email_admin;
	}
	?>
	<!-- Mostrar el correo del administrador de forma inalterable -->
	<input type="text" class="form-control rounded mb-3 bg-light" value="<?php echo htmlspecialchars($selectedEmail, ENT_QUOTES, 'UTF-8'); ?>" disabled>
	
	<!-- Guardar el ID de forma oculta pero válida para el POST -->
	<input type="hidden" name="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($titleCol, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($targetId, ENT_QUOTES, 'UTF-8'); ?>">

<?php else: ?>

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
		if ($matrix === "products") {
			try {
				$host = getenv("DB_HOST") ?: "127.0.0.1";
				$dbName = getenv("DB_NAME") ?: "u228744577_pos";
				$user = getenv("DB_USER") ?: "root";
				$pass = getenv("DB_PASS") ?: "";
				$port = getenv("DB_PORT") ?: "3306";
				$db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
				$db->exec("set names utf8");
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				$stmt = $db->prepare("
					SELECT id_product, title_product 
					FROM products 
					ORDER BY title_product ASC
				");
				$stmt->execute();
				$rows = $stmt->fetchAll(PDO::FETCH_CLASS);
			} catch (Exception $e) {
				$rows = [];
			}
		} else {
			$url    = $matrix;
			$method = "GET";
			$fields = [];
			$resp = CurlController::request($url, $method, $fields);
			$rows = (!empty($resp) && isset($resp->status) && $resp->status == 200) ? ($resp->results ?? []) : [];
		}

		foreach ($rows as $row) {
			$arr = (array)$row;
			$keys = array_keys($arr);
			if (count($keys) < 2) continue;

			$idVal   = (string)$arr[$keys[0]];
			$textVal = (string)$arr[$keys[1]];

			$safeId   = htmlspecialchars($idVal, ENT_QUOTES, 'UTF-8');
			
			$officeSuffix = "";
			if ($matrix === "products" && isset($arr["title_office"])) {
				$officeSuffix = " (" . urldecode($arr["title_office"]) . ")";
			}
			$safeText = htmlspecialchars(urldecode($textVal) . $officeSuffix, ENT_QUOTES, 'UTF-8');

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
<?php endif; ?>