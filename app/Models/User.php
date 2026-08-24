<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Mass Assignable Fields
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone_code',
        'phone',
        'password',
        'balance',
        'game_balance',
        'turnover_required',    
        'turnover_completed',
        'referral_code',
        'referred_by',
        'image',
        'role',
        'status','strong_box_balance','ip_address',
    ];

    /**
     * Hidden Fields
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',

            'balance' => 'decimal:2',
            'game_balance' => 'decimal:2',
            'turnover_required' => 'decimal:2',
            'turnover_completed' => 'decimal:2',

            'status' => 'boolean',
        ];
    }
public function bets(): HasMany
{
    return $this->hasMany(Bet::class);
}public function notifications()
{
    return $this->hasMany(\App\Models\UserNotification::class);
}

public function assignedGiftCodes()
{
    return $this->hasMany(\App\Models\GiftCode::class, 'assigned_user_id');
}

    // User who referred this user
    public function referrer(): BelongsTo { return $this->belongsTo(User::class, 'referred_by'); }

    // Users referred by this user
    public function referrals(): HasMany { return $this->hasMany(User::class, 'referred_by'); }
    public function giftRedemptions() {    return $this->hasMany(GiftCodeRedemption::class); }
    public function deposits(): HasMany {  return $this->hasMany(Deposit::class); }

    public function withdraws(): HasMany { return $this->hasMany(Withdraw::class); }

    public function withdrawWallets(): HasMany { return $this->hasMany(WithdrawWallet::class); }
    public function transactions() {             return $this->hasMany(Transaction::class, 'user_id'); }
    public function vipRewardHistories(){        return $this->hasMany(VipRewardHistory::class, 'user_id');}


    public function vipLevel(){return $this->belongsTo(\App\Models\VipLevel::class, 'vip_id');}


    public function receivedCommissionLogs(){return $this->hasMany(\App\Models\CommissionLog::class, 'to_id');}
    public function sentCommissionLogs(){ return $this->hasMany(\App\Models\CommissionLog::class, 'from_id');}

    public function refBy(){return $this->belongsTo(User::class, 'referred_by');}

    /*
    |--------------------------------------------------------------------------
    | Helper Methods (Optional but Recommended for Game Site)
    |--------------------------------------------------------------------------
    */
    public function isActive(): bool { return $this->status === true; }

    public function isDemo(): bool { return $this->role === 'demo'; }

    public function isRealUser(): bool { return $this->role === 'user'; }
    public function profileImage() { return $this->belongsTo(ProfileImage::class); }
    public function loginHistories() { return $this->hasMany(UserLogin::class); }
}