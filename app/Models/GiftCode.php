<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCode extends Model
{
    protected $fillable = [
        'code',
        'reward_amount',
        'max_users',
        'used_count',
        'is_used',
        'last_used_at'
    ];

    public function redemptions()
    {
        return $this->hasMany(GiftCodeRedemption::class);
    }
}
