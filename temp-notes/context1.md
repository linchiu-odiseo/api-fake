# Context 1 — Estado actual de api-fake

> Snapshot del 2026-06-01. Sirve como primer contexto al retomar el proyecto.

## Propósito
Fake de `https://api.vonex.edu.pe/v3/` para desarrollar y probar la integración **Vonex Intranet sync** en el app principal (`localhost:3001/t/vonex/...`) sin depender de la API real de Vonex.

## Stack
- Laravel 11 (PHP 8.2), Docker (`api_fake_app` + `api_fake_postgres`).
- Postgres **no** persiste datos de dominio — solo lo usa Laravel para sessions/cache/queue.
- Data fija en arrays PHP en `src/app/Services/FakeDataGenerator.php`.

## Base URL
- Local: `http://localhost:2004/v3/`
- Prefijo configurado en `bootstrap/app.php` vía `apiPrefix: 'v3'`.

## Autenticación
- Middleware `apikey` (`App\Http\Middleware\EnsureApiKey`).
- Header acepta `X-API-Key: <key>` o `Authorization: Bearer <key>`.
- Key actual en `.env`: `API_KEY=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a`.
- Sin key → `401 {"message":"API key invalida o ausente."}`.

## Endpoints implementados (de los 4 del entregable)

| Método | Ruta | Controller |
|---|---|---|
| GET | `/v3/` | `Vonex\StatusController@index` |
| GET | `/v3/sedes` | `Vonex\SedeController@index` |
| GET | `/v3/ciclos/{ciclo_id}/aulas` | `Vonex\CicloController@aulas` |
| GET | `/v3/aulas/{aula_id}/alumnos` | `Vonex\AulaController@alumnos` |
| GET | `/v3/aulas/{aula_id}/tutores` | `Vonex\AulaController@tutores` |

Todas paginadas (`?page=`, `?per_page=`, default 50, max 100).
`404` si `ciclo_id` o `aula_id` no existe en los arrays.

## Catálogo de IDs válidos hoy

**Sedes (10):**
`SEDE-LIMA`, `SEDE-AREQ`, `SEDE-TRUJ`, `SEDE-CUSC`, `SEDE-PIUR`, `SEDE-CHIC`, `SEDE-ICA`, `SEDE-TACN`, `SEDE-HUAN`, `SEDE-PUNO`

**Ciclos (2):**
- `CIC-2025-1` → aulas `A2025-00001`, `A2025-00002`, `A2025-00003`
- `CIC-2025-2` → aulas `A2025-00004`, `A2025-00005`, `A2025-00006`

**Por aula:** 10 alumnos + 3 tutores (todos hardcodeados).

## Cómo agregar datos
Todo se edita en `src/app/Services/FakeDataGenerator.php`:

| Quiero añadir... | Edito... |
|---|---|
| Sede | array `SEDES` |
| Ciclo nuevo | clave en `AULAS_BY_CICLO` con sus aulas |
| Aula a ciclo existente | sub-array del ciclo en `AULAS_BY_CICLO` |
| Alumno | sub-array del `aula_id` en `ALUMNOS_BY_AULA` |
| Tutor | sub-array del `aula_id` en `TUTORES_BY_AULA` |

Laravel recarga el archivo en cada request — **no hay caches que limpiar**.

## Quick test

```bash
K="apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a"
curl -s -H "X-API-Key: $K" http://localhost:2004/v3/
curl -s -H "X-API-Key: $K" http://localhost:2004/v3/sedes
curl -s -H "X-API-Key: $K" http://localhost:2004/v3/ciclos/CIC-2025-1/aulas
curl -s -H "X-API-Key: $K" http://localhost:2004/v3/aulas/A2025-00001/alumnos
curl -s -H "X-API-Key: $K" http://localhost:2004/v3/aulas/A2025-00001/tutores
```

## Próximo trabajo planeado
- Añadir Scramble (`dedoc/scramble`) para docs OpenAPI auto-generadas en `/docs/api`.
- Implementar endpoints restantes del spec en `PETICION-VONEX.md` cuando se necesiten (`/sedes/{id}/aulas`, `/ciclos/{id}/alumnos`, `/tutores/...`, etc).

## Referencias
- Spec original: `temp-notes/PETICION-VONEX.md`
- Plan del feature en el app principal: `temp-notes/plan.md` (UTF-16, ver con cuidado)
