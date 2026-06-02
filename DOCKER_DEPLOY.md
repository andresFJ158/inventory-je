# Docker deploy POS

Este proyecto queda dividido en 3 servicios:

- `frontend`: aplicación web principal (carpeta raíz, excluyendo `api.pos`)
- `api`: backend en `api.pos`
- `db`: MariaDB con import automático desde `api.pos/u228744577_pos.sql`

## Requisitos

- Docker
- Docker Compose (plugin `docker compose`)

## Levantar el proyecto

```bash
docker compose up -d --build
```

## URLs

- Frontend: `http://localhost:8080`
- API: `http://localhost:8081`

## Notas

- La base de datos se crea con:
  - DB: `u228744577_pos`
  - Usuario: `root`
  - Password: `root`
- El SQL solo se importa en la primera creación del volumen (`db_data`).

Si necesitas reinicializar la base:

```bash
docker compose down -v
docker compose up -d --build
```
