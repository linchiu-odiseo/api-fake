<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'ts' => now()->toIso8601String(),
        ]);
    }

    public function items(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'Alpha', 'price' => 10.50],
                ['id' => 2, 'name' => 'Beta',  'price' => 22.00],
                ['id' => 3, 'name' => 'Gamma', 'price' => 7.75],
            ],
        ]);
    }

    public function item(int $id): JsonResponse
    {
        return response()->json([
            'id'   => $id,
            'name' => "Item #{$id}",
            'meta' => ['random' => bin2hex(random_bytes(4))],
        ]);
    }

    public function echo(Request $request): JsonResponse
    {
        return response()->json([
            'method'  => $request->method(),
            'headers' => $request->headers->all(),
            'body'    => $request->all(),
        ]);
    }
}
