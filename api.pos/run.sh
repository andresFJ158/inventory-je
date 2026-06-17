#!/bin/bash
export DB_HOST=127.0.0.1
export DB_PORT=3307
export DB_NAME=u228744577_pos
export DB_USER=root
export DB_PASS=root

# Liberar el puerto si está ocupado
lsof -i :8081 -t | xargs kill -9 2>/dev/null
sleep 1

exec php -S localhost:8081 router.php
