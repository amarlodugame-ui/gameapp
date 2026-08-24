<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipMonthlyState extends Model
{
    protected $fillable = [
        'user_id',
        'vip_level_id',
        'is_activated',
        'first_claimed_period',
        'last_claimed_period',
        'next_claim_period',
        'monthly_due_exp',
        'outstanding_due_exp',
        'last_bet_checkpoint_at',
        'meta',
    ];

    protected $casts = [
        'is_activated' => 'boolean',
        'monthly_due_exp' => 'decimal:2',
        'outstanding_due_exp' => 'decimal:2',
        'last_bet_checkpoint_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vipLevel(): BelongsTo
    {
        return $this->belongsTo(VipLevel::class);
    }
}