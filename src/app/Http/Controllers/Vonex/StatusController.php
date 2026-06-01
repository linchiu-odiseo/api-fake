<?php

namespace App\Http\Controllers\Vonex;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'status'  => 'OK',
            'message' => 'conectado a API-Vonex-Intranet . . . 😸 !',
        ]);
    }
}
