<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Envios de simulacros del PWA NeonPanda (Fase 2 — endpoint 2).
 *
 * Contrato: temp-notes/api-fake-fase2-request.md
 *
 * Idempotencia 409: UNIQUE (user_id, simulacro_id). Segundo POST del mismo
 * alumno al mismo simulacro -> unique violation -> el controller atrapa y
 * devuelve 409 con los datos del primer envio.
 *
 * Auditoria: no se borran filas. Conservar simulacros pasados es valor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulacro_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('simulacro_id'); // "sim-{slug}-YYYY-MM-DD"
            $table->jsonb('answers');       // {"1":"C","2":null,...}
            $table->timestampTz('client_submitted_at');
            $table->timestampTz('server_received_at');
            $table->timestamps();

            $table->unique(['user_id', 'simulacro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulacro_envios');
    }
};
