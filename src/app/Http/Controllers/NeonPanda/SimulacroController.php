<?php

namespace App\Http\Controllers\NeonPanda;

use App\Http\Controllers\Controller;
use App\Models\SimulacroEnvio;
use App\Services\NeonPanda\SimulacroCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de simulacros para el PWA NeonPanda (Fase 2).
 *
 * Contrato: temp-notes/api-fake-fase2-request.md
 * Auth: X-API-Key + Bearer Sanctum (mismo middleware que Fase 1).
 */
class SimulacroController extends Controller
{
    private const TZ = 'America/Lima';

    public function __construct(private readonly SimulacroCatalog $catalog)
    {
    }

    /**
     * GET /v3/simulacros
     *
     * Devuelve los simulacros del dia para el alumno autenticado mas la hora
     * oficial del servidor (anclaje de countdowns en el cliente).
     */
    public function index(Request $request): JsonResponse
    {
        $now  = CarbonImmutable::now(self::TZ);
        $user = $request->user();

        return response()->json([
            'serverTime' => $now->toIso8601String(),
            'simulacros' => $this->catalog->forUser($user->email, $now),
        ]);
    }

    /**
     * POST /v3/simulacros/{simulacroId}/envio
     *
     * Orden de validacion (importante — la BD lo agradece):
     *   1. 404 si el simulacro no existe / no es de hoy
     *   2. 409 idempotencia si ya hay envio del mismo (user, simulacro)
     *   3. 400 INVALID_SHAPE si el body no tiene answers/clientSubmittedAt
     *   4. 403 CLOSED si now > fin (y aqui no hay envio previo, ya descartado)
     *   5. 400 INVALID_TIME si clientSubmittedAt cae fuera de [inicio, fin]
     *   6. 400 INVALID_SHAPE si answers no calza (length / keys / values)
     *   7. 200 + insert
     *
     * La unique constraint (user_id, simulacro_id) ataja races: si dos
     * requests pasan los pasos 2-6 al mismo tiempo, el segundo INSERT
     * revienta con 23505 y lo atrapamos como 409.
     */
    public function envio(Request $request, string $simulacroId): JsonResponse
    {
        $now  = CarbonImmutable::now(self::TZ);
        $user = $request->user();

        // 1. ¿Existe el simulacro hoy?
        $simulacro = $this->catalog->findById($simulacroId, $now);
        if ($simulacro === null) {
            return response()->json(['message' => 'Simulacro no asignado'], 404);
        }

        // 2. Idempotencia.
        $existing = SimulacroEnvio::where('user_id', $user->id)
            ->where('simulacro_id', $simulacroId)
            ->first();
        if ($existing !== null) {
            return $this->respondEnvio($existing, 409);
        }

        // 3. Body shape basico.
        $answers           = $request->input('answers');
        $clientSubmittedAt = $request->input('clientSubmittedAt');
        if (! is_array($answers) || ! is_string($clientSubmittedAt)) {
            return $this->invalidShape('Body invalido: requiere answers (object) y clientSubmittedAt (string)');
        }

        // 4. CLOSED.
        $inicio = CarbonImmutable::parse($simulacro['inicio']);
        $fin    = CarbonImmutable::parse($simulacro['fin']);
        if ($now->gt($fin)) {
            return response()->json([
                'message' => 'Este simulacro ya cerro',
                'code'    => 'CLOSED',
            ], 403);
        }

        // 5. clientSubmittedAt parseable y en [inicio, fin].
        try {
            $submittedAt = CarbonImmutable::parse($clientSubmittedAt);
        } catch (\Throwable) {
            return $this->invalidShape('clientSubmittedAt debe ser ISO8601');
        }
        if ($submittedAt->lt($inicio) || $submittedAt->gt($fin)) {
            return response()->json([
                'message' => 'clientSubmittedAt fuera de la ventana del simulacro',
                'code'    => 'INVALID_TIME',
            ], 400);
        }

        // 6. Answers: length exacto, keys "1".."count" sin huecos, valores A-E|null.
        $shapeError = $this->validateAnswers($answers, $simulacro['count']);
        if ($shapeError !== null) {
            return $this->invalidShape($shapeError);
        }

        // 7. Insert. Si pierde la carrera, atrapa unique violation -> 409.
        try {
            // Importante: convertir a UTC antes de persistir. El $dateFormat
            // de Eloquent es 'Y-m-d H:i:s' sin offset, asi que si pasas un
            // Carbon en Lima, Postgres lo interpreta como UTC y pierdes 5h.
            $envio = SimulacroEnvio::create([
                'user_id'             => $user->id,
                'simulacro_id'        => $simulacroId,
                'answers'             => $answers,
                'client_submitted_at' => $submittedAt->setTimezone('UTC'),
                'server_received_at'  => $now->setTimezone('UTC'),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                $existing = SimulacroEnvio::where('user_id', $user->id)
                    ->where('simulacro_id', $simulacroId)
                    ->firstOrFail();
                return $this->respondEnvio($existing, 409);
            }
            throw $e;
        }

        return $this->respondEnvio($envio, 200);
    }

    private function respondEnvio(SimulacroEnvio $envio, int $status): JsonResponse
    {
        return response()->json([
            'status'            => 'enviado',
            'clientSubmittedAt' => $envio->client_submitted_at->setTimezone(self::TZ)->toIso8601String(),
            'serverReceivedAt'  => $envio->server_received_at->setTimezone(self::TZ)->toIso8601String(),
        ], $status);
    }

    private function invalidShape(string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => 'INVALID_SHAPE'], 400);
    }

    /**
     * Devuelve un mensaje de error o null si answers es valido.
     */
    private function validateAnswers(array $answers, int $count): ?string
    {
        if (count($answers) !== $count) {
            return "answers debe tener exactamente {$count} entradas";
        }

        for ($i = 1; $i <= $count; $i++) {
            $key = (string) $i;
            if (! array_key_exists($key, $answers)) {
                return "answers requiere keys '1'..'{$count}' sin huecos. Falta '{$key}'";
            }
            $val = $answers[$key];
            if ($val !== null && ! in_array($val, ['A', 'B', 'C', 'D', 'E'], true)) {
                return "answers['{$key}'] debe ser 'A'..'E' o null";
            }
        }

        return null;
    }
}
