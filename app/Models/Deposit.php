<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_TO_BE_PAID = 2;
    public const STATUS_REJECTED = 3;

    protected $fillable = [
        'user_id',
        'payment_method_currency',
        'amount',
        'charge',
        'payable_amount',
        'payment_id',
        'information',
        'trx_id',
        'status',
        'note','gateway_notify_response',
    ];

    protected $casts = [
        'information' => 'array',
        'user_id' => 'integer',
        'amount' => 'decimal:8',
        'charge' => 'decimal:8',
        'payable_amount' => 'decimal:8',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeToBePaid($query)
    {
        return $query->where('status', self::STATUS_TO_BE_PAID);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_TO_BE_PAID => 'To Be Paid',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }
}