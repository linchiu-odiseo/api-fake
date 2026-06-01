<?php

namespace App\Http\Controllers\Vonex;

use App\Http\Controllers\Controller;
use App\Services\FakeDataGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AulaController extends Controller
{
    public function __construct(private readonly FakeDataGenerator $fake)
    {
    }

    public function alumnos(Request $request, string $aula_id): JsonResponse
    {
        $alumnos = $this->fake->alumnosForAula($aula_id);

        if ($alumnos === null) {
            return response()->json([
                'message' => "Aula '{$aula_id}' no encontrada.",
            ], 404);
        }

        return response()->json($this->fake->paginate($alumnos, $request));
    }

    public function tutores(Request $request, string $aula_id): JsonResponse
    {
        $tutores = $this->fake->tutoresForAula($aula_id);

        if ($tutores === null) {
            return response()->json([
                'message' => "Aula '{$aula_id}' no encontrada.",
            ], 404);
        }

        return response()->json($this->fake->paginate($tutores, $request));
    }
}
