<?php

use App\Http\Controllers\Vonex\AulaController;
use App\Http\Controllers\Vonex\CicloController;
use App\Http\Controllers\Vonex\SedeController;
use App\Http\Controllers\Vonex\StatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('apikey')->group(function () {
    Route::get('/',                                  [StatusController::class, 'index']);

    Route::get('/sedes',                             [SedeController::class,   'index']);
    Route::get('/ciclos',                            [CicloController::class,  'index']);
    Route::get('/ciclos/{ciclo_id}/aulas',           [CicloController::class,  'aulas']);
    Route::get('/aulas/{aula_id}/alumnos',           [AulaController::class,   'alumnos']);
    Route::get('/aulas/{aula_id}/tutores',           [AulaController::class,   'tutores']);
});
