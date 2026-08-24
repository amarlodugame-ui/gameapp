<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCodeRedemption extends Model
{
    protected $fillable = [
        'gift_code_id',
        'user_id',
        'reward_amount',
        'redeemed_at',
    ];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function giftCode()
    {
        return $this->belongsTo(GiftCode::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
