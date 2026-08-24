<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VipRewardHistory extends Model
{
    protected $fillable = [
        'user_id',
        'vip_level_id',
        'reward_type',
        'claim_key',
        'reward_period',
        'reward_amount',
        'reward_love',
        'bet_total',
        'required_bet',
        'claim_rate',
        'balance_before',
        'balance_after',
        'rewarded_at',
        'eligible_from',
        'eligible_to',
        'meta',
    ];

protected $casts = [
    'meta' => 'array',
    'eligible_from' => 'datetime',
    'eligible_to' => 'datetime',
    'rewarded_at' => 'datetime',
    'reward_amount' => 'decimal:2',
    'bet_total' => 'decimal:2',
    'required_bet' => 'decimal:2',
    'claim_rate' => 'decimal:2',
    'balance_before' => 'decimal:2',
    'balance_after' => 'decimal:2',
];

    public function vipLevel()
    {
        return $this->belongsTo(VipLevel::class);
    }
}