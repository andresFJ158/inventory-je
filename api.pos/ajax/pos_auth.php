<?php
/**
 * Autenticación y autorización para pos.ajax.php
 */

/** Acciones públicas (sin sesión PHP). */
function pos_public_ajax_actions(): array {
	return ['loginLabUser', 'logoutLabUser', 'getLoggedUser'];
}

function pos_session_start(): void {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
}

function pos_has_session(): bool {
	pos_session_start();
	return isset($_SESSION['admin']) && is_object($_SESSION['admin']);
}

function pos_current_admin(): ?object {
	pos_session_start();
	return pos_has_session() ? $_SESSION['admin'] : null;
}

function pos_current_admin_id(): int {
	$admin = pos_current_admin();
	return $admin ? intval($admin->id_admin) : 0;
}

function pos_current_office_id(): int {
	$admin = pos_current_admin();
	if (!$admin) {
		return 0;
	}
	$id = intval($admin->id_office_admin);
	if ($id === 0 && isset($admin->id_warehouse_admin) && intval($admin->id_warehouse_admin) > 0) {
		try {
			$db = LocalConnection::connect();
			$stmt = $db->prepare('SELECT id_office_warehouse FROM warehouses WHERE id_warehouse = :wh LIMIT 1');
			$stmt->execute([':wh' => intval($admin->id_warehouse_admin)]);
			$whOffice = $stmt->fetchColumn();
			if ($whOffice) {
				$id = intval($whOffice);
			}
		} catch (Throwable $e) {
			error_log('[pos_auth] office resolve: ' . $e->getMessage());
		}
	}
	return $id;
}

/** Detecta la acción principal del POST (primer flag no vacío). */
function pos_detect_ajax_action(): ?string {
	if (empty($_POST)) {
		return null;
	}
	$skip = ['token', 'startAt', 'category', 'search', 'idOffice', 'isWholesale'];
	foreach (array_keys($_POST) as $key) {
		if (in_array($key, $skip, true)) {
			continue;
		}
		$val = $_POST[$key];
		if ($val === '' || $val === null) {
			continue;
		}
		return $key;
	}
	return null;
}

/** Requiere sesión activa; responde 401 y termina si no hay. */
function pos_require_session(): object {
	if (!pos_has_session()) {
		pos_fail(401, 'Sesión requerida. Inicie sesión nuevamente.');
	}
	return $_SESSION['admin'];
}

/** Requiere uno de los roles indicados. */
function pos_require_role(array $roles): object {
	$admin = pos_require_session();
	$rol = $admin->rol_admin ?? '';
	if (!in_array($rol, $roles, true) && $rol !== 'superadmin') {
		pos_fail(403, 'No tiene permisos para esta operación.');
	}
	return $admin;
}

/** Valida que id_admin del POST coincida con la sesión (salvo superadmin). */
function pos_verify_admin_id(int $postedId): int {
	$admin = pos_require_session();
	$sessionId = intval($admin->id_admin);
	if ($postedId > 0 && $postedId !== $sessionId && ($admin->rol_admin ?? '') !== 'superadmin') {
		pos_fail(403, 'Identidad de usuario no válida.');
	}
	return $sessionId;
}

/** Valida acceso a oficina (superadmin con office 0 puede cualquiera). */
function pos_verify_office_access(int $officeId): void {
	$admin = pos_require_session();
	if (($admin->rol_admin ?? '') === 'superadmin' && intval($admin->id_office_admin) === 0) {
		return;
	}
	$myOffice = pos_current_office_id();
	if ($officeId > 0 && $myOffice > 0 && $officeId !== $myOffice) {
		pos_fail(403, 'No tiene acceso a esta sucursal.');
	}
}

/**
 * Gate global: ejecutar antes de los handlers.
 * Bloquea todo POST excepto acciones públicas.
 */
function pos_enforce_ajax_auth(): void {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST)) {
		return;
	}
	foreach (pos_public_ajax_actions() as $action) {
		if (isset($_POST[$action])) {
			return;
		}
	}
	pos_require_session();
}

/** Rate limiting simple para login (5 intentos / 15 min por email+IP). */
function pos_rate_limit_login(string $email): bool {
	$max = 5;
	$window = 900;
	$key = 'login_' . md5(strtolower(trim($email)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
	$dir = sys_get_temp_dir() . '/unitech_rate_limit';
	if (!is_dir($dir)) {
		@mkdir($dir, 0700, true);
	}
	$file = $dir . '/' . $key;
	$now = time();
	$data = ['count' => 0, 'first' => $now];
	if (is_file($file)) {
		$raw = @file_get_contents($file);
		$parsed = $raw ? json_decode($raw, true) : null;
		if (is_array($parsed)) {
			$data = $parsed;
		}
	}
	if ($now - ($data['first'] ?? $now) > $window) {
		$data = ['count' => 0, 'first' => $now];
	}
	$data['count'] = ($data['count'] ?? 0) + 1;
	@file_put_contents($file, json_encode($data));
	return $data['count'] <= $max;
}

function pos_rate_limit_clear(string $email): void {
	$key = 'login_' . md5(strtolower(trim($email)) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
	$file = sys_get_temp_dir() . '/unitech_rate_limit/' . $key;
	if (is_file($file)) {
		@unlink($file);
	}
}
