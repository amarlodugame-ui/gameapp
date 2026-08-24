<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RebateHistory extends Model
{
    protected $fillable = [
        'user_id',
        'tab',
        'claim_date',
        'total_bet_amount',
        'rebate_rate_percent',
        'rebate_amount',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'total_bet_amount' => 'decimal:2',
        'rebate_rate_percent' => 'decimal:4',
        'rebate_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}