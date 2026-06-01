<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('app.api_key');
        $provided   = (string) ($request->header('X-API-Key') ?? $request->bearerToken() ?? '');

        if ($configured === '' || $provided === '' || ! hash_equals($configured, $provided)) {
            return response()->json(['message' => 'API key invalida o ausente.'], 401);
        }

        return $next($request);
    }
}
