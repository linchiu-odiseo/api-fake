# Reporte 2 — Re-test API Vonex v3

Fecha: 2026-06-05
Base: `https://api.vonex.edu.pe/v3`
Token: el de `como probar.md` (en path).

## Correcciones del reporte anterior

- **`/ciclos` paginación `page=N`**: confirmado, **sí funciona**. En el reporte 1 marqué esto como bug; era un mal cálculo mío. `/ciclos` sólo tiene 25 ciclos (≤50 = per_page por defecto), por eso `?page=2` devuelve `data:[]` con HTTP 200 — eso es comportamiento correcto, no un fallo.
- Lo mismo aplica a `/sedes` (15 items) y `/ciclos/{id}/aulas` (20 items en ciclo 931): `page=2` vacío es esperable.

## Resumen ejecutivo

| # | Endpoint | HTTP | Schema | `page` | `per_page` | Estado |
|---|----------|------|--------|--------|------------|--------|
| 1 | `GET /sedes` | 200 | Parcial | ✓ (no aplica, 15 items) | ✗ ignorado | OK funcional |
| 2 | `GET /ciclos/{id}/aulas` | 200 | OK | ✓ (no aplica, 20 items) | ✗ ignorado | OK funcional |
| 3 | `GET /aulas/{id}/alumnos` | 200 | OK | **✓ funciona** (probado 3 páginas, 106 items) | ✗ ignorado | OK funcional |
| 4 | `GET /aulas/{id}/tutores` | 200 | OK ítems | **✗ sin paginación** | ✗ | **Aún roto** |
| 5 | `GET /ciclos` | 200 | Diverge | ✓ (no aplica, 25 items) | ✗ ignorado | OK funcional, schema diverge |

**`page=N` funciona en todos los endpoints menos `tutores`.** **`per_page` sigue ignorado** en todos. Lo único realmente paginado en este momento es `/aulas/{id}/alumnos` porque su dataset supera el 50 forzado.

---

## Lo que sigue mal

### 1. `per_page` sigue ignorado en todos los endpoints

Probado en los 4 endpoints paginables:

```
GET /sedes/token=...?per_page=5    → data.length=15, per_page=50 en respuesta
GET /ciclos/token=...?per_page=5   → data.length=25, per_page=50
GET /ciclos/931/aulas/...?per_page=5  → data.length=20, per_page=50
GET /aulas/1537/alumnos/...?per_page=10 → data.length=50, per_page=50
```

El servidor **siempre fija `per_page:50`** sin importar el query param. Si el plan es dejarlo fijo, hay que documentarlo. Si no, hay que implementar el parseo del parámetro.

### 2. `/aulas/{id}/tutores` sigue sin objeto `pagination`

```json
GET /aulas/1537/tutores/token=...
{
  "data":[{"tutor_id":"29488","nombres":"Lila Nicole","apellidos":"Verano Vidal",...}]
}
```

- No devuelve `pagination` (`peticion.md` línea 88 sí la exige).
- `?page=2` devuelve el mismo tutor (no paginó nada, simplemente lo ignora).

Hay que envolver la respuesta igual que los otros 4 endpoints para tener envelope uniforme.

### 3. Schema `/ciclos` — `estado` vs `activo`

Sigue devolviendo:

```json
{"ciclo_id":"910","nombre":"...","fecha_inicio":"...","fecha_fin":"...","activo":true}
```

La spec (`peticion.md` línea 103) pide `"estado":"activo"` o `"cerrado"`. La implementación actual con `activo:true/false` no permite representar el estado `"cerrado"` u otros. Hay que decidir:

- **Opción A**: cambiar API → devolver `estado:"activo"|"cerrado"`.
- **Opción B**: actualizar `peticion.md` para reflejar el bool real y aceptar que no hay estado "cerrado".

---

## Verificación de que `page` realmente pagina (no devuelve la misma data)

`/aulas/1537/alumnos` con 106 alumnos — ids de cada página:

| Page | n | Primer codigo | Segundo codigo |
|------|---|---------------|----------------|
| 1 | 50 | 61519585 | 70419530 |
| 2 | 50 | 77973214 | 71153647 |
| 3 | 6  | 60993594 | 73279314 |

50 + 50 + 6 = 106 ✓, ids distintos por página ✓. Paginación correcta.

---

## Cambios desde el reporte 1

| Issue del reporte 1 | Estado ahora |
|---------------------|-------------|
| `/ciclos` `page=2` regresa vacío → **bug** | **Falso positivo mío** — sólo hay 25 ciclos, cabe en una página |
| `per_page` ignorado globalmente | Sigue igual ✗ |
| `/aulas/{id}/tutores` sin `pagination` | Sigue igual ✗ |
| Schema `/ciclos` con `activo` en vez de `estado` | Sigue igual (decisión pendiente) |
| IDs sin prefijo (`"4"` vs `"SEDE-LIMA"`) | Sigue igual — asumir que la spec se actualiza |
| 401 esperado, llega 404 vacío sin token | Sigue igual |
| IDs inexistentes regresan 200 + `data:[]` | Sigue igual |

---

## Anexo

Respuestas crudas en `RESUMEN/out2/*.json`. Comando base:

```bash
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36"
TOKEN="eyJ0eXAi...nw3utZ8"
curl -s --ssl-no-revoke -A "$UA" "https://api.vonex.edu.pe/v3/<recurso>/token=$TOKEN?page=N&per_page=M"
```
