#!/bin/bash
set -e
php /var/www/html/bin/migrate.php || echo "Migrate warning (continuing)"
exec apache2-foreground
