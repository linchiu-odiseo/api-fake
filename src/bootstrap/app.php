<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'v3',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'apikey'      => \App\Http\Middleware\EnsureApiKey::class,
            'apikey.path' => \App\Http\Middleware\EnsureTokenInPath::class,
        ]);

        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        // No tenemos ruta 'login' (API JSON). Devolvemos null para que el
        // middleware Authenticate no llame a route('login') y solo lance
        // AuthenticationException -> render JSON 401.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // El fake es API JSON-only. Si una request a /v3/* falla auth, Laravel
        // por defecto redirige a route('login') (que no existe) -> 500 HTML.
        // Forzamos render JSON para todas las rutas del fake.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('v3/*') || $request->expectsJson();
        });

        // Sanctum lanza AuthenticationException cuando falta/expira el bearer.
        // El handler por defecto intenta redirigir a route('login') que no
        // existe en una API. Cortamos en seco con 401 JSON.
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('v3/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });
    })->create();
