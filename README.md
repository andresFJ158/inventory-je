# UniTech 2.0 — POS + Laboratorio

Sistema integrado de punto de venta y gestión de laboratorio.

## Requisitos

- Docker y Docker Compose, o
- PHP 8.2 + Apache, MariaDB 11.x, Node 20 + pnpm

## Inicio rápido con Docker

```bash
cp api.pos/.env.example api.pos/.env
cp lab-dashboard/.env.example lab-dashboard/.env
docker compose up --build
```

- Dashboard: http://localhost:8080
- API PHP: http://localhost:8081

## Variables de entorno

Ver `api.pos/.env.example` y `lab-dashboard/.env.example`.

**Importante:** En producción, rotar `API_KEY`, `JWT_SECRET` y `API_TOKEN`.

## Documentación de planes

- `PLAN_CORRECCIONES.md` — funcionalidades de negocio
- `PLAN_ESTABILIZACION_TECNICA.md` — correcciones técnicas y seguridad
