<?php

namespace App\Http\Controllers\NeonPanda;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Endpoints de auth para el PWA NeonPanda (Fase 1).
 *
 * Contrato: temp-notes/api-contract-request.md
 * Auth: Sanctum personal access tokens (sin expiracion).
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales invalidas'], 401);
        }

        $token = $user->createToken('neonpanda-pwa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'email' => $user->email,
                'name'  => $user->name,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'email' => $user->email,
                'name'  => $user->name,
            ],
        ]);
    }
}
