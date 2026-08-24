<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyTaskClaim extends Model
{
    protected $fillable = [
        'user_id',
        'task_key',
        'task_title',
        'task_group',
        'required_amount',
        'reward_amount',
        'completed_amount',
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