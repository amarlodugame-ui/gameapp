<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'round_id',
        'bet_type',
        'bet_value',
        'amount',
        'multiplier',
        'qty',
        'payout',
        'status',
        'game_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'payout' => 'decimal:2',
        'qty' => 'integer',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(GameRound::class, 'round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
