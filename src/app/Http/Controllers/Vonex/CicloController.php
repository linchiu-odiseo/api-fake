<?php

namespace App\Http\Controllers\Vonex;

use App\Http\Controllers\Controller;
use App\Services\FakeDataGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CicloController extends Controller
{
    public function __construct(private readonly FakeDataGenerator $fake)
    {
    }

    public function aulas(Request $request, string $ciclo_id): JsonResponse
    {
        $aulas = $this->fake->aulasForCiclo($ciclo_id);

        if ($aulas === null) {
            return response()->json([
                'message' => "Ciclo '{$ciclo_id}' no encontrado.",
            ], 404);
        }

        return response()->json($this->fake->paginate($aulas, $request));
    }
}
