<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySignInClaim extends Model
{
    protected $fillable = [
        'user_id',
        'day_no',
        'required_bet',
        'reward_amount',
        'bet_total',
        'qualified_date',
        'claimed_at',
    ];

    protected $casts = [
        'qualified_date' => 'date',
        'claimed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}