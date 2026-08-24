<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AviatorRound extends Model
{
    protected $fillable = [
        'round_no',
        'status',
        'crash_multiplier',
        'betting_duration_ms',
        'flight_duration_ms',
        'result_duration_ms',
        'slow_factor',
        'betting_starts_at',
        'flying_starts_at',
        'crashed_at',
        'finished_at',
        'settled_at',
    ];

    protected $casts = [
        'crash_multiplier' => 'decimal:2',
        'slow_factor' => 'decimal:2',
        'betting_starts_at' => 'datetime',
        'flying_starts_at' => 'datetime',
        'crashed_at' => 'datetime',
        'finished_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function bets(): HasMany
    {
        return $this->hasMany(AviatorBet::class, 'round_id');
    }
}