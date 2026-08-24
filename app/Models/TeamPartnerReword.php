<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamPartnerReword extends Model
{
    protected $guarded = [];

    protected $casts = [
        'bonus_amount' => 'decimal:2',
        'current_turnover' => 'decimal:2',
    ];

}
