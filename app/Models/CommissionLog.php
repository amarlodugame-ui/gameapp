<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionLog extends Model
{
    protected $fillable = [
        'to_id',
        'from_id',
        'level',
        'amount',
        'type',
        'details',
        'trx',
    ];

    protected $casts = [
        'to_id' => 'integer',
        'from_id' => 'integer',
        'amount' => 'decimal:8',
    ];

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_id');
    }
}