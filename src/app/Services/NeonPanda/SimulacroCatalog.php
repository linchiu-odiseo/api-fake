<?php

namespace App\Services\NeonPanda;

use Carbon\CarbonImmutable;

/**
 * Catalogo fake de simulacros del dia para el PWA NeonPanda.
 *
 * Contrato: temp-notes/api-fake-fase2-request.md
 *
 * Diseno:
 *   - 3 simulacros fijos anclados a "hoy en America/Lima".
 *   - Ventanas calculadas como offsets desde un ancla diaria, de modo que
 *     mientras "hoy" sea "hoy" siempre haya uno abierto, uno pendiente y
 *     uno cerrado. Manana se rotan solos.
 *   - IDs estables por dia: sim-{slug}-{YYYY-MM-DD}. Reproducibles entre
 *     requests del mismo dia, distintos cada dia.
 *   - Sin BD. Sin envios (Fase A — endpoint 1 solamente).
 */
class SimulacroCatalog
{
    private const TZ = 'America/Lima';

    /**
     * Definicion estatica. Las horas son offsets en minutos respecto a un
     * "ancla diaria" que se calcula como now() para que siempre haya un
     * abierto justo ahora.
     *
     * Layout pensado para que en cualquier momento del dia:
     *   - "mate" este abierto (ventana centrada en now)
     *   - "comu" este pendiente (empieza +2h)
     *   - "fisi" este cerrado (termino hace 2h)
     */
    private const SLOTS = [
        [
            'slug'        => 'mate',
            'area'        => 'numeros',
            'name'        => 'Simulacro 03 — Razonamiento',
            'count'       => 5,
            'inicio_min'  => -30,  // abre hace 30 min
            'fin_min'     =>  1,  // cierra en 30 min
        ],
        [
            'slug'        => 'comu',
            'area'        => 'letras',
            'name'        => 'Simulacro 03 — Lectura crítica',
            'count'       => 8,
            'inicio_min'  => 120,  // abre en 2h
            'fin_min'     => 180,  // cierra en 3h
        ],
        [
            'slug'        => 'fisi',
            'area'        => 'ciencias',
            'name'        => 'Simulacro 03 — Mecánica',
            'count'       => 10,
            'inicio_min'  => -180, // abrio hace 3h
            'fin_min'     => -120, // cerro hace 2h
        ],
    ];

    /**
     * Devuelve los simulacros del alumno autenticado para "hoy".
     *
     * Como Fase A solo tiene a fulano@panda.test, devolvemos los mismos 3
     * a cualquier usuario. Cuando haya asignacion real, filtrar por $user.
     */
    public function forUser(string $userEmail, CarbonImmutable $now): array
    {
        $now   = $now->setTimezone(self::TZ);
        $today = $now->format('Y-m-d');

        $out = [];
        foreach (self::SLOTS as $slot) {
            $out[] = $this->buildSimulacro($slot, $now, $today);
        }

        return $out;
    }

    /**
     * Resuelve un id "sim-{slug}-YYYY-MM-DD" a la misma estructura que
     * forUser() emite. Devuelve null si:
     *   - el formato del id no calza,
     *   - el slug no existe en el catalogo,
     *   - la fecha del id no es "hoy" en Lima TZ (no servimos historico aun).
     *
     * Util para el POST /envio: el controller necesita inicio/fin para
     * validar clientSubmittedAt y para evaluar si esta cerrado.
     */
    public function findById(string $simulacroId, CarbonImmutable $now): ?array
    {
        $now   = $now->setTimezone(self::TZ);
        $today = $now->format('Y-m-d');

        if (! preg_match('/^sim-([a-z]+)-(\d{4}-\d{2}-\d{2})$/', $simulacroId, $m)) {
            return null;
        }
        [$_, $slug, $date] = $m;

        if ($date !== $today) {
            return null;
        }

        foreach (self::SLOTS as $slot) {
            if ($slot['slug'] === $slug) {
                return $this->buildSimulacro($slot, $now, $today);
            }
        }

        return null;
    }

    private function buildSimulacro(array $slot, CarbonImmutable $now, string $today): array
    {
        $inicio = $now->addMinutes($slot['inicio_min']);
        $fin    = $now->addMinutes($slot['fin_min']);

        return [
            'id'     => "sim-{$slot['slug']}-{$today}",
            'area'   => $slot['area'],
            'name'   => $slot['name'],
            'count'  => $slot['count'],
            'inicio' => $inicio->toIso8601String(),
            'fin'    => $fin->toIso8601String(),
            'estado' => $this->derivarEstado($now, $inicio, $fin),
        ];
    }

    /**
     * Deriva el estado segun el contrato. Fase A no trackea envios, asi que
     * solo emitimos pendiente | abierto | cerrado. El estado "enviado" llega
     * en Fase B cuando exista el storage de envios.
     */
    private function derivarEstado(CarbonImmutable $now, CarbonImmutable $inicio, CarbonImmutable $fin): string
    {
        if ($now->lt($inicio)) {
            return 'pendiente';
        }
        if ($now->between($inicio, $fin)) {
            return 'abierto';
        }
        return 'cerrado';
    }
}
