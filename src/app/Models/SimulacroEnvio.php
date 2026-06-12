<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulacroEnvio extends Model
{
    protected $fillable = [
        'user_id',
        'simulacro_id',
        'answers',
        'client_submitted_at',
        'server_received_at',
    ];

    protected $casts = [
        'answers'             => 'array',
        'client_submitted_at' => 'immutable_datetime',
        'server_received_at'  => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
