<?php

namespace App\Models;

use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithdrawWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'full_name',
        'account_number',
        'is_active',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'masked_account',
        'type_class',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdraws(): HasMany
    {
        return $this->hasMany(Withdraw::class, 'withdraw_wallet_id');
    }

    public function getMaskedAccountAttribute(): string
    {
        $value = (string) $this->account_number;

        if (mb_strlen($value) <= 4) {
            return $value;
        }

        return substr($value, 0, 3) . str_repeat('*', max(strlen($value) - 6, 3)) . substr($value, -3);
    }

    public function getTypeClassAttribute(): string
    {
        return strtoupper($this->type);
    }
}