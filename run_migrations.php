<?php
require 'api.pos/load_env.php';
$_ENV['DB_HOST'] = getenv('DB_HOST') ?: $_ENV['DB_HOST'];
$_ENV['DB_NAME'] = getenv('DB_NAME') ?: $_ENV['DB_NAME'];
$_ENV['DB_USER'] = getenv('DB_USER') ?: $_ENV['DB_USER'];
$_ENV['DB_PASS'] = getenv('DB_PASS') ?: $_ENV['DB_PASS'];

putenv('DB_HOST=' . $_ENV['DB_HOST']);
putenv('DB_NAME=' . $_ENV['DB_NAME']);
putenv('DB_USER=' . $_ENV['DB_USER']);
putenv('DB_PASS=' . $_ENV['DB_PASS']);

require 'api.pos/bin/migrate.php';
