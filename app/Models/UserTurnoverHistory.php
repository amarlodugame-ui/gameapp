<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTurnoverHistory extends Model
{
    protected $fillable = [
        'user_id',
        'old_turnover_required',
        'change_amount',
        'type',
        'new_turnover_required',
        'note',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'old_turnover_required' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'new_turnover_required' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}