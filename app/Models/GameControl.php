<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameControl extends Model
{
       protected $fillable = [
        'game_type',
        'mode',
        'manual_number',
        'profit_margin',
    ];

    protected $casts = [
        'manual_number' => 'integer',
        'profit_margin' => 'decimal:2',
    ];
}
