<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AviatorSetting extends Model
{
    protected $fillable = [
        'enabled',
        'betting_duration_ms',
        'result_duration_ms',
        'slow_factor',
        'max_bet_amount',
        'max_payout_amount',
        'house_edge_bps',
        'max_crash_multiplier',
        'sound_enabled_by_default',
        'public_heartbeat_ms',
        'engine_sleep_ms',
        'manual_next_crash_multiplier',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'betting_duration_ms' => 'integer',
        'result_duration_ms' => 'integer',
        'slow_factor' => 'decimal:2',
        'max_bet_amount' => 'integer',
        'max_payout_amount' => 'integer',
        'house_edge_bps' => 'integer',
        'max_crash_multiplier' => 'decimal:2',
        'sound_enabled_by_default' => 'boolean',
        'public_heartbeat_ms' => 'integer',
        'engine_sleep_ms' => 'integer',
        'manual_next_crash_multiplier' => 'decimal:2',
    ];
}