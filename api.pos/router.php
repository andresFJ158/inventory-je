<?php
// Router para php -S (servidor built-in)
// Maneja las reescrituras que normalmente hace .htaccess

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api.pos', '', $uri); // Si está bajo /api.pos

// Archivos estáticos
if (is_file(__DIR__ . $uri)) {
    return false;
}

// Directorios
if (is_dir(__DIR__ . $uri)) {
    return false;
}

// Todo lo demás va a index.php
require __DIR__ . '/index.php';
