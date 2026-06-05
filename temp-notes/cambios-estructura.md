# Cambios de estructura — `peticion.md` (plan) vs API real

Documento para llevar al chat donde se modifica el fake API.
Resume **qué cambia** la API real respecto al `peticion.md` original.

---

## TL;DR — checklist de cambios que sí afectan

- [ ] `/ciclos`: cambiar `estado:"activo"/"cerrado"` (string) → `activo:true/false` (bool).
- [ ] `/aulas/{id}/tutores`: quitar el objeto `pagination` (la real no lo devuelve).
- [ ] `/sedes`: agregar campo `activo` numérico (`1` o `0`, **int, no bool**).
- [ ] `per_page` en query string: **ignorar siempre**, devolver fijo `per_page:50` en `pagination`.

### Cosméticos — no afectan al cliente, sólo si querés mimetizar 1:1

- IDs numéricos (`"4"`, `"910"`) vs prefijados (`"SEDE-LIMA"`, `"CIC-2025-2"`): ambos son string, sólo importa si el cliente regexea el formato.
- `ciudad` en MAYÚSCULAS (`"LIMA"`, `"OTRO"`): sólo importa si comparás case-sensitive o lo mostrás sin normalizar.
- Orden de campos en el JSON (`nombres` antes que `apellidos`, `modalidad` antes que `turno`): JSON.parse no preserva orden — irrelevante salvo comparación de strings crudos.

---

## Endpoint 1 — `GET /sedes`

### Spec original
```json
{ "sede_id": "SEDE-LIMA", "nombre": "Sede Lima Centro", "ciudad": "Lima" }
```

### API real
```json
{ "sede_id": "4", "nombre": "CRESPO Y CASTILLO", "ciudad": "OTRO", "activo": 1 }
```

**Cambios para el fake:**
- `sede_id`: string **numérico** (`"4"`), no `"SEDE-XXX"`.
- `ciudad`: en MAYÚSCULAS. Cuando no aplica, usar `"OTRO"`.
- **Agregar `activo`** (int `0`/`1`, **no booleano**) — único endpoint donde `activo` es numérico.

---

## Endpoint 2 — `GET /ciclos/{ciclo_id}/aulas`

### Spec original
```json
{
  "aula_id": "A2025-12560",
  "codigo": "AULA-2025-1-A",
  "nombre": "Aula A - Mañana",
  "sede": { "sede_id": "SEDE-LIMA", "nombre": "Sede Lima Centro" },
  "turno": "manana",
  "modalidad": "presencial",
  "capacidad": 30,
  "activo": true
}
```

### API real
```json
{
  "aula_id": "1537",
  "codigo": "SMSAN0326P1A",
  "nombre": "LCE-USM-SAN-0326-P-A",
  "modalidad": "presencial",
  "turno": "manana",
  "capacidad": 106,
  "activo": true,
  "sede": { "sede_id": "6", "nombre": "LIMA CERCADO" }
}
```

**Cambios para el fake:**
- `aula_id` y `sede.sede_id`: string numérico.
- Orden de campos: `modalidad` antes que `turno`; `sede` al final (no después de `nombre`). _No es funcional, pero si querés mimetizar 1:1._
- `activo`: **bool** (acá sí), igual que la spec.

---

## Endpoint 3 — `GET /aulas/{aula_id}/alumnos`

### Spec original
```json
{ "codigo": "20251001", "apellidos": "...", "nombres": "...", "correo": "...", "estado": "activo" }
```

### API real
```json
{ "codigo": "61519585", "nombres": "Jhann Pool Julio", "apellidos": "Alvarado Estrada", "correo": "61519585@vonex.edu.pe", "estado": "activo" }
```

**Cambios para el fake:**
- Orden: `nombres` antes que `apellidos` (la spec los tiene al revés).
- Resto idéntico a la spec ✓.

---

## Endpoint 4 — `GET /aulas/{aula_id}/tutores`

### Spec original
```json
{
  "data": [
    { "tutor_id": "TUT-2025-1-A", "apellidos": "...", "nombres": "...", "correo": "...", "documento": "12345678", "estado": "activo" }
  ],
  "pagination": { "current_page": 1, "per_page": 50, "total": 1, "total_pages": 1, "has_more": false }
}
```

### API real
```json
{
  "data": [
    { "tutor_id": "29488", "nombres": "Lila Nicole", "apellidos": "Verano Vidal", "correo": "lverano@vonex.edu.pe", "documento": "72206634", "estado": "activo" }
  ]
}
```

**Cambios para el fake:**
- **Eliminar el objeto `pagination`** (la real no lo devuelve — son siempre <10 tutores).
- `tutor_id`: string numérico.
- Orden: `nombres` antes que `apellidos`.

---

## Endpoint 5 — `GET /ciclos`

### Spec original
```json
{
  "ciclo_id": "CIC-2025-2",
  "nombre": "Semestral 2025 - seleccion",
  "fecha_inicio": "2026-05-01",
  "fecha_fin": "2026-07-31",
  "estado": "activo"
}
```

### API real
```json
{
  "ciclo_id": "910",
  "nombre": "UNI - SEM INTENSIVO 0326",
  "fecha_inicio": "2026-03-02",
  "fecha_fin": "2026-08-14",
  "activo": true
}
```

**Cambios para el fake:**
- `ciclo_id`: string numérico (`"910"`), no `"CIC-XXXX"`.
- **Reemplazar `estado:"activo"/"cerrado"` (string)** → **`activo:true/false` (bool)**.
- Sólo dos estados representables (activo / inactivo). Si hay un ciclo "cerrado", queda en `activo:false`.

---

## Paginación — comportamiento global

| Aspecto | Spec / esperado | Real |
|---------|-----------------|------|
| Envelope `pagination` | En los 5 endpoints | En 4 de 5 (no en `/tutores`) |
| `?page=N` | Funciona | Funciona ✓ |
| `?per_page=N` | Configurable | **Ignorado siempre** — devuelve `per_page:50` fijo |
| `per_page` inválido | 400 | Ignorado silenciosamente (siempre 50) |

**Para el fake:**
```js
// regla simple
const PER_PAGE = 50; // fijo, ignorar query.per_page
const page = Math.max(1, parseInt(req.query.page) || 1);
```

Estructura del objeto `pagination` real (igual a la spec):
```json
{ "current_page": 1, "per_page": 50, "total": 106, "total_pages": 3, "has_more": true }
```

---

## Errores / edge cases (la real es laxa, el fake puede copiarla)

| Caso | Real |
|------|------|
| Token ausente o inválido | `404` con cuerpo vacío (no JSON) |
| `ciclo_id` / `aula_id` inexistente | `200` con `{"data":[], "pagination":{"total":0,...}}` |
| `per_page=-1` / `"abc"` / `10000` | `200`, ignora el parámetro |

Si el fake replica esto se acopla 1:1. Si quisieras endurecerlo (401 para auth, 404 para IDs), el cliente _podría_ romper si asume el comportamiento laxo actual.

---

## Auth — token en path

La real espera el token como sufijo del path:
```
GET https://api.vonex.edu.pe/v3/sedes/token=<JWT>
GET https://api.vonex.edu.pe/v3/sedes/token=<JWT>?page=2
```

No es header `Authorization: Bearer`. El fake debe parsear `/token=...` como parte de la ruta (no como query string) y aceptarlo en cualquier endpoint.
