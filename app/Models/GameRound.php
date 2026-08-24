<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRound extends Model
{
    protected $fillable = [
        'period_id',
        'game_type',
        'start_time',
        'end_time',
        'result_number',
        'result_color',
        'result_bigsmall',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'result_number' => 'integer',
    ];

    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class, 'round_id');
    }
}
