<?php

namespace App\Models;

use App\Models\User;
use App\Models\WithdrawWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdraw extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_REJECTED = 3;

    protected $fillable = [
        'user_id',
        'withdraw_wallet_id',
        'payout_currency_code',
        'amount',
        'number',
        'charge',
        'net_amount',
        'response_id',
        'last_error',
        'information',
        'meta_field',
        'feedback',
        'trx_id',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'withdraw_wallet_id' => 'integer',
        'amount' => 'decimal:2',
        'charge' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'information' => 'array',
        'meta_field' => 'array',
        'status' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected $appends = [
        'status_label',
        'status_class',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(WithdrawWallet::class, 'withdraw_wallet_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_PENDING => 'pending',
            self::STATUS_PROCESSING => 'processing',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'fail',
            default => 'default',
        };
    }
}