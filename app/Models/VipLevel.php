<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class VipLevel extends Model
{
    protected $fillable = ['vip','level_class','right_img','head_img','require_exp','progress_bg','level_up_reward_money','level_up_reward_love','monthly_reward_money','monthly_reward_love','safe_rate','rebate_rate',];
    
    protected $casts = ['require_exp' => 'integer','level_up_reward_money' => 'integer','level_up_reward_love' => 'integer','monthly_reward_money' => 'integer','monthly_reward_love' => 'integer','safe_rate' => 'decimal:2','rebate_rate' => 'decimal:2',];
    public function rewardHistories(){ return $this->hasMany(VipRewardHistory::class, 'vip_level_id');}
}
