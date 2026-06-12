# Solicitud de contrato API-FAKE para `add-auth-login` (Fase 1)

**Para:** equipo backend de API-FAKE
**De:** equipo frontend NeonPanda
**Cambio relacionado:** `openspec/changes/add-auth-login`
**Estado:** propuesta — pendiente de revisión y confirmación por backend

Este documento define los endpoints, headers y formatos que la PWA NeonPanda necesita que estén operativos en API-FAKE (Docker local) para completar la Fase 1 (login funcional). Si algún campo o código de respuesta no encaja con los patrones de Laravel/Sanctum que ya usan, márquenlo en la sección **Decisiones a confirmar** al final.

---

## 1. Convenciones generales

- **Base URL:** `<API_BASE_URL>` (la PWA la lee de `.env`; en dev típicamente `http://localhost:8000`).
- **Codificación:** JSON UTF-8 en todas las respuestas y cuerpos.
- **Headers que la PWA envía siempre:**
  - `X-API-Key: <api-key>` — en TODOS los endpoints, validado por middleware.
  - `Authorization: Bearer <token>` — solo en endpoints protegidos.
  - `Content-Type: application/json` — en requests con body.
  - `Accept: application/json`.

## 2. Middleware requerido

### 2.1 Middleware de API key (global)

Aplicado a TODAS las rutas, incluyendo `/auth/login`.

- Falta `X-API-Key` → `401 Unauthorized` con body:
  ```json
  { "message": "API key requerida" }
  ```
- `X-API-Key` no coincide con el configurado → `403 Forbidden` con body:
  ```json
  { "message": "API key inválida" }
  ```

### 2.2 Middleware de autenticación (Sanctum bearer)

Aplicado a rutas protegidas (`/auth/logout`, `/auth/me`, y futuras de Fase 2).

- Falta `Authorization` → `401 Unauthorized` con body:
  ```json
  { "message": "No autenticado" }
  ```
- Bearer inválido o expirado → `401 Unauthorized` con body:
  ```json
  { "message": "Token inválido" }
  ```

### 2.3 CORS (necesario para el dev server)

- **Allowed Origins:** `http://localhost:4200` (dev server Angular).
- **Allowed Methods:** `GET, POST, OPTIONS`.
- **Allowed Headers:** `X-API-Key, Authorization, Content-Type, Accept`.
- **Allow Credentials:** no requerido (la PWA no envía cookies; el bearer va por header).

## 3. Endpoints

### 3.1 `POST /auth/login` — público (solo `X-API-Key`)

Autentica al usuario y devuelve el bearer.

**Request:**
```http
POST /auth/login HTTP/1.1
Host: <API_BASE_URL>
X-API-Key: <api-key>
Content-Type: application/json
Accept: application/json
```

```json
{
  "email": "usuario@example.com",
  "password": "secret123"
}
```

**Response 200 — éxito:**
```json
{
  "token": "1|abc123...sanctum-personal-access-token",
  "user": {
    "email": "usuario@example.com",
    "name": "Nombre Apellido"
  }
}
```

**Response 401 — credenciales inválidas:**
```json
{ "message": "Credenciales inválidas" }
```

**Response 422 — error de validación:**
```json
{
  "message": "Los datos proporcionados no son válidos",
  "errors": {
    "email":    ["El campo email es obligatorio."],
    "password": ["El campo password es obligatorio."]
  }
}
```

**Response 429 — rate limit (si aplica):**
```json
{ "message": "Demasiados intentos, intenta nuevamente más tarde." }
```

**Response 5xx:** la PWA mostrará "No se pudo conectar al servidor."

---

### 3.2 `POST /auth/logout` — protegido (`X-API-Key` + `Authorization`)

Invalida el token server-side.

**Request:**
```http
POST /auth/logout HTTP/1.1
Host: <API_BASE_URL>
X-API-Key: <api-key>
Authorization: Bearer <token>
Accept: application/json
```

Sin body.

**Response 204:** sin body. El token queda inutilizable.

**Response 401:** token ya inválido. La PWA igual limpia el storage local (logout es idempotente del lado cliente).

---

### 3.3 `GET /auth/me` — protegido (`X-API-Key` + `Authorization`) — recomendado

Permite a la PWA validar la sesión al arrancar (cuando hay un bearer persistido en `localStorage`).

**Request:**
```http
GET /auth/me HTTP/1.1
Host: <API_BASE_URL>
X-API-Key: <api-key>
Authorization: Bearer <token>
Accept: application/json
```

**Response 200:**
```json
{
  "user": {
    "email": "usuario@example.com",
    "name": "Nombre Apellido"
  }
}
```

**Response 401:** token inválido. La PWA limpia la sesión local y redirige a `/login`.

> Si abrir `GET /auth/me` no es viable ahora, lo omitimos: la PWA asume que el bearer persistido es válido hasta que cualquier request privado responda 401, momento en que hace logout silencioso. El costo es una pantalla flash si el token estaba muerto. No es bloqueante para Fase 1.

---

## 4. Decisiones a confirmar por backend

| # | Pregunta                                                            | Default asumido si no hay respuesta                    |
|---|---------------------------------------------------------------------|--------------------------------------------------------|
| 1 | ¿Identidad por `email` o por `username`?                            | `email` con validación de formato                      |
| 2 | ¿OK el shape `{ token, user: { email, name } }` en login?           | Sí (patrón Sanctum SPA típico)                         |
| 3 | ¿Política de expiración del token Sanctum?                          | Sin expiración (token longevo hasta logout)            |
| 4 | ¿Campos extra en `user`? (id, role, avatar...)                      | Solo `email` y `name`                                  |
| 5 | ¿OK `POST /auth/logout` invalidando el token server-side?           | Sí; si no es viable, logout local-only                 |
| 6 | ¿OK `GET /auth/me` para validar sesión al arrancar?                 | Sí; si no es viable, omitir (ver nota en §3.3)         |
| 7 | ¿Existe rate limit en `/auth/login`? ¿Bajo qué política?            | Sin rate limit en dev; respuestas 429 manejadas igual  |
| 8 | ¿La api-key es la misma para todos los entornos o cambia por env?   | Una por entorno; PWA la lee de `.env`                  |

## 5. Cómo verificaremos el contrato (acceptance)

Cuando los endpoints estén abiertos, validaremos manualmente con `curl` o Postman antes de integrar:

```bash
# 1. Falta X-API-Key
curl -i -X POST $API_BASE_URL/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"x@y.com","password":"x"}'
# esperado: 401, body { "message": "API key requerida" }

# 2. Credenciales válidas
curl -i -X POST $API_BASE_URL/auth/login \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@neonpanda.test","password":"secret"}'
# esperado: 200, body con { token, user }

# 3. Credenciales inválidas
curl -i -X POST $API_BASE_URL/auth/login \
  -H "X-API-Key: $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@neonpanda.test","password":"wrong"}'
# esperado: 401, body { "message": "Credenciales inválidas" }

# 4. Logout con bearer válido
curl -i -X POST $API_BASE_URL/auth/logout \
  -H "X-API-Key: $API_KEY" \
  -H "Authorization: Bearer $TOKEN"
# esperado: 204

# 5. /auth/me con bearer válido
curl -i $API_BASE_URL/auth/me \
  -H "X-API-Key: $API_KEY" \
  -H "Authorization: Bearer $TOKEN"
# esperado: 200, body con { user }
```

## 6. Fuera de scope (para conversaciones posteriores)

- Refresh tokens (asumimos Sanctum personal access tokens de larga duración).
- Registro de usuarios (`POST /auth/register`).
- Recuperación de contraseña (`POST /auth/forgot-password`).
- Endpoints de Fase 2 (cartilla de marcaciones) — se solicitarán en su propio cambio.
- MFA / 2FA.

---

**Acción esperada del equipo backend:** revisar este documento, responder las preguntas de la sección 4, y confirmar cuándo los endpoints estarán operativos en la instancia de API-FAKE en Docker. Una vez confirmado, este documento se promueve a `agents/api-contract.md` como fuente de verdad del proyecto.
