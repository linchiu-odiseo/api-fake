<?php

use App\Http\Controllers\Lumeria\SyllabusController as LumeriaSyllabusController;
use App\Http\Controllers\Vonex\AulaController;
use App\Http\Controllers\Vonex\CicloController;
use App\Http\Controllers\Vonex\SedeController;
use App\Http\Controllers\Vonex\StatusController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------
// Grupo BEARER / X-API-Key (modo "fake-only")
//
// Vonex hoy NO usa este modo (mete el token en el path, ver
// segundo grupo). Lo dejamos activo porque:
//   - lo usamos para curl/manual testing,
//   - es el modo al que Vonex migrara a futuro segun ellos.
//
// Quitar este grupo cuando Vonex confirme corte a bearer puro.
// ------------------------------------------------------------
Route::middleware('apikey')->group(function () {
    Route::get('/service-health',                                  [StatusController::class, 'index']);

    Route::get('/sedes',                             [SedeController::class,   'index']);
    Route::get('/ciclos',                            [CicloController::class,  'index']);
    Route::get('/ciclos/{ciclo_id}/aulas',           [CicloController::class,  'aulas']);
    Route::get('/aulas/{aula_id}/alumnos',           [AulaController::class,   'alumnos']);
    Route::get('/aulas/{aula_id}/tutores',           [AulaController::class,   'tutores']);

    // Lumeria fake (syllabus-sync consumer). Mismo middleware apikey/Bearer.
    Route::get('/cycles/{cycle_id}/courses',                       [LumeriaSyllabusController::class, 'courses']);
    Route::get('/cycles/{cycle_id}/courses/{course_id}/syllabus',  [LumeriaSyllabusController::class, 'syllabus']);
});

// ------------------------------------------------------------
// Grupo TOKEN EN PATH (modo "Vonex real")
//
//   GET /v3/sedes/token=<KEY>
//   GET /v3/ciclos/{ciclo_id}/aulas/token=<KEY>?page=2
//
// Token invalido/ausente -> 404 con cuerpo vacio (mimetiza la
// API real). El consumidor (Vonex sync) apunta aca.
// ------------------------------------------------------------
Route::middleware('apikey.path')->group(function () {
    Route::get('/sedes/token={token}',                             [SedeController::class,  'index'])->where('token', '[^/]+');
    Route::get('/ciclos/token={token}',                            [CicloController::class, 'index'])->where('token', '[^/]+');
    Route::get('/ciclos/{ciclo_id}/aulas/token={token}',           [CicloController::class, 'aulas'])->where('token', '[^/]+');
    Route::get('/aulas/{aula_id}/alumnos/token={token}',           [AulaController::class,  'alumnos'])->where('token', '[^/]+');
    Route::get('/aulas/{aula_id}/tutores/token={token}',           [AulaController::class,  'tutores'])->where('token', '[^/]+');
});
