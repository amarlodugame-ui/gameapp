<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\GameRound;
use App\Models\VipRewardHistory;
use Illuminate\Support\Facades\DB;
use App\Models\HomeBanner;
use App\Models\Announcement;
use App\Models\DepositCommission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Inertia\Response as InertiaResponse;






class HomeController extends Controller
{

        public function currentRound()
    {
        $round = GameRound::where('game_type', '30s')
            ->where('status', 'running')
            ->latest('id')
            ->first();

        return response()->json([
            'success' => true,
            'period_id' => $round->period_id,
            'round_id' => $round->id,
            'end_time' => $round->end_time->format('Y-m-d H:i:s'),
            'server_time' => now()->format('Y-m-d H:i:s'),
            'status' => $round->status
        ]);
    }
    

    
    public function index(): JsonResponse|InertiaResponse
    {
        return UserSpaPage::render('Home', $this->homeProps());
    }

    public function spa(): JsonResponse|InertiaResponse
    {
        return UserSpaPage::render('Home', $this->homeProps());
    }

    /**
     * Build the same payload used by the legacy Inertia Home page.
     *
     * @return array<string, mixed>
     */
    private function homeProps(): array
    {
        $page = config('home2', []);
        $announcements = [];

        session()->forget('announcements_shown');

        if (! session()->has('announcements_shown')) {
            $announcements = Announcement::query()
                ->where('is_active', true)
                ->latest()
                ->get(['id', 'title', 'content', 'button_text', 'link'])
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'content' => $item->content,
                        'button_text' => $item->button_text ?: 'Confirm',
                        'link' => $item->link,
                    ];
                })
                ->toArray();

            session(['announcements_shown' => true]);
        }

        $bonusItems = DepositCommission::query()
            ->where('is_active', 1)
            ->orderByDesc('deposit_amount')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'deposit_amount' => (float) $item->deposit_amount,
                    'bonus_amount' => (float) $item->commission_percent,
                ];
            })
            ->values()
            ->all();

        if (Auth::check()) {
            Transaction::where('user_id', Auth::id())
                ->where('remark', 'like', 'complete_deposit_bonus_%')
                ->exists();
        }

        return [
            'page' => $page,
            'announcements' => $announcements,
            'bonusItems' => $bonusItems,
            // Preserve the current legacy Home contract exactly.
            'hasClaimedFirstRechargeBonus' => true,
        ];
    }

    public function processVipLevelRewards()
    {
        User::query()
            ->select('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $row) {
                    DB::transaction(function () use ($row) {
                        $user = User::lockForUpdate()->find($row->id);

                        if (! $user) {
                            return;
                        }

                        $totalBet = (float) DB::table('bets')
                            ->where('user_id', $user->id)
                            ->whereIn('status', ['win', 'lose'])
                            ->selectRaw('COALESCE(SUM(amount * IFNULL(qty, 1)), 0) as total_bet')
                            ->value('total_bet');

                        $eligibleLevels = VipLevel::query()
                            ->where('require_exp', '<=', $totalBet)
                            ->orderBy('vip', 'asc')
                            ->get();

                        if ($eligibleLevels->isEmpty()) {
                            return;
                        }

                        $alreadyRewardedLevelIds = VipRewardHistory::query()
                            ->where('user_id', $user->id)
                            ->where('reward_type', 'level_up')
                            ->pluck('vip_level_id')
                            ->toArray();

                        foreach ($eligibleLevels as $level) {
                            if (in_array($level->id, $alreadyRewardedLevelIds)) {
                                continue;
                            }

                            $beforeBalance = (float) $user->balance;
                            $rewardAmount = (float) $level->level_up_reward_money;
                            $afterBalance = $beforeBalance + $rewardAmount;

                            $user->balance = $afterBalance;
                            $user->vip_id = $level->id; 
                            $user->save();

                            VipRewardHistory::create([
                                'user_id' => $user->id,
                                'vip_level_id' => $level->id,
                                'reward_type' => 'level_up',
                                'reward_amount' => $rewardAmount,
                                'bet_total' => $totalBet,
                                'balance_before' => $beforeBalance,
                                'balance_after' => $afterBalance,
                                'rewarded_at' => now(),
                            ]);
                        }

                        $highestEligible = $eligibleLevels->last();

                        if ($highestEligible && (int) $user->vip_id !== (int) $highestEligible->id) {
                            $user->vip_id = $highestEligible->id;
                            $user->save();
                        }
                    });
                }
            });

        return response()->json([
            'status' => true,
            'message' => 'VIP level rewards processed successfully',
        ]);
    }





    
}
