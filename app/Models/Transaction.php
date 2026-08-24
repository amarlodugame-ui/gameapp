<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'charge',
        'post_balance',
        'trx_type',
        'trx',
        'details',
        'remark',
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'amount'       => 'decimal:8',
        'charge'       => 'decimal:8',
        'post_balance' => 'decimal:8',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}