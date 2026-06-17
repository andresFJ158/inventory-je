<?php
/**
 * Simple .env loader for PHP without composer dependencies.
 */
(function() {
    $envPath = __DIR__ . '/.env';
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        // Split on the first '=' character
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        list($name, $value) = $parts;
        $name = trim($name);
        $value = trim($value);

        // Remove quotes if present
        if (preg_match('/^"([^"]*)"$/', $value, $matches) || preg_match('/^\'([^\']*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }

        // Only set if not already defined (allows OS environment variables to override)
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
})();
