<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AviatorBet extends Model
{
    protected $fillable = [
        'round_id',
        'target_round_id',
        'user_id',
         'is_demo',
        'bet_key',
        'slot',
        'amount',
        'status',
        'is_queued',
        'auto_cashout_enabled',
        'auto_cashout_multiplier',
        'cashout_multiplier',
        'cashout_amount',
        'placed_at',
        'cashed_out_at',
        'settled_at',
    ];

    protected $casts = [
           'is_demo' => 'boolean',
        'target_round_id' => 'integer',
        'slot' => 'integer',
        'amount' => 'decimal:2',
        'is_queued' => 'boolean',
        'auto_cashout_enabled' => 'boolean',
        'auto_cashout_multiplier' => 'decimal:2',
        'cashout_multiplier' => 'decimal:2',
        'cashout_amount' => 'decimal:2',
        'placed_at' => 'datetime',
        'cashed_out_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function round(): BelongsTo
    {
        return $this->belongsTo(AviatorRound::class, 'round_id');
    }

    public function targetRound(): BelongsTo
    {
        return $this->belongsTo(AviatorRound::class, 'target_round_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}