<?php

namespace App\Services;

/**
 * Generador deterministico de fixtures Lumeria (syllabuses).
 *
 * 3 tipos de ciclo:
 *   cycle_id "orco"   -> 👹  8 sem, 10 cursos, slug "or"
 *   cycle_id "elfo"   -> 🧝 20 sem, 20 cursos, slug "ef"
 *   cycle_id "dragon" -> 🐲 32 sem, 20 cursos, slug "dr"
 *
 * IDs sinteticos:
 *   curso    : curs-{slug}-{area}{num}                 ej. curs-or-L1, curs-ef-H6
 *   tema     : T-{slug}-{area}{num}-{NN}               ej. T-ef-H2-15
 *   subtema  : ST{slug}{area}{num}{NN}-{sub_NN}        ej. STefH215-03
 *              (name lleva la frase LOTR; id lleva codigo numerico para
 *               poder distinguirlos en testing).
 *
 * Distribucion de semanas:
 *   - Tema t (1..T) cubre rango [floor((t-1)*S/T)+1 .. max(...,floor(t*S/T))].
 *   - Garantiza orden cronologico (tema 1 -> sem 1, tema N -> sem ultima).
 *   - Cubre todas las semanas 1..S sin gaps.
 *   - Maximo 3 temas por semana (cap topicCount en semanas*3).
 *
 * Todo es deterministico por hash del id: misma entrada -> mismo arbol.
 */
class LumeriaDataGenerator
{
    private const TIPOS = [
        'orco' => [
            'slug'    => 'or',
            'nombre'  => 'ORCO 👹',
            'semanas' => 8,
            'areas'   => ['L' => 2, 'C' => 3, 'N' => 3, 'H' => 2],
        ],
        'elfo' => [
            'slug'    => 'ef',
            'nombre'  => 'ELFO 🧝',
            'semanas' => 20,
            'areas'   => ['L' => 5, 'C' => 4, 'N' => 5, 'H' => 6],
        ],
        'dragon' => [
            'slug'    => 'dr',
            'nombre'  => 'DRAGON 🐲',
            'semanas' => 32,
            'areas'   => ['L' => 5, 'C' => 4, 'N' => 5, 'H' => 6],
        ],
    ];

    private const AREA_CODE = [
        'L' => '01', // Letras
        'C' => '02', // Ciencias
        'N' => '03', // Numeros
        'H' => '04', // Humanidades
    ];

    /** Nombres de cursos por area (tematicos LOTR). Hasta 6 por area (max H en ELFO/DRAGON). */
    private const CURSOS_BY_AREA = [
        'L' => ['LENGUAS ÉLFICAS', 'RUNAS ENANAS', 'POESÍA DE RIVENDEL', 'GRAMÁTICA QUENYA', 'DIALECTOS DE ROHAN', 'RETÓRICA NUMENÓREANA'],
        'C' => ['BOTÁNICA DE FANGORN', 'ALQUIMIA DE SARUMAN', 'BIOLOGÍA DE LAS MEARAS', 'ASTRONOMÍA DE EÄRENDIL', 'CIENCIAS DE LOS PALANTÍRI', 'GEOLOGÍA DE LAS NUBLADAS'],
        'N' => ['ARITMÉTICA DE LOS ENANOS', 'GEOMETRÍA DE MORIA', 'TRIGONOMETRÍA DE ORTHANC', 'ÁLGEBRA DE NÚMENOR', 'RAZONAMIENTO DE GANDALF', 'CÁLCULO DE LAS EDADES'],
        'H' => ['HISTORIA DE LA TIERRA MEDIA', 'GEOGRAFÍA DE GONDOR', 'CRÓNICAS DE NÚMENOR', 'FILOSOFÍA ÉLFICA', 'POLÍTICA DE ROHAN', 'ECONOMÍA DE ESGAROTH'],
    ];

    /** Banco de nombres de tema (LOTR). 40 entradas. */
    private const TOPIC_BANK = [
        'EL ANILLO ÚNICO',
        'LA COMARCA Y LOS HOBBITS',
        'MORDOR Y SUS SOMBRAS',
        'LA BATALLA DEL ABISMO DE HELM',
        'LOTHLÓRIEN, EL BOSQUE DORADO',
        'RIVENDEL Y LA CASA DE ELROND',
        'LOS NAZGÛL Y LOS NUEVE',
        'GONDOR Y MINAS TIRITH',
        'ROHAN Y LOS JINETES DE LA MARCA',
        'EL CAMINO DE LOS MUERTOS',
        'LA COMUNIDAD DEL ANILLO',
        'LA CAÍDA DE NÚMENOR',
        'LA TIERRA DE LOS ENTS',
        'LOS PUERTOS GRISES',
        'LA FORJA DE LOS ANILLOS DE PODER',
        'SAURON Y LA TIERRA NEGRA',
        'GANDALF Y LOS ISTARI',
        'ARAGORN, HEREDERO DE ISILDUR',
        'LA BATALLA DE LOS CAMPOS DEL PELENNOR',
        'MORIA Y EL BALROG',
        'LA TRAICIÓN DE SARUMAN',
        'LA TORRE DE ORTHANC',
        'LAS MONTAÑAS NUBLADAS',
        'EL BOSQUE NEGRO',
        'LA CIÉNAGA DE LOS MUERTOS',
        'EL MONTE DEL DESTINO',
        'LA PUERTA NEGRA',
        'CIRITH UNGOL Y SHELOB',
        'LA COMARCA DESPUÉS DE LA GUERRA',
        'EL CONSEJO DE ELROND',
        'LOS PALANTÍRI DE LA ANTIGÜEDAD',
        'LA HUESTE DEL OESTE',
        'GLORFINDEL Y LOS SEÑORES NOLDOR',
        'LA EDAD DE LOS ÁRBOLES',
        'EL SILMARILLION Y LAS JOYAS',
        'BEREN Y LÚTHIEN',
        'TÚRIN TURAMBAR',
        'LA CAÍDA DE GONDOLIN',
        'EÄRENDIL EL MARINERO',
        'LA GUERRA DE LA IRA',
    ];

    /** Banco de nombres de subtema (LOTR). 120 entradas, longitudes variadas. */
    private const SUBTOPIC_BANK = [
        'Frodo Bolsón', 'Samsagaz Gamyi', 'Meriadoc Brandigamo', 'Peregrin Tuk',
        'Gandalf el Gris', 'Gandalf el Blanco', 'Aragorn hijo de Arathorn', 'Boromir de Gondor',
        'Legolas Hojaverde', 'Gimli hijo de Glóin', 'Saruman el Blanco', 'Radagast el Pardo',
        'Galadriel señora de los Galadhrim', 'Celeborn de Lothlórien', 'Elrond medio elfo',
        'Glorfindel del oeste', 'Arwen Undómiel', 'Éowyn de Rohan', 'Théoden rey de la Marca',
        'Éomer mariscal', 'Faramir capitán de Ithilien', 'Denethor senescal',
        'Bilbo Bolsón', 'Tom Bombadil', 'Baya de Oro', 'el Rey Brujo de Angmar',
        'la Boca de Sauron', 'Shelob la grande', 'Gollum y Sméagol', 'Bárbol pastor de árboles',
        'Quickbeam el ent joven', 'Treebeard el viejo', 'el ojo de Sauron', 'el anillo de Sauron',
        'Narsil reforjada', 'Andúril llama del oeste', 'Glamdring martillo de enemigos',
        'Orcrist el destrozador', 'Aguijón daga de Bilbo', 'la capa élfica de Lórien',
        'el lembas pan del camino', 'el frasco de Galadriel', 'el cuerno de Boromir',
        'el palantír de Orthanc', 'el palantír de Minas Tirith', 'el caballo Sombragrís',
        'el águila Gwaihir', 'la corona de Elendil', 'el cetro de Annúminas',
        'la espada partida', 'la flecha negra de Bardo', 'el yelmo del rey brujo',
        'la armadura de mithril', 'el cinto dorado de Galadriel', 'la cota de malla enana',
        'la antorcha de Aragorn', 'la cuerda élfica', 'el caldero de los muertos',
        'Sauron señor oscuro', 'Morgoth enemigo primero', 'Ungoliant la araña ancestral',
        'Glaurung padre de dragones', 'Smaug el dorado', 'Ancalagon el negro',
        'Fëanor el más grande de los Noldor', 'Fingolfin alto rey', 'Finrod Felagund',
        'Maedhros el alto', 'Maglor el cantor', 'Lúthien Tinúviel', 'Beren Erchamion',
        'Túrin Turambar', 'Niënor Níniel', 'Húrin Thalion', 'Tuor de Dor-lómin',
        'Idril Celebrindal', 'Eärendil el marinero', 'Elwing la blanca', 'Elros tar-Minyatur',
        'Isildur de Gondor', 'Anárion de Anárion', 'Elendil el alto', 'Cirdan el carpintero',
        'la batalla de las Lágrimas Innumerables', 'la batalla de la Ira', 'la última alianza',
        'el sitio de Barad-dûr', 'la caída de Minas Ithil', 'la caída de Osgiliath',
        'la batalla del Pelennor', 'la batalla de los cinco ejércitos', 'la quema del Bosque Negro',
        'el viaje a Lonely Mountain', 'el rescate de Frodo en Cirith Ungol',
        'la destrucción del Anillo Único', 'la coronación de Aragorn',
        'el matrimonio de Aragorn y Arwen', 'el regreso de los hobbits a la Comarca',
        'la limpieza de la Comarca', 'la marcha de los Istari a las Tierras Imperecederas',
        'el Mar Separador', 'los Puertos Grises al atardecer',
        'el camino de los muertos bajo el Dwimorberg',
        'el bosque de Fangorn al amanecer', 'la Cuaderna del Sur',
        'el Camino Verde', 'el Puente del Brandivino', 'el Bosque Viejo',
        'los Túmulos de las Quebradas', 'el vado del Bruinen',
        'el paso de Caradhras', 'las puertas de Khazad-dûm',
        'el puente de Khazad-dûm', 'la escalera de Cirith Ungol',
        'el peldaño de Sammath Naur', 'las grietas del Destino',
        'la torre de Minas Morgul', 'la torre oscura de Barad-dûr',
        'el reino bajo la Montaña', 'el reino del Bosque',
        'el reino de Dale', 'el reino de Esgaroth', 'el reino de Beorn',
        'el reino de Khand', 'el reino de Harad', 'el reino de Rhûn',
        'el reino de Forochel', 'el reino perdido de Cardolan',
        'el reino perdido de Rhudaur', 'el reino perdido de Arthedain',
        'el reino unido de Arnor', 'el reino del sur de Gondor',
    ];

    /**
     * Devuelve { cycle_id, name, courses } o null si el cycle_id no es valido.
     */
    public function coursesForCycle(string $cycleId): ?array
    {
        if (! isset(self::TIPOS[$cycleId])) {
            return null;
        }

        $tipo    = self::TIPOS[$cycleId];
        $slug    = $tipo['slug'];
        $cursos  = [];
        $codeIdx = 1;

        foreach ($tipo['areas'] as $areaLetter => $count) {
            for ($n = 1; $n <= $count; $n++) {
                $cursos[] = [
                    'course_id'         => "curs-{$slug}-{$areaLetter}{$n}",
                    'code'              => str_pad((string) $codeIdx, 2, '0', STR_PAD_LEFT),
                    'name'              => self::CURSOS_BY_AREA[$areaLetter][$n - 1],
                    'subject_area_code' => self::AREA_CODE[$areaLetter],
                ];
                $codeIdx++;
            }
        }

        return [
            'cycle_id' => $cycleId,
            'name'     => $tipo['nombre'],
            'courses'  => $cursos,
        ];
    }

    /**
     * Devuelve { topics } o null si cycle_id o course_id no son validos.
     */
    public function syllabusForCourse(string $cycleId, string $courseId): ?array
    {
        if (! isset(self::TIPOS[$cycleId])) {
            return null;
        }

        $tipo = self::TIPOS[$cycleId];
        $slug = $tipo['slug'];

        $parsed = $this->parseCourseId($slug, $courseId, $tipo['areas']);
        if ($parsed === null) {
            return null;
        }

        [$areaLetter, $numEnArea] = $parsed;
        $semanas                  = $tipo['semanas'];

        // Topic count en [15, 25] pero capado por max 3 temas/semana.
        $rawCount   = 15 + ($this->hashInt($courseId) % 11);
        $topicCount = min($rawCount, $semanas * 3);

        $topicNameOffset = $this->hashInt($courseId . ':topics') % count(self::TOPIC_BANK);
        $topics          = [];

        for ($t = 1; $t <= $topicCount; $t++) {
            $topicNN = str_pad((string) $t, 2, '0', STR_PAD_LEFT);
            $seed    = $courseId . ':' . $t;

            // Rango de semanas que cubre este tema -- garantiza orden y cobertura total.
            $wStart = intdiv(($t - 1) * $semanas, $topicCount) + 1;
            $wEnd   = max($wStart, intdiv($t * $semanas, $topicCount));
            $rango  = range($wStart, $wEnd);

            $topicName = self::TOPIC_BANK[($topicNameOffset + $t - 1) % count(self::TOPIC_BANK)];
            $subCount  = 3 + ($this->hashInt($seed . ':subs') % 7); // [3, 9]
            $subStart  = $this->hashInt($seed . ':subnames') % count(self::SUBTOPIC_BANK);
            $subtopics = [];

            for ($s = 1; $s <= $subCount; $s++) {
                $subSeed = $seed . ':' . $s;
                $subName = self::SUBTOPIC_BANK[($subStart + $s - 1) % count(self::SUBTOPIC_BANK)];

                // Semana primaria: distribuye los subtemas dentro del rango del tema.
                $weeks = [$rango[($s - 1) % count($rango)]];

                // ~25% de subtemas se repiten en una semana adicional dentro del rango (cuando hay rango).
                if (count($rango) > 1 && ($this->hashInt($subSeed . ':wext') % 4) === 0) {
                    $extra = $rango[($s) % count($rango)];
                    if ($extra !== $weeks[0]) {
                        $weeks[] = $extra;
                        sort($weeks);
                    }
                }

                $subNN = str_pad((string) $s, 2, '0', STR_PAD_LEFT);
                $subtopics[] = [
                    'id'    => "ST{$slug}{$areaLetter}{$numEnArea}{$topicNN}-{$subNN}",
                    'code'  => $subNN,
                    'name'  => $subName,
                    'weeks' => $weeks,
                ];
            }

            $topics[] = [
                'topic_id'  => "T-{$slug}-{$areaLetter}{$numEnArea}-{$topicNN}",
                'code'      => null,
                'name'      => $topicName,
                'subtopics' => $subtopics,
            ];
        }

        return ['topics' => $topics];
    }

    /**
     * Parsea "curs-{slug}-{area}{n}" y valida que (area, n) este dentro del tipo.
     * Devuelve [area_letter, num_en_area] o null si no matchea.
     */
    private function parseCourseId(string $slug, string $courseId, array $areas): ?array
    {
        $prefix = "curs-{$slug}-";
        if (! str_starts_with($courseId, $prefix)) {
            return null;
        }

        $tail = substr($courseId, strlen($prefix));
        if (! preg_match('/^([LCNH])(\d+)$/', $tail, $m)) {
            return null;
        }

        $areaLetter = $m[1];
        $n          = (int) $m[2];
        $max        = $areas[$areaLetter] ?? 0;

        if ($n < 1 || $n > $max) {
            return null;
        }

        return [$areaLetter, $n];
    }

    private function hashInt(string $s): int
    {
        return crc32($s) & 0x7fffffff;
    }
}
