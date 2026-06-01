<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Datos fijos del fake. Para añadir un registro, edita el array correspondiente.
 *
 * Estructura:
 *   SEDES                 -> lista plana de sedes
 *   AULAS_BY_CICLO        -> mapa ciclo_id => [aulas...]
 *   ALUMNOS_BY_AULA       -> mapa aula_id  => [alumnos...]
 *   TUTORES_BY_AULA       -> mapa aula_id  => [tutores...]
 *
 * IDs validos hoy:
 *   ciclos: CIC-2025-1, CIC-2025-2
 *   aulas:  A2025-00001 .. A2025-00006
 */
class FakeDataGenerator
{
    // ----------------------------------------------------------------
    // SEDES (10)
    // ----------------------------------------------------------------
    private const SEDES = [
        ['sede_id' => 'SEDE-LIMA', 'nombre' => 'Sede Lima Centro',  'ciudad' => 'Lima'],
        ['sede_id' => 'SEDE-AREQ', 'nombre' => 'Sede Arequipa',     'ciudad' => 'Arequipa'],
        ['sede_id' => 'SEDE-TRUJ', 'nombre' => 'Sede Trujillo',     'ciudad' => 'Trujillo'],
        ['sede_id' => 'SEDE-CUSC', 'nombre' => 'Sede Cusco',        'ciudad' => 'Cusco'],
        ['sede_id' => 'SEDE-PIUR', 'nombre' => 'Sede Piura',        'ciudad' => 'Piura'],
        ['sede_id' => 'SEDE-CHIC', 'nombre' => 'Sede Chiclayo',     'ciudad' => 'Chiclayo'],
        ['sede_id' => 'SEDE-ICA',  'nombre' => 'Sede Ica',          'ciudad' => 'Ica'],
        ['sede_id' => 'SEDE-TACN', 'nombre' => 'Sede Tacna',        'ciudad' => 'Tacna'],
        ['sede_id' => 'SEDE-HUAN', 'nombre' => 'Sede Huancayo',     'ciudad' => 'Huancayo'],
        ['sede_id' => 'SEDE-PUNO', 'nombre' => 'Sede Puno',         'ciudad' => 'Puno'],
    ];

    // ----------------------------------------------------------------
    // AULAS por ciclo (2 ciclos x 3 aulas)
    // ----------------------------------------------------------------
    private const AULAS_BY_CICLO = [
        'CIC-2025-1' => [
            [
                'aula_id'   => 'A2025-00001',
                'codigo'    => 'AULA-2025-1-A',
                'nombre'    => 'Aula A - Manana',
                'sede'      => ['sede_id' => 'SEDE-LIMA', 'nombre' => 'Sede Lima Centro'],
                'turno'     => 'manana',
                'modalidad' => 'presencial',
                'capacidad' => 30,
                'activo'    => true,
            ],
            [
                'aula_id'   => 'A2025-00002',
                'codigo'    => 'AULA-2025-1-B',
                'nombre'    => 'Aula B - Tarde',
                'sede'      => ['sede_id' => 'SEDE-AREQ', 'nombre' => 'Sede Arequipa'],
                'turno'     => 'tarde',
                'modalidad' => 'presencial',
                'capacidad' => 28,
                'activo'    => true,
            ],
            [
                'aula_id'   => 'A2025-00003',
                'codigo'    => 'AULA-2025-1-C',
                'nombre'    => 'Aula C - Noche',
                'sede'      => ['sede_id' => 'SEDE-TRUJ', 'nombre' => 'Sede Trujillo'],
                'turno'     => 'noche',
                'modalidad' => 'virtual',
                'capacidad' => 35,
                'activo'    => true,
            ],
        ],
        'CIC-2025-2' => [
            [
                'aula_id'   => 'A2025-00004',
                'codigo'    => 'AULA-2025-2-A',
                'nombre'    => 'Aula A - Manana',
                'sede'      => ['sede_id' => 'SEDE-CUSC', 'nombre' => 'Sede Cusco'],
                'turno'     => 'manana',
                'modalidad' => 'hibrido',
                'capacidad' => 32,
                'activo'    => true,
            ],
            [
                'aula_id'   => 'A2025-00005',
                'codigo'    => 'AULA-2025-2-B',
                'nombre'    => 'Aula B - Tarde',
                'sede'      => ['sede_id' => 'SEDE-PIUR', 'nombre' => 'Sede Piura'],
                'turno'     => 'tarde',
                'modalidad' => 'presencial',
                'capacidad' => 30,
                'activo'    => true,
            ],
            [
                'aula_id'   => 'A2025-00006',
                'codigo'    => 'AULA-2025-2-C',
                'nombre'    => 'Aula C - Noche',
                'sede'      => ['sede_id' => 'SEDE-CHIC', 'nombre' => 'Sede Chiclayo'],
                'turno'     => 'noche',
                'modalidad' => 'virtual',
                'capacidad' => 40,
                'activo'    => false,
            ],
        ],
    ];

    // ----------------------------------------------------------------
    // ALUMNOS por aula (10 por aula)
    // ----------------------------------------------------------------
    private const ALUMNOS_BY_AULA = [
        'A2025-00001' => [
            ['codigo' => '20251001', 'apellidos' => 'Perez Garcia',     'nombres' => 'Juan Carlos',   'correo' => 'juan.perez@example.com',    'estado' => 'activo'],
            ['codigo' => '20251002', 'apellidos' => 'Ramirez Soto',     'nombres' => 'Maria Elena',   'correo' => 'maria.ramirez@example.com', 'estado' => 'activo'],
            ['codigo' => '20251003', 'apellidos' => 'Gonzalez Vega',    'nombres' => 'Pedro Luis',    'correo' => 'pedro.gonzalez@example.com','estado' => 'activo'],
            ['codigo' => '20251004', 'apellidos' => 'Quispe Mamani',    'nombres' => 'Ana Sofia',     'correo' => 'ana.quispe@example.com',    'estado' => 'activo'],
            ['codigo' => '20251005', 'apellidos' => 'Flores Huaman',    'nombres' => 'Carlos Andres', 'correo' => 'carlos.flores@example.com', 'estado' => 'activo'],
            ['codigo' => '20251006', 'apellidos' => 'Rojas Castro',     'nombres' => 'Lucia Isabel',  'correo' => 'lucia.rojas@example.com',   'estado' => 'activo'],
            ['codigo' => '20251007', 'apellidos' => 'Vargas Diaz',      'nombres' => 'Diego Alonso',  'correo' => 'diego.vargas@example.com',  'estado' => 'activo'],
            ['codigo' => '20251008', 'apellidos' => 'Mendoza Ruiz',     'nombres' => 'Valentina',     'correo' => 'valentina.mendoza@example.com', 'estado' => 'activo'],
            ['codigo' => '20251009', 'apellidos' => 'Sanchez Torres',   'nombres' => 'Mateo',         'correo' => 'mateo.sanchez@example.com', 'estado' => 'activo'],
            ['codigo' => '20251010', 'apellidos' => 'Lopez Paredes',    'nombres' => 'Camila',        'correo' => 'camila.lopez@example.com',  'estado' => 'inactivo'],
        ],
        'A2025-00002' => [
            ['codigo' => '20251011', 'apellidos' => 'Castillo Apaza',   'nombres' => 'Sebastian',     'correo' => 'sebastian.castillo@example.com','estado' => 'activo'],
            ['codigo' => '20251012', 'apellidos' => 'Cardenas Reyes',   'nombres' => 'Daniela',       'correo' => 'daniela.cardenas@example.com',  'estado' => 'activo'],
            ['codigo' => '20251013', 'apellidos' => 'Salazar Nunez',    'nombres' => 'Gabriel',       'correo' => 'gabriel.salazar@example.com',   'estado' => 'activo'],
            ['codigo' => '20251014', 'apellidos' => 'Espinoza Rios',    'nombres' => 'Fernanda',      'correo' => 'fernanda.espinoza@example.com', 'estado' => 'activo'],
            ['codigo' => '20251015', 'apellidos' => 'Morales Cruz',     'nombres' => 'Joaquin',       'correo' => 'joaquin.morales@example.com',   'estado' => 'activo'],
            ['codigo' => '20251016', 'apellidos' => 'Aguirre Vasquez',  'nombres' => 'Renata',        'correo' => 'renata.aguirre@example.com',    'estado' => 'activo'],
            ['codigo' => '20251017', 'apellidos' => 'Bautista Cordero', 'nombres' => 'Bruno',         'correo' => 'bruno.bautista@example.com',    'estado' => 'activo'],
            ['codigo' => '20251018', 'apellidos' => 'Chavez Olivares',  'nombres' => 'Antonella',     'correo' => 'antonella.chavez@example.com',  'estado' => 'activo'],
            ['codigo' => '20251019', 'apellidos' => 'Delgado Maldonado','nombres' => 'Emilio',        'correo' => 'emilio.delgado@example.com',    'estado' => 'activo'],
            ['codigo' => '20251020', 'apellidos' => 'Estrada Palomino', 'nombres' => 'Mariana',       'correo' => 'mariana.estrada@example.com',   'estado' => 'activo'],
        ],
        'A2025-00003' => [
            ['codigo' => '20251021', 'apellidos' => 'Rivera Campos',    'nombres' => 'Rodrigo',       'correo' => 'rodrigo.rivera@example.com',    'estado' => 'activo'],
            ['codigo' => '20251022', 'apellidos' => 'Herrera Luna',     'nombres' => 'Paula',         'correo' => 'paula.herrera@example.com',     'estado' => 'activo'],
            ['codigo' => '20251023', 'apellidos' => 'Carrasco Pineda',  'nombres' => 'Tomas',         'correo' => 'tomas.carrasco@example.com',    'estado' => 'activo'],
            ['codigo' => '20251024', 'apellidos' => 'Ortega Bravo',     'nombres' => 'Sofia Belen',   'correo' => 'sofia.ortega@example.com',      'estado' => 'activo'],
            ['codigo' => '20251025', 'apellidos' => 'Nunez Saavedra',   'nombres' => 'Nicolas',       'correo' => 'nicolas.nunez@example.com',     'estado' => 'activo'],
            ['codigo' => '20251026', 'apellidos' => 'Garrido Ponce',    'nombres' => 'Adriana',       'correo' => 'adriana.garrido@example.com',   'estado' => 'activo'],
            ['codigo' => '20251027', 'apellidos' => 'Pacheco Roldan',   'nombres' => 'Ignacio',       'correo' => 'ignacio.pacheco@example.com',   'estado' => 'activo'],
            ['codigo' => '20251028', 'apellidos' => 'Romero Lazo',      'nombres' => 'Isabella',      'correo' => 'isabella.romero@example.com',   'estado' => 'activo'],
            ['codigo' => '20251029', 'apellidos' => 'Cabrera Yupa',     'nombres' => 'Lucas',         'correo' => 'lucas.cabrera@example.com',     'estado' => 'activo'],
            ['codigo' => '20251030', 'apellidos' => 'Tello Mejia',      'nombres' => 'Martina',       'correo' => 'martina.tello@example.com',     'estado' => 'inactivo'],
        ],
        'A2025-00004' => [
            ['codigo' => '20252001', 'apellidos' => 'Acuna Vilca',      'nombres' => 'Alejandro',     'correo' => 'alejandro.acuna@example.com',   'estado' => 'activo'],
            ['codigo' => '20252002', 'apellidos' => 'Benavides Rocha',  'nombres' => 'Romina',        'correo' => 'romina.benavides@example.com',  'estado' => 'activo'],
            ['codigo' => '20252003', 'apellidos' => 'Cespedes Vera',    'nombres' => 'Andres',        'correo' => 'andres.cespedes@example.com',   'estado' => 'activo'],
            ['codigo' => '20252004', 'apellidos' => 'Dominguez Naval',  'nombres' => 'Ximena',        'correo' => 'ximena.dominguez@example.com',  'estado' => 'activo'],
            ['codigo' => '20252005', 'apellidos' => 'Echevarria Pena',  'nombres' => 'Santiago',      'correo' => 'santiago.echevarria@example.com','estado' => 'activo'],
            ['codigo' => '20252006', 'apellidos' => 'Fonseca Trigo',    'nombres' => 'Catalina',      'correo' => 'catalina.fonseca@example.com',  'estado' => 'activo'],
            ['codigo' => '20252007', 'apellidos' => 'Galarza Maza',     'nombres' => 'Maximiliano',   'correo' => 'maximiliano.galarza@example.com','estado' => 'activo'],
            ['codigo' => '20252008', 'apellidos' => 'Huaraca Pomar',    'nombres' => 'Constanza',     'correo' => 'constanza.huaraca@example.com', 'estado' => 'activo'],
            ['codigo' => '20252009', 'apellidos' => 'Ibanez Cueva',     'nombres' => 'Benjamin',      'correo' => 'benjamin.ibanez@example.com',   'estado' => 'activo'],
            ['codigo' => '20252010', 'apellidos' => 'Jimenez Otero',    'nombres' => 'Florencia',     'correo' => 'florencia.jimenez@example.com', 'estado' => 'activo'],
        ],
        'A2025-00005' => [
            ['codigo' => '20252011', 'apellidos' => 'Kohatsu Yauri',    'nombres' => 'Thiago',        'correo' => 'thiago.kohatsu@example.com',    'estado' => 'activo'],
            ['codigo' => '20252012', 'apellidos' => 'Linares Quito',    'nombres' => 'Maite',         'correo' => 'maite.linares@example.com',     'estado' => 'activo'],
            ['codigo' => '20252013', 'apellidos' => 'Mejia Roca',       'nombres' => 'Joaquin',       'correo' => 'joaquin.mejia@example.com',     'estado' => 'activo'],
            ['codigo' => '20252014', 'apellidos' => 'Navarro Suaza',    'nombres' => 'Olivia',        'correo' => 'olivia.navarro@example.com',    'estado' => 'activo'],
            ['codigo' => '20252015', 'apellidos' => 'Ochoa Tinoco',     'nombres' => 'Cristobal',     'correo' => 'cristobal.ochoa@example.com',   'estado' => 'activo'],
            ['codigo' => '20252016', 'apellidos' => 'Pizarro Uribe',    'nombres' => 'Amanda',        'correo' => 'amanda.pizarro@example.com',    'estado' => 'activo'],
            ['codigo' => '20252017', 'apellidos' => 'Quiroz Vasquez',   'nombres' => 'Vicente',       'correo' => 'vicente.quiroz@example.com',    'estado' => 'activo'],
            ['codigo' => '20252018', 'apellidos' => 'Rosado Wong',      'nombres' => 'Julieta',       'correo' => 'julieta.rosado@example.com',    'estado' => 'activo'],
            ['codigo' => '20252019', 'apellidos' => 'Sosa Xavier',      'nombres' => 'Facundo',       'correo' => 'facundo.sosa@example.com',      'estado' => 'activo'],
            ['codigo' => '20252020', 'apellidos' => 'Tavera Ybarra',    'nombres' => 'Lara',          'correo' => 'lara.tavera@example.com',       'estado' => 'inactivo'],
        ],
        'A2025-00006' => [
            ['codigo' => '20252021', 'apellidos' => 'Ulloa Zegarra',    'nombres' => 'Ezequiel',      'correo' => 'ezequiel.ulloa@example.com',    'estado' => 'activo'],
            ['codigo' => '20252022', 'apellidos' => 'Valverde Ango',    'nombres' => 'Pilar',         'correo' => 'pilar.valverde@example.com',    'estado' => 'activo'],
            ['codigo' => '20252023', 'apellidos' => 'Yepez Berna',      'nombres' => 'Octavio',       'correo' => 'octavio.yepez@example.com',     'estado' => 'activo'],
            ['codigo' => '20252024', 'apellidos' => 'Zarate Cano',      'nombres' => 'Helena',        'correo' => 'helena.zarate@example.com',     'estado' => 'activo'],
            ['codigo' => '20252025', 'apellidos' => 'Alfaro Davila',    'nombres' => 'Matias',        'correo' => 'matias.alfaro@example.com',     'estado' => 'activo'],
            ['codigo' => '20252026', 'apellidos' => 'Bermudez Eche',    'nombres' => 'Agustina',      'correo' => 'agustina.bermudez@example.com', 'estado' => 'activo'],
            ['codigo' => '20252027', 'apellidos' => 'Cano Fuentes',     'nombres' => 'Leonardo',      'correo' => 'leonardo.cano@example.com',     'estado' => 'activo'],
            ['codigo' => '20252028', 'apellidos' => 'Diaz Galdos',      'nombres' => 'Rafaela',       'correo' => 'rafaela.diaz@example.com',      'estado' => 'activo'],
            ['codigo' => '20252029', 'apellidos' => 'Elias Huertas',    'nombres' => 'Dante',         'correo' => 'dante.elias@example.com',       'estado' => 'activo'],
            ['codigo' => '20252030', 'apellidos' => 'Falcon Iglesias',  'nombres' => 'Aitana',        'correo' => 'aitana.falcon@example.com',     'estado' => 'activo'],
        ],
    ];

    // ----------------------------------------------------------------
    // TUTORES por aula (3 por aula)
    // ----------------------------------------------------------------
    private const TUTORES_BY_AULA = [
        'A2025-00001' => [
            ['tutor_id' => 'TUT-2025-1-A-1', 'apellidos' => 'Ramirez Soto',  'nombres' => 'Maria Elena',     'correo' => 'maria.ramirez@example.com',   'documento' => '12345678', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-A-2', 'apellidos' => 'Quispe Mamani', 'nombres' => 'Carlos Alberto',  'correo' => 'carlos.quispe@example.com',   'documento' => '23456789', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-A-3', 'apellidos' => 'Flores Huaman', 'nombres' => 'Patricia Lucia',  'correo' => 'patricia.flores@example.com', 'documento' => '34567890', 'estado' => 'activo'],
        ],
        'A2025-00002' => [
            ['tutor_id' => 'TUT-2025-1-B-1', 'apellidos' => 'Rojas Castro',  'nombres' => 'Jorge Luis',      'correo' => 'jorge.rojas@example.com',     'documento' => '45678901', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-B-2', 'apellidos' => 'Vargas Diaz',   'nombres' => 'Rosa Aurora',     'correo' => 'rosa.vargas@example.com',     'documento' => '56789012', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-B-3', 'apellidos' => 'Mendoza Ruiz',  'nombres' => 'Luis Fernando',   'correo' => 'luis.mendoza@example.com',    'documento' => '67890123', 'estado' => 'activo'],
        ],
        'A2025-00003' => [
            ['tutor_id' => 'TUT-2025-1-C-1', 'apellidos' => 'Sanchez Torres','nombres' => 'Carmen Rosa',     'correo' => 'carmen.sanchez@example.com',  'documento' => '78901234', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-C-2', 'apellidos' => 'Lopez Paredes', 'nombres' => 'Roberto Carlos',  'correo' => 'roberto.lopez@example.com',   'documento' => '89012345', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-1-C-3', 'apellidos' => 'Castillo Apaza','nombres' => 'Beatriz Adriana', 'correo' => 'beatriz.castillo@example.com','documento' => '90123456', 'estado' => 'activo'],
        ],
        'A2025-00004' => [
            ['tutor_id' => 'TUT-2025-2-A-1', 'apellidos' => 'Cardenas Reyes','nombres' => 'Ana Lucia',       'correo' => 'ana.cardenas@example.com',    'documento' => '11223344', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-A-2', 'apellidos' => 'Salazar Nunez', 'nombres' => 'Eduardo Manuel',  'correo' => 'eduardo.salazar@example.com', 'documento' => '22334455', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-A-3', 'apellidos' => 'Espinoza Rios', 'nombres' => 'Gloria Isabel',   'correo' => 'gloria.espinoza@example.com', 'documento' => '33445566', 'estado' => 'activo'],
        ],
        'A2025-00005' => [
            ['tutor_id' => 'TUT-2025-2-B-1', 'apellidos' => 'Morales Cruz',  'nombres' => 'Hugo Alberto',    'correo' => 'hugo.morales@example.com',    'documento' => '44556677', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-B-2', 'apellidos' => 'Aguirre Vasquez','nombres' => 'Cecilia Marina', 'correo' => 'cecilia.aguirre@example.com', 'documento' => '55667788', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-B-3', 'apellidos' => 'Bautista Cordero','nombres' => 'Daniel Arturo','correo' => 'daniel.bautista@example.com', 'documento' => '66778899', 'estado' => 'activo'],
        ],
        'A2025-00006' => [
            ['tutor_id' => 'TUT-2025-2-C-1', 'apellidos' => 'Chavez Olivares','nombres' => 'Susana Karina', 'correo' => 'susana.chavez@example.com',   'documento' => '77889900', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-C-2', 'apellidos' => 'Delgado Maldonado','nombres' => 'Javier Antonio','correo' => 'javier.delgado@example.com','documento' => '88990011', 'estado' => 'activo'],
            ['tutor_id' => 'TUT-2025-2-C-3', 'apellidos' => 'Estrada Palomino','nombres' => 'Pilar Veronica','correo' => 'pilar.estrada@example.com',  'documento' => '99001122', 'estado' => 'activo'],
        ],
    ];

    public function sedes(): array
    {
        return self::SEDES;
    }

    /** Devuelve null si el ciclo no existe. */
    public function aulasForCiclo(string $cicloId): ?array
    {
        return self::AULAS_BY_CICLO[$cicloId] ?? null;
    }

    /** Devuelve null si el aula no existe. */
    public function alumnosForAula(string $aulaId): ?array
    {
        return self::ALUMNOS_BY_AULA[$aulaId] ?? null;
    }

    /** Devuelve null si el aula no existe. */
    public function tutoresForAula(string $aulaId): ?array
    {
        return self::TUTORES_BY_AULA[$aulaId] ?? null;
    }

    public function paginate(array $items, Request $request): array
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $total      = count($items);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $page = (int) $request->query('page', 1);
        $page = max(1, min($page, $totalPages));

        $offset = ($page - 1) * $perPage;
        $data   = array_slice($items, $offset, $perPage);

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'total_pages'  => $totalPages,
                'has_more'     => $page < $totalPages,
            ],
        ];
    }
}
