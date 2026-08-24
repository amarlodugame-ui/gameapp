<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'content',
        'button_text',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
    ];
}