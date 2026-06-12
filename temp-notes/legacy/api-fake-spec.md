# Spec — endpoints Lumeria fake en `api_fake_app`

Pedido para extender el `api_fake_app` (Laravel) con 2 endpoints que
simulen el lado servidor de Lumeria. Lo va a consumir el módulo
`syllabus-sync` del backend `apps/api` de **learnex**.

## Contexto

- `api_fake_app` corre en `http://localhost:2004` y ya tiene un grupo de
  rutas Vonex bajo `routes/api.php` mounted en `/v3`.
- El grupo `apikey` (Bearer header) es el que usamos — Lumeria nunca
  va a usar token-in-path; arrancan directo con Bearer.
- `GET /v3/service-health` ya existe (lo usamos como probe del
  test-connection del admin). No tocar.

## Endpoints a agregar

Ambos van **dentro del grupo `Route::middleware('apikey')`** (mismo bloque
donde están `/sedes`, `/ciclos`, etc.).

### Endpoint 1 — Lista de cursos de un ciclo

```
GET /v3/cycles/{cycle_id}/courses
Authorization: Bearer <api-key>
```

**Path param**:
- `cycle_id` (string) — el id del ciclo en Lumeria. Para el fake, **ignorar
  el valor** y servir siempre el mismo response (es un fixture). Acepta
  cualquier id, ej. `140`, `SAN_MAYO`, `"abc"`.

**Response 200**:

```json
{
  "cycle_id": "1",
  "name": "SEMIANUAL UNMSM-MAYO 2026",
  "courses": [
    { "course_id": "1",  "code": "01", "name": "ARITMÉTICA",             "subject_area_code": "03" },
    { "course_id": "2",  "code": "02", "name": "ÁLGEBRA",                "subject_area_code": "03" },
    ... (20 items en total, ver temp-notes/lumeria-cycle-courses.json)
  ]
}
```

**Notas del shape**:

- `name` (string) — nombre legible del syllabus/ciclo en Lumeria.
  Para el fake, hardcodeado al valor del fixture (no varía por
  `cycle_id`). El consumidor lo persiste en `syllabus.name` y lo usa
  como display del header del syllabus en la UI (admin/student/tutor).
  Ojo: hay otro `name` adentro de cada `courses[i]` (nombre del curso).
  Son campos distintos por path, no se confunden.
- `course_id` es **string** (no integer). El consumidor lo usa textual
  como path param del endpoint 2.
- `code` es código corto display (puede repetirse — fines visuales).
- `subject_area_code` es uno de **`"01"`, `"02"`, `"03"`, `"04"`**:
  - `01` = Letras
  - `02` = Ciencias
  - `03` = Números
  - `04` = Humanidades
- Los nombres están en **MAYÚSCULAS con tildes** y son strings UTF-8.

**El JSON completo está en** `temp-notes/lumeria-cycle-courses.json`. Servir
exactamente ese contenido.

---

### Endpoint 2 — Syllabus de un curso específico

```
GET /v3/cycles/{cycle_id}/courses/{course_id}/syllabus
Authorization: Bearer <api-key>
```

**Path params**:
- `cycle_id` — ignorar (mismo criterio que endpoint 1).
- `course_id` — debe matchear uno de los `course_id` devueltos por el
  endpoint 1 (`"1"` a `"20"`).

**Response 200** — árbol `topics → subtopics → weeks`:

```json
{
  "topics": [
    {
      "topic_id": "51953",
      "code": null,
      "name": "ÁNGULO TRIGONOMÉTRICO",
      "subtopics": [
        { "id": "900", "code": "02", "name": "SISTEMAS DE MEDICIÓN ANGULAR", "weeks": [5] },
        { "id": "957", "code": "01", "name": "DEFINICIÓN DE RAZONES...", "weeks": [3, 6, 9] }
      ]
    },
    {
      "topic_id": null,
      "code": null,
      "name": "TOPIC SIN ID EN LUMERIA",
      "subtopics": []
    }
  ]
}
```

**Notas del shape**:

- Topics tienen `topic_id` (string nullable — Lumeria a veces no lo da
  todavía); subtopics tienen `id` (string nullable también).
- `code` en topics suele venir `null`; en subtopics suele venir tipo
  `"02"`, `"15"` (código numérico zero-padded).
- `weeks` es **siempre un array** de integers entre 1 y N (donde N es
  la cantidad de semanas del ciclo, típicamente 20). El mismo subtopic
  puede aparecer en varias semanas → distribución real `[3, 6, 9]`.
- Topics sin subtopics → `"subtopics": []`.
- Si un topic tiene `topic_id: null`, todo el topic se ignora corriente
  arriba (el consumidor no lo procesa). Pero igual debe devolverse en
  el array, no skipearlo en el response.

**Para el sample real**, hay un fixture en
`temp-notes/lumeria-course-syllabus.json` con 24 topics + 68 subtopics
únicos — corresponde al `course_id=4` (TRIGONOMETRÍA).

**Edge case del `course_id`**: si llega un `course_id` que NO está
en la lista del endpoint 1 (ej. `999`), devolver `404` con body
`{ "error": "course_not_found" }`. NO devolver `{ "topics": [] }` porque
es ambiguo (puede confundirse con "curso sin syllabus").

---

## Fixture data — 20 cursos × 1 syllabus c/u

El fake tiene que servir **20 syllabuses distintos** (uno por
`course_id` 1-20). Hoy solo hay sample real para `course_id=4`. Hay
2 caminos para generar los otros 19:

### Camino A — Mismo fixture para los 20 (no recomendado)

Servir `temp-notes/lumeria-course-syllabus.json` para cualquier
`course_id` válido. Trade-off: el QA visual va a ver "ANATOMÍA → ÁNGULO
TRIGONOMÉTRICO" y se ve raro.

### Camino B — Generar 19 fixtures procedural (recomendado)

Generar contenido distinto por curso usando bancos de topics/subtopics
por área. Ejemplo:

- Cursos del área **03 (Números)**: topic banks tipo "ECUACIONES
  CUADRÁTICAS", "FUNCIONES TRIGONOMÉTRICAS", "GEOMETRÍA ANALÍTICA".
- Cursos del área **02 (Ciencias)**: "CINEMÁTICA", "ENLACE QUÍMICO",
  "GENÉTICA MENDELIANA".
- Cursos del área **01 (Letras)**: "MORFOLOGÍA", "ANÁLISIS LITERARIO",
  "GRAMMAR TENSES".
- Cursos del área **04 (Humanidades)**: "REVOLUCIÓN FRANCESA", "CULTURA
  CHAVÍN", "ESCUELAS FILOSÓFICAS".

Por cada syllabus:
- 8-15 topics (algunos con `topic_id: null` para realismo)
- 3-7 subtopics por topic con nombres del banco
- Distribución `weeks` deterministic: subtopics asignados a semanas
  1-20 con cierta repetición (algunos en 1-3 semanas distintas).

**Para generar**: un script PHP artisan (o Node prep-time) que tome el
contenido del banco y arme el JSON por curso. Output: 20 archivos
`storage/lumeria/syllabus-{1..20}.json` o un solo
`storage/lumeria/syllabuses.json` indexed por course_id.

---

## Auth + middleware

Ambos endpoints usan el **mismo middleware `apikey`** que las rutas Vonex
existentes. La apiKey hardcoded del fake es:

```
apifake_cddebd7dc3dcc9aa1da296d5995e5214dc6c23931a573b4a
```

El consumidor (learnex) la guarda encriptada y la manda en el header
`Authorization: Bearer <key>`. El middleware existente ya valida esto;
no hay que tocar nada de auth.

## Validación

Con el fake corriendo, debe pasar:

```bash
# Endpoint 1 — 200 con name + courses
curl -H "Authorization: Bearer apifake_..." http://localhost:2004/v3/cycles/140/courses
# → { "name": "SEMIANUAL ...", "courses": [ ... 20 items ... ] }

# Endpoint 2 — 200 con topics
curl -H "Authorization: Bearer apifake_..." http://localhost:2004/v3/cycles/140/courses/4/syllabus
# → { "topics": [ ... ] }

# Endpoint 2 con course_id desconocido — 404
curl -H "Authorization: Bearer apifake_..." http://localhost:2004/v3/cycles/140/courses/999/syllabus
# → 404 { "error": "course_not_found" }

# Sin auth — 401
curl http://localhost:2004/v3/cycles/140/courses
# → 401

# Con auth mal — 401
curl -H "Authorization: Bearer wrong" http://localhost:2004/v3/cycles/140/courses
# → 401
```

## Archivos de referencia (en este repo, learnex)

- `temp-notes/lumeria-cycle-courses.json` — body exacto del endpoint 1.
- `temp-notes/lumeria-course-syllabus.json` — body del endpoint 2 para
  `course_id=4` (TRIGONOMETRÍA).
- `temp-notes/syllabus.json` — sample original de Lumeria (242 KB). NO
  servir esto al consumidor; tiene metadata sobrante. Está acá solo
  como referencia de los nombres reales de topics/subtopics.

## Lo que NO hay que hacer

- No tocar las rutas Vonex existentes (`/sedes`, `/ciclos`, etc.).
- No agregar nuevos middlewares.
- No usar el modo token-in-path (`/cycles/.../syllabus/token=...`)
  — Lumeria es Bearer puro.
- No cachear los responses en Redis ni nada por el estilo — son
  fixtures, leer del filesystem (o hardcoded en el controller) es OK.
- No incluir el envelope `{ success, type, message, data }` del Lumeria
  real — el shape simplificado de los JSON adjuntos es el target.
