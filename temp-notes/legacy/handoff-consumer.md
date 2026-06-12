# Handoff — Cambios en API Vonex v3 (consumidor sync)

> Documento para el chat que mantiene el consumidor de Vonex (el feature
> de sync en la app principal). Vonex finalmente respondió con el contrato
> real de su API y hay que adaptar el cliente.
>
> El fake (`api-fake`) ya está actualizado al contrato real y testeado
> 1:1 contra `https://api.vonex.edu.pe/v3/`. Lo que sigue describe qué
> cambia y qué hay que tocar en el consumidor.

---

## TL;DR — checklist para el consumidor

- [ ] **Auth**: cambiar `Authorization: Bearer <token>` → token en el path: `GET /v3/sedes/token=<TOKEN>`.
- [ ] **`/ciclos`**: ya no devuelve `estado:"activo"|"cerrado"` (string). Ahora devuelve `activo:true|false` (bool). Si parseás `estado`, hay que cambiarlo.
- [ ] **`/sedes`**: nueva key `activo` (int `1`/`0`, **no bool**). Si tu schema/struct es estricto, agregala.
- [ ] **`/aulas/{id}/tutores`**: ya no envuelve en `pagination`. Devuelve solo `{"data":[...]}`. Si iterás pagination, dejá de hacerlo solo para este endpoint.
- [ ] **Manejo de auth fallida**: token inválido devuelve `404` con **body vacío** (no JSON). No intentes parsear JSON en esa branch.
- [ ] **`per_page`**: Vonex real lo ignora silenciosamente y siempre devuelve 50. No asumas que pasarlo cambia el tamaño de página.

---

## 1) Auth: token en el path (no bearer)

### Antes (lo que asumíamos)
```http
GET /v3/sedes HTTP/1.1
Host: api.vonex.edu.pe
Authorization: Bearer <TOKEN>
```

### Ahora (lo que Vonex realmente acepta)
```http
GET /v3/sedes/token=<TOKEN> HTTP/1.1
Host: api.vonex.edu.pe
```

El token va como **sufijo del path**, formato literal `token=<valor>`. No es query string (`?token=`), es parte del path.

### Aplicado a los 5 endpoints

| Endpoint | URL nueva |
|---|---|
| Sedes | `GET /v3/sedes/token=<TOKEN>` |
| Ciclos | `GET /v3/ciclos/token=<TOKEN>` |
| Aulas por ciclo | `GET /v3/ciclos/{ciclo_id}/aulas/token=<TOKEN>` |
| Alumnos por aula | `GET /v3/aulas/{aula_id}/alumnos/token=<TOKEN>` |
| Tutores por aula | `GET /v3/aulas/{aula_id}/tutores/token=<TOKEN>` |

### Paginación combinada con auth en path
```
GET /v3/aulas/1537/alumnos/token=<TOKEN>?page=2
```
La query string va después del path completo (incluyendo el `/token=...`).

### Token inválido / ausente
La API real responde **`404` con body vacío** (no es JSON). No intentes `JSON.parse` en esa branch — chequeá status code primero.

### Nota: Vonex migrará a bearer "en el futuro"
Ellos mencionaron que eventualmente pasarán a `Authorization: Bearer`. Cuando lo hagan, será cambiar URL + agregar header. Por ahora, **token en path es lo único que funciona contra el real**.

El fake nuestro acepta **ambos modos** activos (path y bearer) — el path se introdujo nuevo y el bearer quedó para curl/testing nuestro. Si te conviene, podés desarrollar el consumidor con path-mode y dejarlo así.

---

## 2) `/ciclos` — `estado` (string) → `activo` (bool)

### Antes (schema asumido)
```json
{
  "ciclo_id": "CIC-2025-2",
  "nombre": "Semestral 2025 - II seleccion",
  "fecha_inicio": "2025-08-04",
  "fecha_fin": "2025-12-12",
  "estado": "activo"
}
```

### Ahora (real)
```json
{
  "ciclo_id": "910",
  "nombre": "UNI - SEM INTENSIVO 0326",
  "fecha_inicio": "2026-03-02",
  "fecha_fin": "2026-08-14",
  "activo": true
}
```

### Qué cambia funcionalmente
- Campo `estado` (string `"activo"` o `"cerrado"`) → **eliminado**.
- Nuevo campo `activo` (bool `true` / `false`).
- Mapeo: ciclos antes `estado:"activo"` ahora `activo:true`; ciclos antes `estado:"cerrado"` ahora `activo:false`.
- Solo dos valores posibles. No hay un tercer estado.

### Acciones en el consumidor
- Renombrar campo en struct/DTO: `estado: string` → `activo: bool`.
- Si comparabas `if ciclo.estado == "activo"`, ahora `if ciclo.activo`.
- Si serializás/persistís, ajustar el schema de DB (puede ser cambio breaking aguas abajo).

---

## 3) `/sedes` — nuevo campo `activo` (int)

### Antes (schema asumido)
```json
{ "sede_id": "SEDE-LIMA", "nombre": "Sede Lima Centro", "ciudad": "Lima" }
```

### Ahora (real)
```json
{ "sede_id": "4", "nombre": "CRESPO Y CASTILLO", "ciudad": "OTRO", "activo": 1 }
```

### Qué cambia funcionalmente
- Nuevo campo `activo` (**int** `0` o `1`).
- **OJO**: aquí `activo` es **numérico**, no booleano. Este es el único endpoint donde `activo` es int. En `/ciclos` y `/aulas` es bool.
- Solo aparecen sedes con `activo:1` por ahora en la data observada, pero el esquema soporta `0`.

### Acciones en el consumidor
- Agregar campo `activo: int` (o `activo: number`) al struct/DTO de Sede.
- Si tu deserializer es estricto con tipos, no lo trates como bool — es int.
- Si vas a filtrar por sede activa: `sede.activo == 1`.

---

## 4) `/aulas/{id}/tutores` — sin envelope `pagination`

### Antes (schema asumido)
```json
{
  "data": [
    { "tutor_id": "TUT-...", "apellidos": "...", "nombres": "...", "correo": "...", "documento": "...", "estado": "activo" }
  ],
  "pagination": { "current_page": 1, "per_page": 50, "total": 1, "total_pages": 1, "has_more": false }
}
```

### Ahora (real)
```json
{
  "data": [
    { "tutor_id": "29488", "nombres": "Lila Nicole", "apellidos": "Verano Vidal", "correo": "lverano@vonex.edu.pe", "documento": "72206634", "estado": "activo" }
  ]
}
```

### Qué cambia funcionalmente
- **Solo este endpoint** pierde el envelope `pagination`. Los otros 4 lo conservan.
- Razón: tutores por aula son siempre <10 (típicamente 1-3). Vonex no pagina algo tan chico.
- El campo `estado` sigue siendo string (`"activo"`), no cambió a bool como en ciclos.

### Acciones en el consumidor
- Para tutores específicamente: no esperar `pagination`. Tratar `data` como lista completa.
- Si tu código genérico de "parsear respuesta paginada" se aplicaba a todos los endpoints, necesitás un caso especial para tutores (o cambiar la firma para que pagination sea opcional).

---

## 5) Lo que NO cambia (ignorar — son cosméticos)

Hay diferencias entre el schema viejo y el real que **no afectan al cliente** y que decidimos no tocar:

| Tema | Viejo (asumido) | Nuevo (real) | ¿Importa? |
|---|---|---|---|
| `sede_id`, `ciclo_id`, `aula_id`, `tutor_id` | `"SEDE-LIMA"`, `"CIC-2025-2"`, `"A2025-12560"` | `"4"`, `"910"`, `"1537"` | Solo si regexás formato. Ambos son `string`. |
| `ciudad` en `/sedes` | `"Lima"` (Title Case) | `"LIMA"` o `"OTRO"` (MAYÚSCULAS) | Solo si comparás case-sensitive. Normalizá con `.lower()` y listo. |
| Orden de keys en JSON | `nombres` antes que `apellidos`, etc. | Distinto orden | JSON.parse no preserva orden — irrelevante salvo comparación de strings crudos. |

**Acción**: no tocar nada para estos. Si tu código ya hacía `.lower()` o tratabas IDs como string opaco, no hay nada que cambiar.

---

## 6) Paginación — comportamiento global

| Aspecto | Comportamiento real Vonex |
|---|---|
| Envelope `pagination` | En 4 de 5 endpoints (no en `/tutores`) |
| `?page=N` | Funciona |
| `?per_page=N` | **Ignorado siempre** — devuelve `per_page:50` fijo |
| `per_page=-1` / `"abc"` / `10000` | `200` OK, parámetro descartado en silencio |

**Acción**: no asumir que `per_page` cambia algo. Si necesitás iterar todo, usá `page=1,2,3...` hasta que `has_more:false`.

Estructura del envelope `pagination` (no cambió):
```json
{ "current_page": 1, "per_page": 50, "total": 106, "total_pages": 3, "has_more": true }
```

---

## 7) Errores y edge cases

| Caso | Respuesta real |
|---|---|
| Token ausente o inválido | `404` con **cuerpo vacío** (no JSON) |
| `ciclo_id` / `aula_id` inexistente | `200` con `{"data":[], "pagination":{"total":0,...}}` (laxa) |
| `per_page` inválido | `200`, ignora el parámetro |

**Notar**: la API real es laxa con IDs inexistentes (devuelve 200 vacío, no 404). Si tu consumidor espera 404 para "no encontrado", revisalo.

---

## 8) Cómo testear contra el fake mientras desarrollás

El fake está en `api-fake` (Laravel, repo separado). Soporta **ambos modos de auth**: el path-mode (idéntico a Vonex real) y bearer (legacy/curl).

### Base URL
- Local fake: `http://localhost:2004/v3/`
- API key fake: `apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a`

### Endpoints token-en-path (recomendado, mimetiza Vonex real)
```
GET http://localhost:2004/v3/sedes/token=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
GET http://localhost:2004/v3/ciclos/token=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
GET http://localhost:2004/v3/ciclos/CIC-2025-1/aulas/token=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
GET http://localhost:2004/v3/aulas/A2025-00001/alumnos/token=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
GET http://localhost:2004/v3/aulas/A2025-00001/tutores/token=apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
```

### IDs válidos en el fake
- **Ciclos con aulas**: `CIC-2025-1`, `CIC-2025-2` (los otros 10 listan en `/ciclos` pero `/ciclos/{id}/aulas` devuelve 404 en el fake — recordatorio: Vonex real devuelve 200 vacío, este es un caveat conocido del fake).
- **Aulas**: `A2025-00001` a `A2025-00006`.
- **Sedes**: 10 sedes (`SEDE-LIMA`, `SEDE-AREQ`, etc.) — IDs prefijados, distintos del real (numéricos).

### Endpoints bearer (sigue activo en el fake, no usa el real)
```
GET http://localhost:2004/v3/sedes
Header: Authorization: Bearer apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
```
Útil solo para tests internos del fake. **No desarrolles el consumidor en este modo** porque no es el contrato real de Vonex.

---

## 9) Cómo validar contra Vonex real

Si querés verificar contra producción real (necesita IP autorizada por Vonex y User-Agent de navegador real — curl con UA default lo bloquea Cloudflare):

```bash
# Token real (rotar si se filtra):
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJTRUNSRVRfS0VZIjoicEhESzNYdHNybU9GbUpxQTZERTJZS0lmVzhYbSJ9.az--C23ljXeCeo1T00vROrYa45hXmaJAisp4nw3utZ8"

curl -A "Mozilla/5.0 ..." "https://api.vonex.edu.pe/v3/sedes/token=$TOKEN"
```

IDs reales para probar (de la última corrida):
- ciclo con aulas: `910`, `931`
- aula con alumnos+tutores: `1537`

---

## Resumen — qué tocar en el consumidor

1. **HTTP client** — cambiar construcción de URL: agregar `/token=<TOKEN>` al final del path, eliminar header `Authorization`.
2. **DTO/struct de Ciclo** — `estado: string` → `activo: bool`.
3. **DTO/struct de Sede** — agregar `activo: int`.
4. **Deserializer de tutores** — quitar expectativa de `pagination`, parsear solo `data`.
5. **Manejo de error de auth** — `404 + body vacío`, no `401 + JSON`.
6. **Lógica de paginación** — no pasar `per_page` esperando que cambie nada; iterar con `page`.
7. **Si comparabas `ciudad` o IDs case-sensitive** — revisar (`OTRO` vs `Otro`, IDs numéricos vs prefijados).
