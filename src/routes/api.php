<?php

use App\Http\Controllers\Lumeria\SyllabusController as LumeriaSyllabusController;
use App\Http\Controllers\NeonPanda\AuthController;
use App\Http\Controllers\Vonex\AulaController;
use App\Http\Controllers\Vonex\CicloController;
use App\Http\Controllers\Vonex\SedeController;
use App\Http\Controllers\Vonex\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API-Fake v3 is running.']);
});

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello, World! . . . 🍔']);
});

// ------------------------------------------------------------
Route::middleware('apikey')->group(function () {

    Route::get('/service-health', [StatusController::class, 'index']);

    // Vonex fake (cycle_sync consumer).
    Route::get('/sedes', [SedeController::class,   'index']);
    Route::get('/ciclos', [CicloController::class,  'index']);
    Route::get('/ciclos/{ciclo_id}/aulas', [CicloController::class,  'aulas']);
    Route::get('/aulas/{aula_id}/alumnos', [AulaController::class,   'alumnos']);
    Route::get('/aulas/{aula_id}/tutores', [AulaController::class,   'tutores']);

    // Lumeria fake (syllabus-sync consumer). 
    Route::get('/cycles/{cycle_id}/courses', [LumeriaSyllabusController::class, 'courses']);
    Route::get('/cycles/{cycle_id}/courses/{course_id}/syllabus', [LumeriaSyllabusController::class, 'syllabus']);


    // NeonPanda Auth fake (PWA consumer). 
    //   POST /v3/auth/login   -> publico (solo X-API-Key)
    //   POST /v3/auth/logout  -> protegido (X-API-Key + Bearer Sanctum)
    //   GET  /v3/auth/me      -> protegido (X-API-Key + Bearer Sanctum)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
    });
});



// ------------------------------------------------------------
// Grupo TOKEN EN PATH (modo "Vonex real")
//
//   GET /v3/sedes/token=<KEY>
//   GET /v3/ciclos/{ciclo_id}/aulas/token=<KEY>?page=2
// ------------------------------------------------------------
Route::middleware('apikey.path')->group(function () {
    Route::get('/sedes/token={token}', [SedeController::class,  'index'])->where('token', '[^/]+');
    Route::get('/ciclos/token={token}', [CicloController::class, 'index'])->where('token', '[^/]+');
    Route::get('/ciclos/{ciclo_id}/aulas/token={token}', [CicloController::class, 'aulas'])->where('token', '[^/]+');
    Route::get('/aulas/{aula_id}/alumnos/token={token}', [AulaController::class,  'alumnos'])->where('token', '[^/]+');
    Route::get('/aulas/{aula_id}/tutores/token={token}', [AulaController::class,  'tutores'])->where('token', '[^/]+');
});
