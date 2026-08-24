<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrongBoxTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'interest_at','actor'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
