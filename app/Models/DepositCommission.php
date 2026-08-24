<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositCommission extends Model
{
    protected $fillable = [
        'deposit_amount',
        'commission_percent',
        'is_active',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'commission_percent' =>  'decimal:5',
        'is_active' => 'boolean',
    ];
}