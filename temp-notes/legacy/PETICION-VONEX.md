
# DOCUMENTO DE ESPECIFICACIONES PARA API-VONEX-INTRANET 

## INTRO
por si para consulta a clin@odiseo.pe por google chat

👏👏👏 y porfis si es que se me pude blindar de los datos en fisico de las sedes y los ciclos seleccion si hay mas de 10 las que esten activas ahora en un excel o en un md para tener un contexto.

## ENTREGABLE
    * Sincronizacion de Aulas por ciclo con Sedes y Turno
    * Sincronizacion de Alumnos y Tutores por aulas de un ciclo

## CONTEXTO
1. este es una *PROPUESTA* del diseño basico de las rutas del api para ser consumidas en el futuro 

```json
midleware(token)
{
    * base url
    https://api.vonex.edu.pe/v3/                -> { status: OK, message: conectado a API-Vonex-Intranet . . . 😸 ! }

    * sedes
✅GET /sedes                          → Lista todas las sedes                            
    GET /sedes/{sede_id}                → Detalle de una sede
    GET /sedes/{sede_id}/alumnos        → Alumnos de una sede específica
    GET /sedes/{sede_id}/aulas          → Todas las aulas de una sede (todos los ciclos)
    GET /sedes/{sede_id}/ciclos         → Ciclos que se imparten en una sede específica
    GET /sedes/{sede_id}/tutores        → Tutores en una sede específica

    * ciclos
✅GET /ciclos                         → Lista todos los ciclos
    GET /ciclos/{ciclo_id}              → Detalle de un ciclo
    GET /ciclos/{ciclo_id}/alumnos      → Todos los alumnos de un ciclo
✅GET /ciclos/{ciclo_id}/aulas        → Todas las aulas de un ciclo
    GET /ciclos/{ciclo_id}/sedes        → Sedes donde se imparte un ciclo específico
    GET /ciclos/{ciclo_id}/tutores      → Todos los tutores de un ciclo

    * aulas
    GET /aulas                          → Lista todos las aulas
    GET /aulas/{aula_id}                → Detalle de un aula
✅GET /aulas/{aula_id}/alumnos        → Alumnos de una aula específica
✅GET /aulas/{aula_id}/tutores        → tutores de una aula específica

    * alumnos
    GET /alumnos                        → Lista todos los alumnos (con filtros)
    GET /alumnos/{alumno_id}            → Detalle de un alumno

    * tutores
    GET /tutores                        → Lista todos los tutores
    GET /tutores/{tutor_id}             → Detalle de un tutor
    GET /tutores/{tutor_id}/alumnos     → Alumnos de un tutor
    GET /tutores/{tutor_id}/aulas       → Aulas de un tutor
}
```

2. en este entregable por el momento activar la 5 rutas necesarias

```json
✅GET /sedes                          → Lista todas las sedes 
✅GET /ciclos                         → Lista todos los ciclos
✅GET /ciclos/{ciclo_id}/aulas        → Todas las aulas de un ciclo
✅GET /aulas/{aula_id}/alumnos        → Alumnos de una aula específica
✅GET /aulas/{aula_id}/tutores        → tutores de una aula específica
```


## Respuestas JSON

### Endpoint 1 — GET /sedes
Paginado por defensividad (aunque normalmente sean <20). Lista todas las sedes del tenant 
```json
  {
    "data": [
      {
        "sede_id": "SEDE-LIMA",                     // sede_id ->varchar(20)
        "nombre": "Sede Lima Centro",               // nombre  ->varchar(100)
        "ciudad": "Lima"                            // ciudad  ->varchar(100)
      },
      {
        "sede_id": "SEDE-AREQ",
        "nombre": "Sede Arequipa",
        "ciudad": "Arequipa"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 2,
      "total_pages": 1,
      "has_more": false
    }
  }
```

### Endpoint 2 — GET /ciclos/{ciclo_id}/aulas
Paginado. Devuelve aulas del ciclo identificado por su ciclo_id.
```json
  {
    "data": [
      {
        "aula_id": "A2025-12560",                  // aula_id   ->varchar(20)
        "codigo": "AULA-2025-1-A",                 // codigo    ->varchar(20)
        "nombre": "Aula A - Mañana",               // nombre"   ->varchar(80)
        "sede": {
          "sede_id": "SEDE-LIMA",                  // sede_id   ->varchar(20)
          "nombre": "Sede Lima Centro"             // nombre    ->varchar(100)
        },
        "turno": "manana",                         // turno     ->string (manana, tarde, noche)
        "modalidad": "presencial",                 // modalidad ->string (presencial, virtual, hibrido)
        "capacidad": 30,                           // capacidad ->int4 o null
        "activo": true                             // activo    ->bool
      }
    ],
    "pagination": { 
        "current_page": 1, 
        "per_page": 50, 
        "total": 12, 
        "total_pages": 1, 
        "has_more": false 
    }
  }
```

### Endpoint 3 — GET /aulas/{aula_id}/alumnos
Paginado (puede traer 90+).
```json
  {
    "data": [
      {
        "codigo": "20251001",                    // codigo      ->varchar(10)
        "apellidos": "Pérez García",             // apellidos   ->varchar(60)
        "nombres": "Juan Carlos",                // nombres     ->varchar(40)
        "correo": "juan@example.com",            // correo      ->varchar(255) | null
        "estado": "activo"                       // estado      ->varchar(20) | null
      }
    ],
    "pagination": { "current_page": 1, "per_page": 50, "total": 90, "total_pages": 2, "has_more": true }
  }
```

### Endpoint 4 — GET /aulas/{aula_id}/tutores
```json
  {
    "data": [
      {
        "tutor_id": "TUT-2025-1-A",               // tutor_id       ->varchar(20)
        "apellidos": "Ramírez Soto",              // apellidos      ->varchar(60)
        "nombres": "María Elena",                 // nombres        ->varchar(40)
        "correo": "maria.ramirez@example.com",    // correo         ->varchar(255) | null
        "documento": "12345678",                  // documento      ->varchar(10)
        "estado": "activo"                        // estado         ->varchar(20) | null
      }
    ],
    "pagination": { "current_page": 1, "per_page": 50, "total": 1, "total_pages": 1, "has_more": false }
  }

```

### Endpoint 5 — GET /ciclos
Paginado por defensividad (aunque normalmente sean <20). Lista todos los ciclos del tenant.
```json
  {
    "data": [
      {
        "ciclo_id": "CIC-2025-2",                   // ciclo_id      ->varchar(20)
        "nombre": "Semestral 2025 - seleccion",     // nombre        ->varchar(80)
        "fecha_inicio": "2026-05-01",               // fecha_inicio  ->date ISO 8601 (YYYY-MM-DD)
        "fecha_fin": "2026-07-31",                  // fecha_fin     ->date ISO 8601 (YYYY-MM-DD)
        "estado": "activo"                          // estado        ->string (activo, cerrado)
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 2,
      "total_pages": 1,
      "has_more": false
    }
  }
```

