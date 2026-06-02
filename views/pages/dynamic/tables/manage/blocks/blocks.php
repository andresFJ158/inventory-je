<?php
$colTitle = $module->columns[$i]->title_column;
$isAutoAdminField = (($module->title_module === "cashs" && $colTitle === "id_admin_cash") || ($module->title_module === "bills" && $colTitle === "id_admin_bill"));
$isRolAdmin = ($module->title_module === "admins" && $colTitle === "rol_admin");

if ($isAutoAdminField):
	$currentValue = null;
	if (!empty($data)) {
		if (is_array($data) && array_key_exists($colTitle, $data)) {
			$currentValue = $data[$colTitle];
		} elseif (is_object($data) && property_exists($data, $colTitle)) {
			$currentValue = $data->{$colTitle};
		}
	}
	$inputValue = ($currentValue !== null) ? $currentValue : $_SESSION["admin"]->id_admin;
?>
	<input type="hidden" name="<?php echo htmlspecialchars($colTitle, ENT_QUOTES, 'UTF-8'); ?>" id="<?php echo htmlspecialchars($colTitle, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($inputValue, ENT_QUOTES, 'UTF-8'); ?>">

<?php elseif ($isRolAdmin):
	$currentValue = "";
	if (!empty($data)) {
		if (is_array($data) && array_key_exists($colTitle, $data)) {
			$currentValue = urldecode($data[$colTitle]);
		} elseif (is_object($data) && property_exists($data, $colTitle)) {
			$currentValue = urldecode($data->{$colTitle});
		}
	}

	$urlRoles = "roles?select=name_role";
	$rolesRes = CurlController::request($urlRoles, "GET", array());
	$allRoles = array();
	if (isset($rolesRes->status) && $rolesRes->status == 200) {
		foreach ($rolesRes->results as $r) {
			$allRoles[] = $r->name_role;
		}
	}
	
	// Fallback a los roles quemados en caso de que la tabla 'roles' no exista
	if (empty($allRoles)) {
		$allRoles = array("superadmin", "admin", "cajero", "despachador", "lab_admin", "lab_worker");
	}
	if (!in_array("vendedor", $allRoles)) {
		$pos = array_search("cajero", $allRoles);
		if ($pos !== false) {
			array_splice($allRoles, $pos + 1, 0, "vendedor");
		} else {
			$allRoles[] = "vendedor";
		}
	}
?>
<div class="card rounded border-0 shadow mb-3 pb-3">
	<div class="card-body">
		<label for="<?php echo $module->columns[$i]->title_column ?>" class="form-label float-start text-capitalize">
			<?php echo $module->columns[$i]->alias_column ?>:
		</label>
		<span class="float-end badge badge-default border small rounded text-muted">
			<?php echo $module->columns[$i]->type_column ?>
		</span>
		<div class="clearfix"></div>

		<input type="hidden" name="rol_admin" id="rol_admin" value="<?php echo htmlspecialchars($currentValue, ENT_QUOTES, 'UTF-8') ?>">
		
		<div class="mb-2">
			<small class="text-muted">Selecciona el rol (solo se permite uno):</small>
		</div>

		<div class="d-flex flex-wrap gap-2">
			<?php foreach ($allRoles as $r): ?>
				<?php $isSelected = ($currentValue === $r); ?>
				<span 
					class="badge rounded-pill px-3 py-2 role-badge <?php echo $isSelected ? 'bg-primary text-white' : 'bg-light text-dark border' ?>"
					style="cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;"
					data-role="<?php echo htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>"
					onclick="selectRole(this)">
					<?php if ($isSelected): ?>
						<i class="bi bi-check-circle-fill me-1"></i>
					<?php else: ?>
						<i class="bi bi-circle me-1"></i>
					<?php endif ?>
					<?php echo htmlspecialchars(ucfirst(str_replace("_", " ", $r)), ENT_QUOTES, "UTF-8") ?>
				</span>
			<?php endforeach ?>
		</div>

		<script>
		function selectRole(span) {
			var role = span.getAttribute("data-role");
			var allBadges = document.querySelectorAll('.role-badge');
			allBadges.forEach(function(b) {
				b.className = "badge rounded-pill px-3 py-2 role-badge bg-light text-dark border";
				b.style.cssText = "cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;";
				var textNode = "";
				b.childNodes.forEach(function(n){ if(n.nodeType === 3) textNode += n.textContent.trim(); });
				b.innerHTML = '<i class="bi bi-circle me-1"><\/i>' + textNode;
			});

			span.className = "badge rounded-pill px-3 py-2 role-badge bg-primary text-white";
			span.style.cssText = "cursor:pointer; font-size:0.85rem; user-select:none; transition:all 0.2s;";
			var activeTextNode = "";
			span.childNodes.forEach(function(n){ if(n.nodeType === 3) activeTextNode += n.textContent.trim(); });
			span.innerHTML = '<i class="bi bi-check-circle-fill me-1"><\/i>' + activeTextNode;

			document.getElementById("rol_admin").value = role;
			toggleRoleFields(role);

			if (role === "cajero" || role === "vendedor" || role === "despachador") {
				var targetModules = [];
				if (role === "cajero") {
					targetModules = ["pos", "caja", "clientes", "productos", "ordenes", "ventas", "gastos", "reports", "solicitar_inventario", "mi_inventario"];
				} else if (role === "vendedor") {
					targetModules = ["pos", "clientes", "ordenes", "solicitar_inventario", "mi_inventario"];
				} else if (role === "despachador") {
					targetModules = ["proveedores", "productos", "compras", "almacen", "despachos"];
				}
				
				var permitsInput = document.getElementById("permissions_admin");
				if (permitsInput) {
					var currentObj = {};
					targetModules.forEach(function(mod) {
						currentObj[mod] = "on";
					});
					permitsInput.value = JSON.stringify(currentObj);

					var permitBadges = document.querySelectorAll('.permit-badge');
					permitBadges.forEach(function(pBadge) {
						var m = pBadge.getAttribute("data-module");
						var alias = pBadge.getAttribute("data-alias");
						if (targetModules.indexOf(m) !== -1) {
							pBadge.className = "badge rounded-pill px-3 py-2 permit-badge bg-success text-white";
							pBadge.innerHTML = '<i class="bi bi-check-circle-fill me-1"><\/i>' + alias;
						} else {
							pBadge.className = "badge rounded-pill px-3 py-2 permit-badge bg-light text-dark border";
							pBadge.innerHTML = '<i class="bi bi-circle me-1"><\/i>' + alias;
						}
					});
				}
			}
		}

		function toggleRoleFields(role) {
			var sucursalCard = document.querySelector(".col-card-id_office_admin");
			var almacenCard = document.querySelector(".col-card-id_warehouse_admin");
			if (role === "despachador") {
				if (sucursalCard) sucursalCard.style.display = "none";
				if (almacenCard) almacenCard.style.display = "block";
			} else {
				if (sucursalCard) sucursalCard.style.display = "block";
				if (almacenCard) almacenCard.style.display = "none";
			}
		}

		window.addEventListener('load', function() {
			var rolInput = document.getElementById("rol_admin");
			if (rolInput) {
				toggleRoleFields(rolInput.value);
			}
		});
		</script>
	</div>
</div>

<?php else: ?>
<div class="card rounded border-0 shadow mb-3 pb-3 col-card-<?php echo $module->columns[$i]->title_column ?>">
	
	<div class="card-body">

		<label for="<?php echo $module->columns[$i]->title_column ?>" class="form-label float-start text-capitalize">
			<?php echo $module->columns[$i]->alias_column ?>:
		</label>
		<span class="float-end badge badge-default border small rounded text-muted">
			<?php echo $module->columns[$i]->type_column ?>
		</span>
		<div class="clearfix"></div>

		<?php 
		
		/*=============================================
		Formulario de tipo Texto		
		=============================================*/
		
		include "forms/text.php"; 

		/*=============================================
		Formulario de tipo TextoArea			
		=============================================*/
		
		include "forms/textarea.php"; 

		/*=============================================
		Formulario de tipo Número Entero		
		=============================================*/
		
		include "forms/int.php"; 

		/*=============================================
		Formulario de tipo Número con decimal			
		=============================================*/
		
		include "forms/double.php"; 

		/*=============================================
		Formulario de tipo Selección	
		=============================================*/
		
		include "forms/select.php"; 

		/*=============================================
		Formulario de tipo Boleano		
		=============================================*/
		
		include "forms/boolean.php"; 

		/*=============================================
		Formulario de tipo Arreglo	
		=============================================*/
		
		include "forms/array.php"; 

		/*=============================================
		Formulario de tipo Objeto		
		=============================================*/
		
		include "forms/object.php"; 

		/*=============================================
		Formulario de tipo JSON		
		=============================================*/
		
		include "forms/_json.php"; 

		/*=============================================
		Formulario de tipo Archivo, Imagen, Video
		=============================================*/
		
		include "forms/file.php"; 

		/*=============================================
		Formulario de tipo Fecha	
		=============================================*/
		
		include "forms/date.php"; 

		/*=============================================
		Formulario de tipo tiempo	
		=============================================*/
		
		include "forms/time.php"; 

		/*=============================================
		Formulario de tipo Fecha y Tiempo
		=============================================*/
		
		include "forms/datetime.php"; 

		/*=============================================
		Formulario de tipo Fecha y Tiempo Automático
		=============================================*/

		include "forms/timestamp.php"; 

		/*=============================================
		Formulario de tipo Código
		=============================================*/

		include "forms/code.php"; 

		/*=============================================
		Formulario de tipo Color
		=============================================*/

		include "forms/color.php"; 

		/*=============================================
		Formulario de tipo Contraseña
		=============================================*/

		include "forms/password.php"; 

		/*=============================================
		Formulario de tipo Email
		=============================================*/

		include "forms/email.php"; 

		/*=============================================
		Formulario de tipo Relaciones
		=============================================*/

		include "forms/relations.php";

		/*=============================================
		Formulario de tipo Relaciones
		=============================================*/

		include "forms/chatgpt.php";


		?>

		<div class="valid-feedback">Válido.</div>
		<div class="invalid-feedback">Campo inválido.</div>
	
	</div>

</div>
<?php endif; ?>