<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth para rutas token-en-path (estilo Vonex real):
 *   GET /v3/sedes/token=<KEY>
 *
 * La API real responde 404 con cuerpo vacio cuando el token no coincide.
 * Replicamos ese comportamiento para acoplarnos 1:1.
 */
class EnsureTokenInPath
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('app.api_key');
        $provided   = (string) ($request->route('token') ?? '');

        if ($configured === '' || $provided === '' || ! hash_equals($configured, $provided)) {
            return response('', 404);
        }

        return $next($request);
    }
}
