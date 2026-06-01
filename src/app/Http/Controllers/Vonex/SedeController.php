<?php

namespace App\Http\Controllers\Vonex;

use App\Http\Controllers\Controller;
use App\Services\FakeDataGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SedeController extends Controller
{
    public function __construct(private readonly FakeDataGenerator $fake)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->fake->paginate($this->fake->sedes(), $request)
        );
    }
}
