# API-FAKE Fase 2 — Endpoints de cartilla de marcaciones

> **Para:** equipo de API-FAKE (Laravel + Sanctum + Postgres).
> **De:** equipo de NeonPanda (PWA Angular).
> **Estado:** contrato locked. El frontend ya está implementado contra este contrato (376 tests verde). Falta tu lado.

## Resumen ejecutivo

Necesitamos **2 endpoints nuevos** + **1 header de respuesta** para habilitar la cartilla de marcaciones de Fase 2:

| Qué | Endpoint | Para qué |
|---|---|---|
| GET | `/v3/simulacros` | Lista de simulacros del día del alumno + hora oficial del servidor |
| POST | `/v3/simulacros/:id/envio` | Envío de marcaciones del alumno |
| Header | `X-New-Bearer` | Renovación rolling del bearer (TTL 6h) |

Ambos endpoints:
- **Requieren** `Authorization: Bearer` válido (mismo middleware Sanctum que Fase 1).
- **Requieren** `X-API-Key` (mismo header de Fase 1).
- Reusan el usuario único de prueba: `fulano@panda.test` / `12345678`.

---

## Modelo conceptual

### Estados del simulacro (4, derivados por backend)

El backend **deriva el `estado` en cada GET** a partir de `(inicio, fin, hubo_envío_recibido, now)`. **No se almacena como columna** — se computa.

| Estado | Cuándo |
|---|---|
| `pendiente` | `now < inicio` |
| `abierto` | `inicio ≤ now ≤ fin` AND sin envío recibido |
| `enviado` | hubo envío recibido (con `clientSubmittedAt` válido) |
| `cerrado` | `now > fin` AND sin envío recibido |

**No existe estado `atrasable` ni `atrasado`.** Pasado `fin` sin envío, queda `cerrado` y terminal — el alumno no puede dar el examen.

### Los 2 tiempos

| Tiempo | Quién lo dice | Para qué |
|---|---|---|
| **`clientSubmittedAt`** | Cliente (en el body del POST) | Cuándo el alumno "terminó" el examen. **Se confía en el cliente.** |
| **`serverReceivedAt`** | Backend (`now()` al recibir) | Auditoría. NO afecta el estado del simulacro. |

**Regla clave:** el cliente puede mandar el POST minutos o horas después de `clientSubmittedAt` (porque su red estaba caída). El backend acepta y registra el `clientSubmittedAt` como verdad **siempre que caiga en `[inicio, fin]`**.

Ejemplo: alumno marca a las 8:55 sin red, red vuelve a las 9:30, POST llega a las 9:30 con `clientSubmittedAt: "8:55"`. Backend: 200 OK, `clientSubmittedAt: 8:55`, `serverReceivedAt: 9:30`. **Queda como `enviado` a las 8:55**, no como tarde.

### Garantía que pedimos a backend

**Backend NUNCA debe asignar a un mismo alumno dos simulacros con horarios solapados.** El cliente asume que a lo más un `abierto` simultáneo por alumno. Si esto no se cumple, el cliente degrada con un warning (loguea en consola) pero la UX se confunde.

---

## Endpoint 1 — `GET /v3/simulacros`

### Request

```http
GET /v3/simulacros HTTP/1.1
Host: localhost:2004
X-API-Key: {{env.API_KEY}}
Authorization: Bearer {{token}}
Accept: application/json
```

Sin body. Sin query params en Fase 2 (en Fase 2.x agregaremos `?date=YYYY-MM-DD` para historial).

### Response 200 OK

```json
{
  "serverTime": "2026-06-12T08:15:05-05:00",
  "simulacros": [
    {
      "id": "uuid-or-string",
      "area": "Matemática",
      "name": "Simulacro 03 — Razonamiento",
      "count": 20,
      "inicio": "2026-06-12T08:00:00-05:00",
      "fin":    "2026-06-12T09:00:00-05:00",
      "estado": "abierto"
    },
    {
      "id": "otro-uuid",
      "area": "Comunicación",
      "name": "Simulacro 03 — Lectura crítica",
      "count": 20,
      "inicio": "2026-06-12T10:00:00-05:00",
      "fin":    "2026-06-12T11:00:00-05:00",
      "estado": "pendiente"
    }
  ]
}
```

### Reglas de la response

- **`serverTime`**: ISO8601 con timezone, hora del servidor al momento de armar la respuesta. El cliente la usa para anclar countdowns y bloqueos de entrada.
- **`simulacros`**: array (puede estar vacío si el alumno no tiene simulacros hoy).
- **`id`**: string no vacío. UUID o slug, lo que prefieras.
- **`area`**: string no vacío. Ej: "Matemática", "Comunicación", "Física".
- **`name`**: string no vacío. Lo verá el alumno como título de la card.
- **`count`**: entero positivo. Cantidad de preguntas del simulacro (típicamente 20).
- **`inicio`** y **`fin`**: ISO8601 con timezone. `fin > inicio` siempre.
- **`estado`**: uno de `"pendiente"`, `"abierto"`, `"enviado"`, `"cerrado"`. Derivado por backend en cada GET.

### Status codes

| Code | Cuándo | Body |
|---|---|---|
| `200 OK` | OK (incluyendo lista vacía) | shape de arriba |
| `401 Unauthorized` | bearer expirado/revocado | `{ "message": "..." }` (cliente clasifica solo por status, ignora message) |
| `5xx` | error de backend | `{ "message": "..." }` |

### Notas de implementación

- **Filtrar simulacros por el alumno autenticado** (la sesión Sanctum identifica al usuario).
- **Validar no-overlap al asignar** (no al consultar). Si por bug entran dos `abierto` simultáneos, devuelves ambos y el cliente degrada con warning.
- **El día académico** se define por el timezone del servidor. Si necesitas zona configurable, déjala fija en Fase 2 y la parametrizamos en Fase 2.x.

---

## Endpoint 2 — `POST /v3/simulacros/:id/envio`

### Request

```http
POST /v3/simulacros/{id}/envio HTTP/1.1
Host: localhost:2004
X-API-Key: {{env.API_KEY}}
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json

{
  "answers": {
    "1": "C",
    "2": "A",
    "3": null,
    "4": "B",
    "5": "C",
    "6": "D",
    "7": "E",
    "8": "A",
    "9": null,
    "10": "C",
    "11": "B",
    "12": "C",
    "13": "A",
    "14": null,
    "15": "D",
    "16": "E",
    "17": "C",
    "18": "B",
    "19": "A",
    "20": "C"
  },
  "clientSubmittedAt": "2026-06-12T08:47:00-05:00"
}
```

### Reglas del body

- **`answers`**: objeto plano. Keys son strings numéricos `"1"` a `"count"`. Values son `"A"|"B"|"C"|"D"|"E"|null`. El `null` significa "el alumno no marcó nada en esa pregunta".
- **`clientSubmittedAt`**: ISO8601 con timezone. Es el momento en que el alumno terminó el examen según su reloj anclado al server (no su reloj local; el cliente computa offset al recibir el `serverTime` del GET).

### Validaciones del backend

| Validación | Si falla |
|---|---|
| Existe el simulacro `:id` AND está asignado al alumno autenticado | `404 Not Found` |
| El simulacro no está ya en estado `cerrado` (es decir, `now ≤ fin` o hubo envío exitoso previo) | `403 Forbidden` con `code: "CLOSED"` |
| `clientSubmittedAt` cae en `[inicio, fin]` (inclusive) | `400 Bad Request` con `code: "INVALID_TIME"` |
| `Object.keys(answers).length === count` exacto | `400 Bad Request` con `code: "INVALID_SHAPE"` |
| Cada value en `answers` es uno de `"A"`, `"B"`, `"C"`, `"D"`, `"E"`, `null` | `400 Bad Request` con `code: "INVALID_SHAPE"` |
| Las keys de `answers` son strings numéricos de `"1"` a `"count"` sin huecos | `400 Bad Request` con `code: "INVALID_SHAPE"` |

### Idempotencia

Si el alumno ya envió este simulacro antes (con éxito), el segundo POST debe devolver **`409 Conflict`** reusando los datos del primer envío. **No es un error desde la UX del cliente — lo trata como éxito.** Esto cubre el caso "el alumno envió desde su celular, vuelve a entrar desde una tablet, intenta enviar otra vez".

### Status codes y responses

| Code | Body | Cuándo |
|---|---|---|
| `200 OK` | `{ "status": "enviado", "clientSubmittedAt": "...", "serverReceivedAt": "..." }` | Envío aceptado dentro de ventana |
| `409 Conflict` | `{ "status": "enviado", "clientSubmittedAt": "...", "serverReceivedAt": "..." }` (los del primer envío) | Idempotencia — ya envió antes |
| `400 Bad Request` | `{ "message": "...", "code": "INVALID_TIME" }` | `clientSubmittedAt` fuera de `[inicio, fin]` |
| `400 Bad Request` | `{ "message": "...", "code": "INVALID_SHAPE" }` | answers length / values / keys inválidos |
| `403 Forbidden` | `{ "message": "...", "code": "CLOSED" }` | Simulacro ya cerrado terminal |
| `404 Not Found` | `{ "message": "..." }` | Simulacro no asignado a este alumno |
| `401 Unauthorized` | `{ "message": "..." }` | Bearer expirado/inválido |
| `5xx` | `{ "message": "..." }` | Error de backend |

### Reglas críticas de respuestas de error

1. **Siempre incluye `code`** en errores 400 y 403 (donde aplica). El cliente clasifica por `(status, code)`, **NUNCA** por el `message`. El `message` es libre para que tú lo uses para logging o para mostrar en debug, pero el cliente lo ignora.

2. **`serverReceivedAt`** en las respuestas exitosas es opcional pero recomendado para auditoría — registra cuándo tu backend recibió el POST (puede diferir de `clientSubmittedAt` si la red llegó tarde).

3. **El 200 con `status: "enviado"` y el 409 con `status: "enviado"` deben tener el mismo shape** para que el cliente los procese igual (colapsa 409 a éxito).

### Ejemplo: envío exitoso dentro de ventana

```http
POST /v3/simulacros/abc-123/envio
{
  "answers": { "1": "C", ..., "20": "B" },
  "clientSubmittedAt": "2026-06-12T08:47:00-05:00"
}

→ 200 OK
{
  "status": "enviado",
  "clientSubmittedAt": "2026-06-12T08:47:00-05:00",
  "serverReceivedAt": "2026-06-12T08:47:02-05:00"
}
```

### Ejemplo: envío tardío (red volvió después de fin)

El simulacro Mate tenía ventana `[08:00, 09:00]`. El alumno marcó a las 08:55 sin red. La red volvió a las 09:30. El cliente despacha entonces:

```http
POST /v3/simulacros/abc-123/envio
{
  "answers": { "1": "C", ..., "20": "B" },
  "clientSubmittedAt": "2026-06-12T08:55:00-05:00"
}

→ 200 OK    (porque 08:55 está en [08:00, 09:00])
{
  "status": "enviado",
  "clientSubmittedAt": "2026-06-12T08:55:00-05:00",
  "serverReceivedAt": "2026-06-12T09:30:14-05:00"
}
```

**El simulacro queda `enviado` a las 08:55.** El `serverReceivedAt` 09:30 queda en tu BD para auditoría pero no afecta el estado.

### Ejemplo: envío tardío rechazado

Mismo simulacro Mate `[08:00, 09:00]`. El alumno no entró nunca. A las 09:30, alguien (probablemente un hacker o un cliente mal configurado) intenta:

```http
POST /v3/simulacros/abc-123/envio
{
  "answers": { ... },
  "clientSubmittedAt": "2026-06-12T09:30:00-05:00"
}

→ 400 Bad Request    (porque 09:30 no está en [08:00, 09:00])
{ "message": "El clientSubmittedAt está fuera de la ventana del simulacro", "code": "INVALID_TIME" }
```

O equivalentemente, si el simulacro ya pasó a `cerrado`:

```http
→ 403 Forbidden
{ "message": "Este simulacro ya cerró", "code": "CLOSED" }
```

(Ambos códigos son aceptables — el cliente los maneja en ramas distintas pero el efecto UX es el mismo: redirige al alumno a `/home`.)

---

## Header de renovación rolling — `X-New-Bearer`

### Problema

El día académico puede llegar a 8–10 horas. Un bearer fijo de 6h forzaría re-login mid-día para simulacros de la tarde, que es mala UX.

### Solución

**Cualquier respuesta exitosa autenticada** (GET /simulacros, POST envío, GET /auth/me, etc.) puede incluir el header:

```http
X-New-Bearer: <nuevo-token>
```

Cuando el TTL del bearer actual cae bajo un umbral (sugerido: 2h restantes de los 6h nominales), emites un bearer nuevo en este header.

### Comportamiento del cliente

Ya implementado en el interceptor: cuando ve `X-New-Bearer`, persiste el nuevo bearer y los siguientes requests lo usan. **Es silencioso para el alumno** — no muestra ningún mensaje, no re-renderiza nada.

### Notas de implementación

- Si el TTL todavía es alto, **no incluyas el header** (no hay que rotar).
- Si el bearer está expirado, devuelve `401` normal — el cliente hace logout silencioso y manda al alumno a `/login` (no intentes renovar al borde, mejor fail clean).
- El bearer nuevo debe ser válido por otros 6h (TTL nominal completo).
- El bearer viejo puede quedar válido durante una ventana de gracia corta (ej. 30s) por requests in-flight, o ser revocado inmediato — tu llamada.

---

## Mapeo HTTP → errores del cliente (referencia)

Esto es cómo el cliente clasifica tus respuestas. Te lo paso para que tengas visibilidad de qué decisión UX dispara cada caso, pero del lado backend solo tienes que devolver el `(status, code)` correcto.

| Origen | Status code | Body code | Error del cliente | UX |
|---|---|---|---|---|
| GET /simulacros | 200 | — | (ok) | renderiza lista |
| GET /simulacros | 401 | — | `SessionExpiredError` | logout silencioso + `/login` |
| GET /simulacros | 5xx / 0 / network | — | `NetworkError` | banner "No se pudo conectar" + retry |
| POST envío | 200 | — | (ok) | navega a `/home` con simulacro enviado |
| POST envío | 409 | — | (ok — idempotencia) | igual que 200 |
| POST envío | 400 | `INVALID_TIME` | `InvalidSubmissionTimeError` | error operacional + `/home` |
| POST envío | 400 | `INVALID_SHAPE` | `InvalidPayloadError` | "Hubo un error inesperado" (bug del cliente) |
| POST envío | 403 | `CLOSED` | `SimulacroCerradoError` | "Este simulacro ya cerró" + `/home` |
| POST envío | 404 | — | `SimulacroNoAsignadoError` | refresh `/home` |
| POST envío | 401 | — | `SessionExpiredError` | logout silencioso + `/login` |
| POST envío | 5xx / 0 / network | — | `NetworkError` | cliente encola en IndexedDB local y reintenta cuando vuelva la red — **tú no haces nada especial** |

---

## Headers compartidos con Fase 1 (recordatorio)

| Header | Cuándo | Valor |
|---|---|---|
| `X-API-Key` | TODO request | tu env `API_KEY` |
| `Authorization` | Endpoints protegidos | `Bearer {{token}}` |
| `Content-Type` | Requests con body | `application/json` |
| `Accept` | Todos | `application/json` |

CORS sigue siendo `Access-Control-Allow-Origin: *` como en Fase 1.

---

## Testing en dev

Cuando tengas listos los endpoints, podemos verificar end-to-end con:

```bash
# Login (Fase 1, ya funciona)
curl -X POST http://localhost:2004/v3/auth/login \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"fulano@panda.test","password":"12345678"}'
# → { "token": "...", "user": {...} }

# GET simulacros del día
curl http://localhost:2004/v3/simulacros \
  -H "X-API-Key: $API_KEY" \
  -H "Authorization: Bearer $TOKEN"
# → { "serverTime": "...", "simulacros": [...] }

# POST envío
curl -X POST http://localhost:2004/v3/simulacros/abc-123/envio \
  -H "X-API-Key: $API_KEY" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"answers":{"1":"C","2":"A",...,"20":"B"},"clientSubmittedAt":"2026-06-12T08:47:00-05:00"}'
# → { "status": "enviado", "clientSubmittedAt": "...", "serverReceivedAt": "..." }
```

Para data de prueba, sugiero seedear al usuario `fulano@panda.test` con 2–3 simulacros con horarios:
- Uno que esté `abierto` ahora (para probar el flujo feliz).
- Uno que esté `pendiente` para más tarde (para probar el bloqueo de entrada).
- Uno que ya esté `cerrado` de ayer (para probar el estado terminal).

---

## Fuera de scope (para más adelante)

- Refresh token dedicado (Fase 2 usa rolling vía header).
- Historial de simulacros pasados (`?date=YYYY-MM-DD` en el GET).
- Resultados / calificaciones del envío.
- Cancelar / modificar envío después del POST.
- Anti-fraude técnico avanzado (rate limit por IP, detección de DevTools, etc.). Fase 2 asume aula supervisada.

---

## Preguntas que probablemente te surjan

**¿Cómo sé qué simulacros asignar al alumno?**
Esa decisión es tuya / del modelo de datos. Asumimos que ya hay un mecanismo "el profesor asigna simulacros al alumno con (id, area, name, count, inicio, fin)". El endpoint solo filtra los del alumno autenticado.

**¿Y si el alumno tiene varios simulacros que pisan horario?**
**No debe pasar.** Tu lógica de asignación debe validar no-overlap. El cliente no recompone si llegan dos `abierto` simultáneos, solo loguea warning. Pero la UX se confunde, así que es importante que backend lo prevenga.

**¿Qué pasa si el alumno se autentica desde dos dispositivos y manda envíos desde ambos?**
Idempotencia 409 lo cubre. El primer POST que llega se acepta. Los siguientes devuelven 409 con los datos del primero. Ambos dispositivos ven "enviado" con el mismo `clientSubmittedAt`.

**¿Necesitas que devuelva el `userEmail` o `userId` en algún sitio?**
No en estos endpoints. El cliente ya tiene la sesión local con el email del alumno (lo guardó en login).

**¿Cómo manejo el caso "alumno hace POST con un bearer que ya rotaste y entregaste uno nuevo"?**
Si tu Sanctum sigue aceptando el viejo durante la ventana de gracia (30s) → todo OK, lo procesas. Si no → 401, el cliente detecta y logout silencioso. Tu llamada.

---

¿Algo no queda claro o quieres que aclare un caso específico? Pásamelo y armo un patch al documento.
