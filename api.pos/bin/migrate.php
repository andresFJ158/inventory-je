#!/usr/bin/env php
<?php
/**
 * Ejecutor CLI de migraciones de esquema.
 * Uso: php bin/migrate.php
 */
if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	echo "Solo CLI\n";
	exit(1);
}

date_default_timezone_set('America/La_Paz');
require_once __DIR__ . '/../ajax/lib/LocalConnection.php';
require_once __DIR__ . '/../ajax/lib/SchemaMigrator.php';

echo "UniTech — migraciones de esquema\n";

try {
	$host = getenv('DB_HOST') ?: '127.0.0.1';
	$dbName = getenv('DB_NAME') ?: 'u228744577_pos';
	$user = getenv('DB_USER') ?: 'root';
	$pass = getenv('DB_PASS') ?: '';
	$port = getenv('DB_PORT') ?: '3306';

	$link = new PDO("mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4", $user, $pass, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	]);
	$link->exec('SET NAMES utf8mb4');

	if (SchemaMigrator::isApplied($link)) {
		echo "Esquema ya aplicado (schema_migrations). Nada que hacer.\n";
		exit(0);
	}

	SchemaMigrator::run($link);
	echo "Migraciones aplicadas correctamente.\n";
	exit(0);
} catch (Throwable $e) {
	fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
	exit(1);
}
