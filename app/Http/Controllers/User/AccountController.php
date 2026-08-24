<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use Illuminate\Http\Request;
use App\Models\StrongBoxTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\VipRewardHistory;
use Illuminate\Support\Facades\Auth;
use App\Models\Bet;
use Carbon\Carbon;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\UserNotification;

use Illuminate\Http\RedirectResponse;
use Throwable;

class AccountController extends Controller
{
    public function index()
    {$user = auth()->user();
    $userNotificationUnreadCount = UserNotification::where('user_id', $user->id)
        ->where('is_read', false)
        ->count();
        return UserSpaPage::render('user/account/Index',[
            'userRefcode' => $user->referral_code,
            'userCreated' => $user->created_at->format('Y-m-d H:i:s'),
            'userNotificationUnreadCount' => $userNotificationUnreadCount,
        ]);
    }
    public function feedback()
    {
        return UserSpaPage::render('user/account/Feedback');
    }
    public function notification()
    {
        return UserSpaPage::render('user/account/Notification');
    }
public function guide()
{
    $sections = [
        [
            'title' => '1. How to Register',
            'texts' => [
                '- Fill Your Phone Number',
                '- Set Your Own Password (6 letters)',
                '- Confirm The Password',
                '- Fill Your Recommendation Code',
                '- Click I Have Read And Agree',
                '- Click Register',
            ],
            'images' => [
                asset('assets/guide/1.png'),
            ],
            'imageClass' => 'guide-image-large',
        ],
        [
            'title' => '2. How to Betting',
            'texts' => [
                'Click start game then choose 1 minute, 3 minute, 5 minute or 10 minute.',
                'Green : if the result shows 1,3,7,9',
                'Red : if the result shows 2,4,6,8',
                'Violet : if the result shows 0 or 5',
                'Small : if the result shows 0,1,2,3,4',
                'Big : if the result shows 5,6,7,8,9',
                'This company not allowed to place Illegal betting',
                'Exp: Betting (Big and small together) or (Red and Green together) in the same time.',
            ],
            'images' => [
                asset('assets/guide/2.png'),
            ],
            'imageClass' => 'guide-image-large',
        ],
        [
            'title' => '3. How to Recharge',
            'texts' => [
                'Click Wallet Icon, Click The Recharge Button, and we have five ways to make a recharge (BANK TRANSFER, E-WALLET)',
            ],
            'images' => [
                asset('assets/guide/3.png'),
            ],
            'imageClass' => 'guide-image-medium',
        ],
        [
            'title' => '4. How to Withdraw',
            'texts' => [
                'Click Wallet Icon, Click Withdraw Button.',
                '- Enter withdraw amount',
                '- Make Sure Your Total Bet Until Zero',
                '- Select Your Bank Account Or Add Your Bank Account',
                '- Input Amount You Want To Withdraw',
                '- Input Your Login Password',
            ],
            'images' => [
                asset('assets/guide/4.png'),
            ],
            'imageClass' => 'guide-image-medium',
        ],
        [
            'title' => '7. Promotion',
            'texts' => [
                '- If you have a downline or referral member use your own link to register and if they make a recharge you can claim a reward. The agent will get a minimum commission of 0.6% (level 1) and 0.18% (level 2) from each transaction that is done by the referral (Added every day at 00:30 AM.) If the accumulated transactions of the Referral reach a certain target, the agent will get an additional bonus with the following rebates.',
                '- You Can Click The Sharing Invitation Poster To See The Barcode',
            ],
            'images' => [
                asset('assets/guide/5.png'),
            ],
            'imageClass' => 'guide-image-small',
        ],
        [
            'title' => '8. Account Security',
            'texts' => [
                'Go To My Icon, Click Setting Icon.',
                '- Click Login Password.',
                '- Re-Enter Your Login Password.',
                '- Enter Strongest New Password, and Confirm Your Password.',
                '- Click Save Changes.',
            ],
            'images' => [
                asset('assets/guide/6.png'),
            ],
            'imageClass' => 'guide-image-small',
        ],
        [
            'title' => '10. App Download',
            'texts' => [
                'Click top right corner download icon, you can download the app and easy to use',
            ],
            'images' => [
                asset('assets/guide/7.png'),
            ],
            'imageClass' => 'guide-image-small',
        ],
        [
            'title' => '11. About',
            'texts' => [
                'Click About for more details regarding Privacy Policy and Risk Disclosure Agreement.',
            ],
            'images' => [
                asset('assets/guide/8.png'),
            ],
            'imageClass' => 'guide-image-small',
        ],
        [
            'title' => '11. Gift',
            'texts' => [
                '- Click Gift Exchange',
                '- Input The Unique Code To Redemption Code The Money',
                '- Click Receive',
            ],
            'images' => [
                asset('assets/guide/9.png'),
                asset('assets/guide/10.png'),
            ],
            'imageClass' => 'guide-image-small',
        ],
    ];

    return UserSpaPage::render('user/account/Guide', [
        'sections' => $sections,
    ]);
}
public function about()
{
    return UserSpaPage::render('user/account/About');
}
public function language()
{
    return UserSpaPage::render('user/account/Language');
}

public function wallet()
{
    $user = Auth::user();

    $totalBalance = (float) ($user->balance ?? 0);

    $totalWithdraw = (float) Withdraw::where('user_id', $user->id)
        ->whereIn('status', [2])
        ->sum('amount');

    $totalDeposit = (float) Deposit::where('user_id', $user->id)
        ->where('status', 1)
        ->sum('amount');

    $mainWallet = $totalBalance;
    $thirdPartyWallet = 0;

    $walletTotal = $mainWallet + $thirdPartyWallet;

    $mainWalletPercent = $walletTotal > 0 ? round(($mainWallet / $walletTotal) * 100) : 0;
    $thirdPartyWalletPercent = $walletTotal > 0 ? round(($thirdPartyWallet / $walletTotal) * 100) : 0;

    return UserSpaPage::render('user/account/Wallet', [
        'wallet' => [
            'total_balance' => $totalBalance,
            'total_withdraw' => $totalWithdraw,
            'total_deposit' => $totalDeposit,
            'main_wallet' => $mainWallet,
            'third_party_wallet' => $thirdPartyWallet,
            'main_wallet_percent' => $mainWalletPercent,
            'third_party_wallet_percent' => $thirdPartyWalletPercent,
            'ar_game' => 0,
            'lottery' => 0,
        ],
    ]);
}
    

    public function betRecords()
    {
        $user = auth()->user();
        $betRecords = Bet::where('user_id', $user->id)->latest()->paginate(20);
        return UserSpaPage::render('user/account/GameHistory', [
            'betRecords' => $betRecords,
        ]);
    }
public function transactionHistory(Request $request)
{
    $user = auth()->user();

    $transactions = $user->transactions()
        ->whereNotIn('remark', ['referral_commission'])
        ->latest()
        ->paginate(20)
        ->withQueryString();

    return UserSpaPage::render('user/account/TransactionHistory', [
        'transactions' => $transactions,
    ]);
}







private function getVipRulesData(): array
{
    return [
        [
            'title' => 'Upgrade standard',
            'description' => 'The VIP member\'s experience points (valid bet amount) that meet the requirements of the corresponding rank will be promoted to the corresponding VIP level, the member\'s VIP data statistics period starts from 00:00:00 days VIP system launched. VIP level calculation is refreshed every 10 minutes! The corresponding experience level is calculated according to valid odds 1:1 !',
        ],
        [
            'title' => 'Upgrade order',
            'description' => 'The VIP level that meets the corresponding requirements can be promoted by one level every day, but the VIP level cannot be promoted by leapfrogging.',
        ],
        [
            'title' => 'Level maintenance',
            'description' => 'VIP members need to complete the maintenance requirements of the corresponding level within 30 days after the VIP level change; if the promotion is completed during this period, the maintenance requirements will be calculated according to the current level.',
        ],
        [
            'title' => 'Downgrade standard',
            'description' => 'If a VIP member fails to complete the corresponding level maintenance requirements within 30 days, the system will automatically deduct the experience points corresponding to the level. If the experience points are insufficient, the level will be downgraded, and the corresponding discounts will be adjusted to the downgraded level accordingly.',
        ],
        [
            'title' => 'Upgrade Bonus',
            'description' => 'The upgrade benefits can be claimed on the VIP page after the member reaches the VIP membership level, and each VIP member can only get the upgrade reward of each level once.',
        ],
        [
            'title' => 'Monthly reward',
            'description' => 'VIP members can earn the highest level of VIP rewards once a month. Can only be received once a month. Prizes cannot be accumulated. And any unclaimed rewards will be refreshed on the next settlement day. When receiving the highest level of monthly rewards this month, monthly rewards earned in this month will be deducted.',
        ],
        [
            'title' => 'Real-time rebate',
            'description' => 'The higher the VIP level, the higher the return rate, all the games are calculated in real time and can be self-rewarded!',
        ],
        [
            'title' => 'Safe',
            'description' => 'VIP members who have reached the corresponding level will get additional benefits on safe deposit based on the member\'s VIP level.',
        ],
    ];
}





    public function vip()
    {
        $authUser = Auth::user()->loadMissing('vipLevel:id,vip');

        $levels = VipLevel::query()
            ->orderBy('vip', 'asc')
            ->get();

        $totalExp = $this->getTotalVipExp($authUser->id);
        $currentVipLevel = $this->resolveCurrentVipLevel($levels, $totalExp);
        $vipNumber = (int) ($currentVipLevel->vip ?? 0);

        $vipLevelsData = $levels->map(function ($item) use ($authUser, $totalExp) {
            $progress = $this->buildLevelProgressData($authUser->id, $item, $totalExp);

            return [
                'id' => (int) $item->id,
                'vip' => (int) $item->vip,
                'levelClass' => (string) $item->level_class,
                'rightImg' => (string) $item->right_img,
                'headImg' => (string) $item->head_img,
                'requireExp' => (float) $item->require_exp,
                'progressBg' => (string) $item->progress_bg,

                'progressCurrent' => (float) $progress['current'],
                'progressTarget' => (float) $progress['target'],
                'progressText' => (string) $progress['text'],
                'progressMode' => (string) $progress['mode'],

                'levelUpRewardMoney' => (float) $item->level_up_reward_money,
                'levelUpRewardLove' => (int) $item->level_up_reward_love,

                'monthlyRewardMoney' => (float) $item->monthly_reward_money,
                'monthlyRewardLove' => (int) $item->monthly_reward_love,
                'monthlyRewardEnabled' => (bool) $item->monthly_reward_enabled,
                'monthlyClaimRate' => (float) $item->monthly_claim_rate,

                'safeRate' => (float) $item->safe_rate,
                'rebateRate' => (float) $item->rebate_rate,
            ];
        })->values();

        $benefitsDataProp = $levels->mapWithKeys(function ($item) {
            return [
                'VIP' . $item->vip => [
                    [
                        'title' => 'Level up rewards',
                        'desc' => 'Each account can only receive 1 time',
                        'right1' => (string) $item->level_up_reward_money,
                        'right2' => (string) $item->level_up_reward_love,
                        'type' => 'money_love',
                    ],
                    [
                        'title' => 'Monthly reward',
                        'desc' => 'Each unlocked VIP level can receive 1 time per month',
                        'right1' => (string) $item->monthly_reward_money,
                        'right2' => (string) $item->monthly_reward_love,
                        'type' => 'money_love',
                    ],
                    [
                        'title' => 'Safe',
                        'desc' => 'Increase the extra income of the safe',
                        'right' => rtrim(rtrim(number_format((float) $item->safe_rate, 2, '.', ''), '0'), '.') . '%',
                        'type' => 'percent_safe',
                    ],
                    [
                        'title' => 'Rebate rate',
                        'desc' => 'Increase income of rebate',
                        'right' => rtrim(rtrim(number_format((float) $item->rebate_rate, 2, '.', ''), '0'), '.') . '%',
                        'type' => 'percent_rebate',
                    ],
                ],
            ];
        });

        $rewardStateMap = $this->buildRewardStateMap($authUser, $levels, $totalExp);

        $rewardHistoryData = VipRewardHistory::query()
            ->with('vipLevel:id,vip')
            ->where('user_id', $authUser->id)
            ->latest('id')
            ->take(100)
            ->get()
            ->map(function ($item) {
                $isMonthly = $item->reward_type === 'monthly_reward';
                $vipNo = (int) ($item->vipLevel->vip ?? 0);

                return [
                    'id' => (int) $item->id,
                    'historyType' => 'claim',
                    'vip' => $vipNo,
                    'statusClass' => 'green',
                    'statusText' => 'Successfully received',
                    'title' => $isMonthly ? "Successfully received [VIP{$vipNo} Monthly Salary reward]" : "Successfully received [VIP{$vipNo} Level up reward]",
                    'rewardType' => (string) $item->reward_type,
                    'rewardTitle' => $isMonthly ? 'Monthly Salary reward' : 'Level up reward',

                    'rewardAmount' => number_format((float) $item->reward_amount, 2, '.', ''),
                    'rewardLove' => (int) ($item->reward_love ?? 0),

                    'betTotal' => number_format((float) $item->bet_total, 2, '.', ''),
                    'requiredBet' => number_format((float) ($item->required_bet ?? 0), 2, '.', ''),
                    'claimRate' => number_format((float) ($item->claim_rate ?? 0), 2, '.', ''),
                    'balanceBefore' => number_format((float) $item->balance_before, 2, '.', ''),
                    'balanceAfter' => number_format((float) $item->balance_after, 2, '.', ''),

                    'rewardedAt' => optional($item->rewarded_at)->format('Y-m-d H:i:s')
                        ?? optional($item->created_at)->format('Y-m-d H:i:s'),
                ];
            })
            ->values();

        $avatar = '/assets/png/1-a6662edb.webp';
        if (!empty($authUser->image)) {
            $avatar = str_starts_with($authUser->image, 'http')
                ? $authUser->image
                : asset($authUser->image);
        }
        return UserSpaPage::render('user/account/Vip', [
            'userData' => [
                'avatar' => $avatar,
                'nickname' => $authUser->username ?: ($authUser->name ?: 'Member'),
                'vip' => $vipNumber,
                'vipClass' => 'n' . $vipNumber,
                'exp' => $totalExp,
                'payoutDays' => now()->startOfDay()->diffInDays(now()->copy()->endOfMonth()->startOfDay()),
            ],
            'vipLevelsData' => $vipLevelsData,
            'benefitsDataProp' => $benefitsDataProp,
            'rewardHistoryData' => $rewardHistoryData,
            'rewardStateMap' => $rewardStateMap,
            'betRuleText' => 'Bet ৳1 = 1 EXP',
            'tipText' => 'VIP monthly rewards are settled level-wise at 2:00 am on the 1st of every month',
            'rulesData' => $this->getVipRulesData(),
        ]);
    }
protected function currentClaimPeriod(): string
{
    return now()->format('Y-m');
}

protected function nextPeriod(string $period): string
{
    return \Carbon\Carbon::createFromFormat('Y-m', $period)
        ->startOfMonth()
        ->addMonth()
        ->format('Y-m');
}

protected function comparePeriods(string $a, string $b): int
{
    $aDate = \Carbon\Carbon::createFromFormat('Y-m', $a)->startOfMonth();
    $bDate = \Carbon\Carbon::createFromFormat('Y-m', $b)->startOfMonth();

    if ($aDate->equalTo($bDate)) {
        return 0;
    }

    return $aDate->lt($bDate) ? -1 : 1;
}

protected function getMonthlyDueExp(VipLevel $vipLevel): float
{
    return round(
        ((float) $vipLevel->require_exp * (float) $vipLevel->monthly_claim_rate) / 100,
        2
    );
}

protected function getValidBetSince(int $userId, ?\Carbon\Carbon $from = null): float
{
    $normalBetQuery = \Illuminate\Support\Facades\DB::table('bets')
        ->where('user_id', $userId)
        ->whereIn('status', ['win', 'lose']);

    if ($from) {
        $normalBetQuery->where('created_at', '>=', $from);
    }

    $aviatorBetQuery = \Illuminate\Support\Facades\DB::table('aviator_bets')
        ->where('user_id', $userId)
        ->where('is_demo', 0)
        ->whereNotNull('settled_at')
        ->whereIn('status', ['won', 'lost', 'cashed_out', 'win', 'lose', 'cashout']);

    if ($from) {
        $aviatorBetQuery->where('settled_at', '>=', $from);
    }

    return round(
        (float) $normalBetQuery->sum('amount') + (float) $aviatorBetQuery->sum('amount'),
        2
    );
}
    public function claimVipLevelUp(VipLevel $vipLevel): RedirectResponse
    {
        $authUser = Auth::user();

        try {
            DB::transaction(function () use ($authUser, $vipLevel) {
                $totalExp = $this->getTotalVipExp($authUser->id);
                $claimKey = "level_up:{$authUser->id}:{$vipLevel->id}";

                if ($totalExp < (float) $vipLevel->require_exp) {
                     toast('Not eligible for level up reward claim.', 'error', 'cross');
                    return back();
                }

                $alreadyClaimed = VipRewardHistory::query()
                    ->where('claim_key', $claimKey)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyClaimed) {
                     toast('This level up reward has already been claimed.', 'error', 'cross');
                        return back();
                }

                $user = User::query()->lockForUpdate()->findOrFail($authUser->id);

                $balanceBefore = (float) $user->balance;
                $rewardMoney = (float) $vipLevel->level_up_reward_money;
                $rewardLove = (int) $vipLevel->level_up_reward_love;

                if ($rewardMoney > 0) {
                    $user->increment('balance', $rewardMoney);
                }

                $user->refresh();

                VipRewardHistory::query()->create([
                    'user_id' => $user->id,
                    'vip_level_id' => $vipLevel->id,
                    'reward_type' => 'level_up',
                    'claim_key' => $claimKey,
                    'reward_period' => 'once',
                    'reward_amount' => $rewardMoney,
                    'reward_love' => $rewardLove,
                    'bet_total' => $totalExp,
                    'required_bet' => (float) $vipLevel->require_exp,
                    'claim_rate' => 0,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $user->balance,
                    'rewarded_at' => now(),
                    'eligible_from' => null,
                    'eligible_to' => now(),
                    'meta' => [
                        'source' => 'vip_level_up_claim',
                    ],
                ]);

                createUserNotification(
                    $user->id,
                    'VIP Level Up Reward Claim',
                    "You have successfully claimed your VIP level up reward of amount {$rewardMoney}.",
                    'vip_level_up_reward_claim'
                );

                $trx = new Transaction();
                $trx->user_id = $user->id;
                $trx->amount = $rewardMoney;
                $trx->post_balance = $user->balance;
                $trx->charge = 0;
                $trx->trx_type = '+';
                $trx->details = 'VIP Level Up Reward claimed: ' . $rewardMoney . ' BDT';
                $trx->remark = 'vip_level_up_reward_claim';
                $trx->trx = get_trx();
                $trx->save();
            });

            toast('Level up reward claimed successfully.', 'success', 'success');
        } catch (Throwable $e) {
            toast($e->getMessage() ?: 'Level up reward claim failed.', 'error', 'cross');
        }

        return back();
    }

    public function claimVipMonthly(VipLevel $vipLevel): RedirectResponse
    {
        $authUser = Auth::user();

        try {
            DB::transaction(function () use ($authUser, $vipLevel) {
                $period = now()->format('Y-m');
                $totalExp = $this->getTotalVipExp($authUser->id);

                if ($totalExp < (float) $vipLevel->require_exp) {
                     toast('This VIP level is not unlocked yet.', 'error', 'cross');
                     return back();
                }

                if (
                    !(bool) $vipLevel->monthly_reward_enabled ||
                    (
                        (float) $vipLevel->monthly_reward_money <= 0 &&
                        (int) $vipLevel->monthly_reward_love <= 0
                    )
                ) {
                     toast('Monthly reward is not available for this level.', 'error', 'cross');
                     return back();
                }

                $claimKey = "monthly:{$authUser->id}:{$vipLevel->id}:{$period}";

                $alreadyClaimed = VipMonthlyClaim::query()
                    ->where('claim_key', $claimKey)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyClaimed) {
                     toast('You have already claimed the monthly reward for this VIP level this month.', 'error', 'cross');
                        return back();
                }

                $state = VipMonthlyState::query()
                    ->lockForUpdate()
                    ->firstOrCreate(
                        [
                            'user_id' => $authUser->id,
                            'vip_level_id' => $vipLevel->id,
                        ],
                        [
                            'is_activated' => false,
                            'first_claimed_period' => null,
                            'last_claimed_period' => null,
                            'next_claim_period' => $period,
                            'monthly_due_exp' => $this->getMonthlyDueExp($vipLevel),
                            'outstanding_due_exp' => 0,
                            'last_bet_checkpoint_at' => null,
                            'meta' => null,
                        ]
                    );

                $currentMonthlyDue = $this->getMonthlyDueExp($vipLevel);
                $state->monthly_due_exp = $currentMonthlyDue;

                $carryBefore = 0.00;
                $qualifiedBet = 0.00;
                $carryAfterSettlement = 0.00;
                $carryAfterClaim = $currentMonthlyDue;

                if ((bool) $state->is_activated) {
                    $preview = $this->makeMonthlyPreview($state, $vipLevel, $authUser->id, $period);

                    $carryBefore = (float) $preview['requiredBet'];
                    $qualifiedBet = (float) $preview['qualifiedBet'];
                    $carryAfterSettlement = (float) $preview['carryAfterSettlement'];

                    if (!$preview['claimable']) {
                         toast('Need more valid bet: ' . number_format((float) $preview['remainingBet'], 2, '.', ''), 'error', 'cross');
                         return back();
                    }
                }

                $user = User::query()->lockForUpdate()->findOrFail($authUser->id);

                $balanceBefore = (float) $user->balance;
                $rewardMoney = (float) $vipLevel->monthly_reward_money;
                $rewardLove = (int) $vipLevel->monthly_reward_love;

                if ($rewardMoney > 0) {
                    $user->increment('balance', $rewardMoney);
                }

                $user->refresh();

                $monthlyClaim = VipMonthlyClaim::query()->create([
                    'user_id' => $user->id,
                    'vip_level_id' => $vipLevel->id,
                    'claim_period' => $period,
                    'claim_key' => $claimKey,
                    'reward_amount' => $rewardMoney,
                    'reward_love' => $rewardLove,
                    'carry_before' => $carryBefore,
                    'qualified_bet' => $qualifiedBet,
                    'carry_after_settlement' => $carryAfterSettlement,
                    'carry_after_claim' => $carryAfterClaim,
                    'monthly_due_exp' => $currentMonthlyDue,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $user->balance,
                    'claimed_at' => now(),
                    'meta' => [
                        'source' => 'vip_monthly_claim',
                        'first_claim' => !(bool) $state->is_activated,
                    ],
                ]);

                // optional mirror history for existing UI/history section
                VipRewardHistory::query()->create([
                    'user_id' => $user->id,
                    'vip_level_id' => $vipLevel->id,
                    'reward_type' => 'monthly_reward',
                    'claim_key' => $claimKey,
                    'reward_period' => $period,
                    'reward_amount' => $rewardMoney,
                    'reward_love' => $rewardLove,
                    'bet_total' => $qualifiedBet,
                    'required_bet' => $carryBefore,
                    'claim_rate' => (float) $vipLevel->monthly_claim_rate,
                    'balance_before' => $balanceBefore,
                    'balance_after' => (float) $user->balance,
                    'rewarded_at' => now(),
                    'eligible_from' => $state->last_bet_checkpoint_at,
                    'eligible_to' => now(),
                    'meta' => [
                        'source' => 'vip_monthly_claim',
                        'monthly_claim_id' => $monthlyClaim->id,
                        'carry_before' => $carryBefore,
                        'qualified_bet' => $qualifiedBet,
                        'carry_after_settlement' => $carryAfterSettlement,
                        'carry_after_claim' => $carryAfterClaim,
                    ],
                ]);

                $wasActivated = (bool) $state->is_activated;

                $state->is_activated = true;

                if (!$wasActivated) {
                    $state->first_claimed_period = $period;
                }

                $state->last_claimed_period = $period;
                $state->next_claim_period = $this->nextPeriod($period);
                $state->outstanding_due_exp = $currentMonthlyDue;
                $state->last_bet_checkpoint_at = now();
                $state->meta = [
                    'last_monthly_claim_id' => $monthlyClaim->id,
                    'last_claim_period' => $period,
                ];
                $state->save();

                createUserNotification(
                    $user->id,
                    'VIP Monthly Reward Claim',
                    "You have successfully claimed your VIP monthly reward of amount {$rewardMoney}.",
                    'vip_monthly_reward_claim'
                );

                $trx = new Transaction();
                $trx->user_id = $user->id;
                $trx->amount = $rewardMoney;
                $trx->post_balance = $user->balance;
                $trx->charge = 0;
                $trx->trx_type = '+';
                $trx->details = 'VIP Monthly Reward claimed: ' . $rewardMoney . ' BDT';
                $trx->remark = 'vip_monthly_reward_claim';
                $trx->trx = get_trx();
                $trx->save();
            });

            toast('Monthly reward claimed successfully.', 'success', 'success');
        } catch (Throwable $e) {
            toast($e->getMessage() ?: 'Monthly reward claim failed.', 'error', 'cross');
        }

        return back();
    }

    protected function getTotalVipExp(int $userId): float
    {
        $normalBet = (float) DB::table('bets')
            ->where('user_id', $userId)
            ->whereIn('status', ['win', 'lose'])
            ->sum('amount');

        $aviatorBet = (float) DB::table('aviator_bets')
            ->where('user_id', $userId)
            ->where('is_demo', 0)
            ->whereNotNull('settled_at')
            ->whereIn('status', ['won', 'lost', 'cashed_out', 'win', 'lose', 'cashout'])
            ->sum('amount');

        return round($normalBet + $aviatorBet, 2);
    }

    protected function resolveCurrentVipLevel($levels, float $totalExp): ?VipLevel
    {
        return $levels
            ->sortBy('require_exp')
            ->filter(fn ($level) => $totalExp >= (float) $level->require_exp)
            ->last();
    }


    protected function buildRewardStateMap($user, $levels, float $totalExp): array
    {
        $map = [];

        foreach ($levels as $level) {
            $map['VIP' . $level->vip] = [
                'levelUp' => $this->buildLevelUpRewardState($user->id, $level, $totalExp),
                'monthly' => $this->buildMonthlyRewardState($user->id, $level, $totalExp),
            ];
        }

        return $map;
    }

    protected function buildLevelUpRewardState(int $userId, VipLevel $level, float $totalExp): array
    {
        $claimKey = "level_up:{$userId}:{$level->id}";
        $alreadyClaimed = VipRewardHistory::query()
            ->where('claim_key', $claimKey)
            ->exists();

        $hasReward = ((float) $level->level_up_reward_money > 0)
            || ((int) $level->level_up_reward_love > 0);

        $qualified = $totalExp >= (float) $level->require_exp;
        $claimable = $hasReward && $qualified && !$alreadyClaimed;

        return [
            'showCard' => $hasReward,
            'claimable' => $claimable,
            'claimed' => $alreadyClaimed,
            'buttonText' => $alreadyClaimed ? 'Received' : ($claimable ? 'Claim' : 'Locked'),
            'buttonClass' => $claimable ? 'claim_reword' : 'noActive',
            'hint' => $qualified ? '' : ('Need bet: ' . number_format(max(0, ((float) $level->require_exp - $totalExp)), 2, '.', '')),
        ];
    }

protected function buildMonthlyRewardState(int $userId, VipLevel $level, float $totalExp): array
{
    $hasMonthlyReward = (bool) $level->monthly_reward_enabled
        && (
            (float) $level->monthly_reward_money > 0
            || (int) $level->monthly_reward_love > 0
        );

    if (!$hasMonthlyReward) {
        return [
            'showCard' => false,
            'claimable' => false,
            'claimed' => false,
            'buttonText' => 'Unavailable',
            'buttonClass' => 'noActive',
            'hint' => '',
            'requiredBet' => 0,
            'qualifiedBet' => 0,
            'remainingBet' => 0,
            'outstandingDueExp' => 0,
            'monthlyDueExp' => 0,
        ];
    }

    if ($totalExp < (float) $level->require_exp) {
        return [
            'showCard' => false,
            'claimable' => false,
            'claimed' => false,
            'buttonText' => 'Locked',
            'buttonClass' => 'noActive',
            'hint' => '',
            'requiredBet' => 0,
            'qualifiedBet' => 0,
            'remainingBet' => 0,
            'outstandingDueExp' => 0,
            'monthlyDueExp' => $this->getMonthlyDueExp($level),
        ];
    }

    $currentPeriod = $this->currentClaimPeriod();
    $claimKey = "monthly:{$userId}:{$level->id}:{$currentPeriod}";

    $claimedThisMonth = VipMonthlyClaim::query()
        ->where('claim_key', $claimKey)
        ->exists();

    $state = VipMonthlyState::query()->firstOrNew(
        [
            'user_id' => $userId,
            'vip_level_id' => $level->id,
        ],
        [
            'is_activated' => false,
            'next_claim_period' => $currentPeriod,
            'monthly_due_exp' => $this->getMonthlyDueExp($level),
            'outstanding_due_exp' => 0,
        ]
    );

    $nextClaimPeriod = $state->next_claim_period ?: $currentPeriod;

    // claim করে ফেললে current month-এ আর card দেখাবে না
    if ($claimedThisMonth) {
        return [
            'showCard' => false,
            'claimable' => false,
            'claimed' => true,
            'buttonText' => 'Received',
            'buttonClass' => 'noActive',
            'hint' => '',
            'requiredBet' => 0,
            'qualifiedBet' => 0,
            'remainingBet' => 0,
            'outstandingDueExp' => (float) $state->outstanding_due_exp,
            'monthlyDueExp' => $this->getMonthlyDueExp($level),
        ];
    }

    // next claim month আসার আগে card দেখাবে না
    if ($this->comparePeriods($currentPeriod, $nextClaimPeriod) < 0) {
        return [
            'showCard' => false,
            'claimable' => false,
            'claimed' => false,
            'buttonText' => 'Unavailable',
            'buttonClass' => 'noActive',
            'hint' => '',
            'requiredBet' => 0,
            'qualifiedBet' => 0,
            'remainingBet' => 0,
            'outstandingDueExp' => (float) $state->outstanding_due_exp,
            'monthlyDueExp' => $this->getMonthlyDueExp($level),
        ];
    }

    if (!(bool) $state->is_activated) {
        return [
            'showCard' => true,
            'claimable' => true,
            'claimed' => false,
            'buttonText' => 'Claim',
            'buttonClass' => 'claim_reword',
            'hint' => 'First monthly claim is free',
            'requiredBet' => 0,
            'qualifiedBet' => 0,
            'remainingBet' => 0,
            'outstandingDueExp' => 0,
            'monthlyDueExp' => $this->getMonthlyDueExp($level),
        ];
    }

    $preview = $this->makeMonthlyPreview($state, $level, $userId, $currentPeriod);

    return [
        'showCard' => true,
        'claimable' => (bool) $preview['claimable'],
        'claimed' => false,
        'buttonText' => $preview['claimable'] ? 'Claim' : 'Need more bet',
        'buttonClass' => $preview['claimable'] ? 'claim_reword' : 'noActive',
        'hint' => $preview['claimable']
            ? ''
            : ('Need ' . number_format((float) $preview['remainingBet'], 2, '.', '') . ' more valid bet'),
        'requiredBet' => (float) $preview['requiredBet'],
        'qualifiedBet' => (float) $preview['qualifiedBet'],
        'remainingBet' => (float) $preview['remainingBet'],
        'outstandingDueExp' => (float) $preview['carryAfterSettlement'],
        'monthlyDueExp' => (float) $preview['monthlyDueExp'],
    ];
}
protected function makeMonthlyPreview(
    VipMonthlyState $state,
    VipLevel $level,
    int $userId,
    ?string $currentPeriod = null
): array {
    $currentPeriod = $currentPeriod ?: $this->currentClaimPeriod();
    $monthlyDueExp = $this->getMonthlyDueExp($level);

    $storedOutstanding = round((float) $state->outstanding_due_exp, 2);

    $rawRefill = round(
        $this->getValidBetSince(
            $userId,
            $state->last_bet_checkpoint_at
                ? \Carbon\Carbon::parse($state->last_bet_checkpoint_at)
                : null
        ),
        2
    );

    $targetPeriod = $state->next_claim_period ?: $currentPeriod;

    $requiredBet = $storedOutstanding;
    $missedPeriods = 0;

    while ($this->comparePeriods($targetPeriod, $currentPeriod) < 0) {
        $requiredBet += $monthlyDueExp;
        $missedPeriods++;
        $targetPeriod = $this->nextPeriod($targetPeriod);
    }

    $qualifiedBet = round(min($requiredBet, $rawRefill), 2);
    $remainingBet = round(max(0, $requiredBet - $rawRefill), 2);

    return [
        'currentPeriod' => $currentPeriod,
        'targetPeriod' => $targetPeriod,
        'monthlyDueExp' => $monthlyDueExp,
        'storedOutstanding' => $storedOutstanding,
        'rawRefill' => $rawRefill,
        'requiredBet' => round($requiredBet, 2),
        'qualifiedBet' => $qualifiedBet,
        'remainingBet' => $remainingBet,
        'carryAfterSettlement' => $remainingBet,
        'missedPeriods' => $missedPeriods,
        'claimable' => $this->comparePeriods($targetPeriod, $currentPeriod) === 0
            && $remainingBet <= 0,
    ];
}
protected function buildLevelProgressData(int $userId, VipLevel $level, float $totalExp): array
{
    $requireExp = round((float) $level->require_exp, 2);

    if ($totalExp < $requireExp) {
        return [
            'mode' => 'unlock',
            'current' => round(min($totalExp, $requireExp), 2),
            'target' => $requireExp,
            'text' => "{$requireExp} EXP can be leveled up",
        ];
    }

    $hasMonthlyReward = (bool) $level->monthly_reward_enabled
        && (
            (float) $level->monthly_reward_money > 0
            || (int) $level->monthly_reward_love > 0
        );

    if (!$hasMonthlyReward) {
        return [
            'mode' => 'unlock',
            'current' => $requireExp,
            'target' => $requireExp,
            'text' => 'Unlocked',
        ];
    }

    $state = VipMonthlyState::query()->firstOrNew(
        [
            'user_id' => $userId,
            'vip_level_id' => $level->id,
        ],
        [
            'is_activated' => false,
            'next_claim_period' => $this->currentClaimPeriod(),
            'monthly_due_exp' => $this->getMonthlyDueExp($level),
            'outstanding_due_exp' => 0,
        ]
    );

    if (!(bool) $state->is_activated) {
        return [
            'mode' => 'unlock',
            'current' => $requireExp,
            'target' => $requireExp,
            'text' => 'Unlocked',
        ];
    }

    $preview = $this->makeMonthlyPreview($state, $level, $userId, $this->currentClaimPeriod());

    $effectiveCurrent = round(max(0, $requireExp - (float) $preview['remainingBet']), 2);

    return [
        'mode' => 'monthly',
        'current' => $effectiveCurrent,
        'target' => $requireExp,
        'text' => $preview['remainingBet'] > 0
            ? ('Need ' . number_format((float) $preview['remainingBet'], 2, '.', '') . ' EXP to refill')
            : 'Ready for monthly claim',
    ];
}









public function strongBox()
{
    $user = auth()->user();

    $todayInterest = \App\Models\StrongBoxTransaction::where('user_id', $user->id)
        ->where('type', 'interest')
        ->whereDate('created_at', today())
        ->sum('amount');

    $totalInterest = \App\Models\StrongBoxTransaction::where('user_id', $user->id)
        ->where('type', 'interest')
        ->sum('amount');

    return UserSpaPage::render('user/account/StrongBox', [
        'summary' => [
            'strong_box_balance' => (float) $user->strong_box_balance,
            'wallet_balance' => (float) $user->balance, // user balance
            'today_interest' => (float) $todayInterest,
            'total_interest' => (float) $totalInterest,
            'daily_rate_percent' => 0.10,
        ]
    ]);
}

public function transferIn(Request $request)
{
    $request->validate([
        'amount' => ['required', 'numeric', 'min:1'],
    ]);

    $user = auth()->user();
    $amount = (float) $request->amount;

    if ($amount > $user->balance) {
        toast('Insufficient balance', 'error');
        return back();
    }

    DB::transaction(function () use ($user, $amount) {
        $user->decrement('balance', $amount); 
        $user->increment('strong_box_balance', $amount); 
        $user->refresh();
        StrongBoxTransaction::create([
            'user_id' => $user->id,
            'type' => 'in',
            'actor' => 'user',
            'amount' => $amount,
            'balance_after' => $user->strong_box_balance,
        ]);
        $trx = new Transaction();
        $trx->user_id = $user->id;
        $trx->amount = $amount;
        $trx->trx_type = '+';
        $trx->details = 'Transfer from Strong Box'; 
        $trx->remark = 'strong_box_transfer';
        $trx->save();
    });

    toast('Transfer success', 'success');
    return redirect()->route('user.account.strong-box');
}

public function transferOut()
{
    $user = auth()->user();
    $amount = (float) $user->strong_box_balance;

    if ($amount <= 0) {
        toast('No balance', 'error');
        return back();
    }

    DB::transaction(function () use ($user, $amount) {
        $user->increment('balance', $amount); 
        $user->update([
            'strong_box_balance' => 0,
        ]);

        StrongBoxTransaction::create([
            'user_id' => $user->id,
            'type' => 'out',
            'actor' => 'user',
            'amount' => $amount,
            'balance_after' => 0,
        ]);
        
        
            $trx = new Transaction();
            $trx->user_id = $user->id;
            $trx->amount = $amount;
            $trx->trx_type = '-';
            $trx->details = 'Transfer to Strong Box';
            $trx->remark = 'strong_box_transfer';
            $trx->save();
    });

    toast('Transfer out success', 'success');
    return redirect()->route('user.account.strong-box');
}


public function runStrongBoxInterest()
{

    $rate = 0.10 / 100;
    $processed = 0;
    $skipped = 0;

    $users = User::where('strong_box_balance', '>', 0)->get();

    foreach ($users as $user) {
        try {
            DB::transaction(function () use ($user, $rate, &$processed, &$skipped) {

                $alreadyGiven = StrongBoxTransaction::where('user_id', $user->id)
                    ->where('type', 'interest')
                    ->whereDate('created_at', today())
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyGiven) {
                    $skipped++;
                    return;
                }

                $interest = round($user->strong_box_balance * $rate, 2);

                if ($interest <= 0) {
                    $skipped++;
                    return;
                }

                $user->increment('balance', $interest);

                StrongBoxTransaction::create([
                    'user_id' => $user->id,
                    'type' => 'interest',
                    'actor' => 'system',
                    'amount' => $interest,
                    'balance_after' => $user->strong_box_balance,
                    'interest_at' => now(),
                ]);

                $processed++;
            });
        } catch (\Throwable $e) {
            $skipped++;
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'StrongBox interest run completed',
        'processed' => $processed,
        'skipped' => $skipped,
        'date' => now()->toDateString(),
    ]);
}

    public function strongBoxRecord()
    {
        $records = StrongBoxTransaction::where('user_id',auth()->id())->latest()->get();
        return UserSpaPage::render('user/account/strongBoxRecord',['records'=>$records]);
    }


    public function messages(Request $request)
    {
        $user = auth()->user();

        UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        $messages = UserNotification::where('user_id', $user->id)
            ->latest('id')
            ->get([
                'id',
                'title',
                'message',
                'type',
                'is_read',
                'read_at',
                'created_at',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => $item->message,
                    'type' => $item->type,
                    'is_read' => $item->is_read,
                    'time' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return UserSpaPage::render('user/account/Messages', [
            'messages' => $messages,
        ]);
    }

    public function deleteMessage(Request $request, $id)
    {
        $message = UserNotification::where('user_id', auth()->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $message->delete();

        toast('Message deleted', 'success', 'success');
        return back();
    }




    public function gameStats(Request $request)
    {
        $user = auth()->user();
        $range = $request->string('range')->toString() ?: 'today';

        [$start, $end] = $this->resolveStatsRange($range);

        $lotteryQuery = Bet::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end]);

        $lotteryStats = [
            'key' => 'lottery',
            'label' => 'lottery',
            'icon' => '/assets/png/loterry-13b4d059.webp',
            'total_bet' => (float) $lotteryQuery->sum('amount'),
            'number_of_bets' => (int) $lotteryQuery->count(),
            'winning_amount' => (float) $lotteryQuery->sum('payout'),
        ];

        $items = [
            $lotteryStats,
            [
                'key' => 'video',
                'label' => 'video',
                'icon' => '/assets/png/iconRealPerson-d6a539c2.webp',
                'total_bet' => 0,
                'number_of_bets' => 0,
                'winning_amount' => 0,
            ],
            [
                'key' => 'slot',
                'label' => 'Slot',
                'icon' => '/assets/png/iconSlots-dc0d52e9.webp',
                'total_bet' => 0,
                'number_of_bets' => 0,
                'winning_amount' => 0,
            ],
            [
                'key' => 'fish',
                'label' => 'Fish',
                'icon' => '/assets/png/iconFishing-467fb1f6.webp',
                'total_bet' => 0,
                'number_of_bets' => 0,
                'winning_amount' => 0,
            ],
            [
                'key' => 'sport',
                'label' => 'sport',
                'icon' => '/assets/png/iconPhysics-57f83ec6.webp',
                'total_bet' => 0,
                'number_of_bets' => 0,
                'winning_amount' => 0,
            ],
            [
                'key' => 'chesscard',
                'label' => 'ChessCard',
                'icon' => '/assets/png/iconChess-13aafe08.webp',
                'total_bet' => 0,
                'number_of_bets' => 0,
                'winning_amount' => 0,
            ],
        ];

        $summaryTotalBet = collect($items)->sum('total_bet');

        return UserSpaPage::render('user/account/GameStats', [
            'filters' => [
                'range' => $range,
            ],
            'tabs' => [
                ['key' => 'today', 'label' => 'Today'],
                ['key' => 'yesterday', 'label' => 'Yesterday'],
                ['key' => 'week', 'label' => 'This week'],
                ['key' => 'month', 'label' => 'This month'],
            ],
            'summary' => [
                'total_bet' => (float) $summaryTotalBet,
            ],
            'items' => $items,
        ]);
    }

    protected function resolveStatsRange(string $range): array
    {
        $now = now();

        return match ($range) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            'month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
        };
    }

    
}
