<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipMonthlyClaim extends Model
{
    protected $fillable = [
        'user_id',
        'vip_level_id',
        'claim_period',
        'claim_key',
        'reward_amount',
        'reward_love',
        'carry_before',
        'qualified_bet',
        'carry_after_settlement',
        'carry_after_claim',
        'monthly_due_exp',
        'balance_before',
        'balance_after',
        'claimed_at',
        'meta',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'carry_before' => 'decimal:2',
        'qualified_bet' => 'decimal:2',
        'carry_after_settlement' => 'decimal:2',
        'carry_after_claim' => 'decimal:2',
        'monthly_due_exp' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'claimed_at' => 'datetime',
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