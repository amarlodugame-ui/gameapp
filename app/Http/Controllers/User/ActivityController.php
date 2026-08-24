<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\GiftCode;
use App\Models\Deposit;
use App\Models\GiftCodeRedemption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\DailyTaskClaim;


use App\Models\DailySignInClaim;



use App\Models\Bet;
use App\Models\RebateHistory;
use App\Models\User;
use Inertia\Response;
use App\Models\Transaction;
use App\Models\DepositCommission;


class ActivityController extends Controller
{

public function firstRecharge()
{
    $user = Auth::user();

    $totalDeposit = (float) ($user->deposits()
    ->where('status', 1)
    ->orderBy('id', 'asc')
    ->value('amount') ?? 0);

    $claimedRemarks = Transaction::where('user_id', $user->id)
        ->where('remark', 'like', 'complete_deposit_bonus_%')
        ->pluck('remark')
        ->toArray();
        // all ready claimed once then return user.home
        $alreadyClaimed = Transaction::where('user_id', $user->id)->where('remark', 'like', 'complete_deposit_bonus_%')->exists();
        if($alreadyClaimed){
            toast('You have already claimed the first recharge bonus.', 'error', 'cross');
            return redirect()->route('user.home');
        }


    $claimedIds = collect($claimedRemarks)
        ->map(function ($remark) {
            return (int) str_replace('complete_deposit_bonus_', '', $remark);
        })
        ->filter()
        ->values()
        ->all();

    $commissions = DepositCommission::where('is_active', 1)
        ->orderBy('deposit_amount', 'asc')
        ->get();

    $eligibleCurrent = $commissions
        ->filter(function ($item) use ($totalDeposit, $claimedIds) {
            return $totalDeposit >= (float) $item->deposit_amount && !in_array($item->id, $claimedIds);
        })
        ->sortByDesc('deposit_amount')
        ->first();

    $eligibleId = $eligibleCurrent?->id;

    $commissions = $commissions
        ->sortByDesc('deposit_amount')
        ->values()
        ->map(function ($item) use ($totalDeposit, $claimedIds, $eligibleId) {
            $bonusAmount = (float) $item->commission_percent;
            $isClaimed = in_array($item->id, $claimedIds);

            return [
                'id' => $item->id,
                'deposit_amount' => (float) $item->deposit_amount,
                'commission_percent' => (float) $item->commission_percent,
                'bonus_amount' => round($bonusAmount, 2),
                'progress_amount' => min($totalDeposit, (float) $item->deposit_amount),
                'progress_percent' => (float) $item->deposit_amount > 0
                    ? min(100, (($totalDeposit / (float) $item->deposit_amount) * 100))
                    : 0,
                'claimed' => $isClaimed,
                'can_claim' => !$isClaimed && $eligibleId === $item->id,
            ];
        });

    return UserSpaPage::render('user/activity/FirstRecharge', [
        'commissions' => $commissions,
        'totalDeposit' => $totalDeposit,
    ]);
}
public function claimFirstRecharge($id)
{
    $authUser = Auth::user();

    $commission = DepositCommission::where('id', $id)
        ->where('is_active', 1)
        ->firstOrFail();

    $alreadyClaimedThisSlab = Transaction::where('user_id', $authUser->id)
        ->where('remark', 'complete_deposit_bonus_' . $commission->id)
        ->exists();

    if ($alreadyClaimedThisSlab) {
        toast('You have already claimed this bonus.', 'error', 'cross');
        return redirect()->back();
    }

    $totalDeposit = (float) $authUser->deposits()
        ->where('status', 1)
        ->sum('amount');

    $claimedRemarks = Transaction::where('user_id', $authUser->id)
        ->where('remark', 'like', 'complete_deposit_bonus_%')
        ->pluck('remark')
        ->toArray();

    $claimedIds = collect($claimedRemarks)
        ->map(function ($remark) {
            return (int) str_replace('complete_deposit_bonus_', '', $remark);
        })
        ->filter()
        ->values()
        ->all();

    $eligibleCommission = DepositCommission::where('is_active', 1)
        ->orderBy('deposit_amount', 'desc')
        ->get()
        ->first(function ($item) use ($totalDeposit, $claimedIds) {
            return $totalDeposit >= (float) $item->deposit_amount
                && !in_array($item->id, $claimedIds);
        });

    if (!$eligibleCommission) {
        toast('No bonus is available for claim right now.', 'error', 'cross');
        return redirect()->back();
    }

    if ((int) $eligibleCommission->id !== (int) $commission->id) {
        toast('You can only claim the currently eligible bonus slab.', 'error', 'cross');
        return redirect()->back();
    }

    DB::transaction(function () use ($authUser, $commission) {
        $user = DB::table('users')
            ->where('id', $authUser->id)
            ->lockForUpdate()
            ->first();

        $alreadyClaimedThisSlab = Transaction::where('user_id', $authUser->id)
            ->where('remark', 'complete_deposit_bonus_' . $commission->id)
            ->lockForUpdate()
            ->exists();

        if ($alreadyClaimedThisSlab) {
            throw new \Exception('Already claimed');
        }

        $totalDeposit = (float) Deposit::where('user_id', $authUser->id)
            ->where('status', 1)
            ->sum('amount');

        $claimedRemarks = Transaction::where('user_id', $authUser->id)
            ->where('remark', 'like', 'complete_deposit_bonus_%')
            ->lockForUpdate()
            ->pluck('remark')
            ->toArray();

        $claimedIds = collect($claimedRemarks)
            ->map(function ($remark) {
                return (int) str_replace('complete_deposit_bonus_', '', $remark);
            })
            ->filter()
            ->values()
            ->all();

        $eligibleCommission = DepositCommission::where('is_active', 1)
            ->orderBy('deposit_amount', 'desc')
            ->lockForUpdate()
            ->get()
            ->first(function ($item) use ($totalDeposit, $claimedIds) {
                return $totalDeposit >= (float) $item->deposit_amount
                    && !in_array($item->id, $claimedIds);
            });

        if (!$eligibleCommission || (int) $eligibleCommission->id !== (int) $commission->id) {
            throw new \Exception('Invalid bonus slab');
        }

        $bonusAmount = (float) $commission->commission_percent;
        $newBalance = (float) $user->balance + $bonusAmount;

        DB::table('users')
            ->where('id', $authUser->id)
            ->update([
                'balance' => $newBalance,
                'updated_at' => now(),
            ]);

        Transaction::create([
            'user_id' => $authUser->id,
            'amount' => $bonusAmount,
            'charge' => 0,
            'post_balance' => $newBalance,
            'trx_type' => '+',
            'trx' => get_trx(),
            'details' => 'Complete deposit bonus claimed for deposit amount ' . number_format($commission->deposit_amount, 2),
            'remark' => 'complete_deposit_bonus_' . $commission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    toast('Bonus claimed successfully!', 'success', 'success');
    return redirect()->route('user.home');
}






public function index()
{
    $activityBanners = [
        [
            'image' => '/assets/banner/banner.jpg',
            'title' => 'আমাদের ওয়েবসাইটে আপনাকে স্বাগতম!!',
        ],
        // [
        //     'image' => '/assets/banner/banner.jpg',
        //     'title' => '⭐️Member Activities Winning Streak⭐️',
        // ],
        // [
        //     'image' => '/assets/banner/banner.jpg',
        //     'title' => '🔴 Youtube Creative Video 🔴',
        // ],
        // [
        //     'image' => '/assets/banner/banner.jpg',
        //     'title' => 'Lucky "10" Days Of Interest',
        // ],
        // [
        //     'image' => '/assets/banner/banner.jpg',
        //     'title' => '❗️ Mysterious Gift ❗️',
        // ],
        // [
        //     'image' => '/assets/banner/banner.jpg',
        //     'title' => '⭐️Aviator High Betting Award⭐️',
        // ],
    ];
    $user = auth()->user();
    $sumTotalBonus = Transaction::where('user_id', $user->id)
        ->where(function ($query) {
            $query->whereIn('remark', [
                'daily_task_reward',
                'betting_rebate',
                'super_jackpot_reward',
                'daily_deposit_reward',
                'gift_code_redeem',
                'balance_adjustment'
            ])
            ->orWhere('remark', 'LIKE', 'complete_deposit_bonus_%');
        })
        ->sum('amount');
    $sumTodayBonus = Transaction::where('user_id', $user->id)
    ->where(function ($query) {
        $query->whereIn('remark', [
            'daily_task_reward',
            'betting_rebate',
            'super_jackpot_reward',
            'daily_deposit_reward',
            'gift_code_redeem',
            'balance_adjustment'
        ])
        ->orWhere('remark', 'LIKE', 'complete_deposit_bonus_%');
    })
    ->whereDate('created_at', today())
    ->sum('amount');

    $bonusSummary = [
        'today_bonus' => $sumTodayBonus,
        'total_bonus' => $sumTotalBonus,
    ];


    $topMenus = [
        [
            'title' => 'Activity Award',
            'class' => 'a1',
            'route' => route('user.activity.daily-tasks'),
        ],
        [
            'title' => 'Betting rebate',
            'class' => 'a3',
            'route' => route('user.activity.main.laundry'),
        ],
        [
            'title' => 'Super Jackpot',
            'class' => 'a4',
            'route' => route('user.activity.main.super-jackpot'),
        ],
        [
            'title' => 'Invite Wheel',
            'class' => 'a6',
            'route' => null,
        ],
    ];

    $giftItems = [
        [
            'image' => '/assets/png/signInBanner-ff4a210f.webp',
            'title' => 'Gifts',
            'description' => 'Enter the redemption code to receive gift rewards',
            'route' => route('user.activity.main.redeem-gift'),
        ],
        [
            'image' => '/assets/png/giftRedeem-bb2f7a92.webp',
            'title' => 'Attendance bonus',
            'description' => 'The more consecutive days you sign in, the higher the reward will be.',
            'route' => route('user.activity.main.daily-sign-in'),
        ],
    ];

    return UserSpaPage::render('user/activity/Index', [
        'activityBanners' => $activityBanners,
        'bonusSummary' => $bonusSummary,
        'topMenus' => $topMenus,
        'giftItems' => $giftItems,
    ]);
}










    protected function dailyTaskDefinitions(): array
    {
        return [
            // [
            //     'key' => 'newbie_gift_pack',
            //     'title' => 'Gift package for newcomers',
            //     'description' => 'Gift package for newcomers',
            //     'group' => 'daily',
            //     'type' => 'newbie',
            //     'required_amount' => 3,
            //     'reward_amount' => 30,
            //     'progress_mode' => 'count',
            //     'bet_filter' => null,
            // ],

            [
                'key' => 'daily_bet_1000',
                'title' => 'Daily betting bonus',
                'description' => 'Daily betting bonus',
                'group' => 'daily',
                'type' => 'bet',
                'required_amount' => 1000,
                'reward_amount' => 10,
                'progress_mode' => 'amount',
                'bet_filter' => null,
            ],
            [
                'key' => 'daily_bet_10000',
                'title' => 'Daily betting bonus',
                'description' => 'Daily betting bonus',
                'group' => 'daily',
                'type' => 'bet',
                'required_amount' => 10000,
                'reward_amount' => 80,
                'progress_mode' => 'amount',
                'bet_filter' => null,
            ],
            [
                'key' => 'daily_bet_100000',
                'title' => 'Daily betting bonus',
                'description' => 'Daily betting bonus',
                'group' => 'daily',
                'type' => 'bet',
                'required_amount' => 100000,
                'reward_amount' => 800,
                'progress_mode' => 'amount',
                'bet_filter' => null,
            ],

            [
                'key' => 'daily_bingo_bet_5000',
                'title' => 'Daily BINGO betting bonus',
                'description' => 'Daily BINGO betting bonus',
                'group' => 'daily',
                'type' => 'bingo',
                'required_amount' => 5000,
                'reward_amount' => 50,
                'progress_mode' => 'amount',
                'bet_filter' => 'bingo',
            ],
            [
                'key' => 'daily_bingo_bet_100000',
                'title' => 'Daily BINGO betting bonus',
                'description' => 'Daily BINGO betting bonus',
                'group' => 'daily',
                'type' => 'bingo',
                'required_amount' => 100000,
                'reward_amount' => 500,
                'progress_mode' => 'amount',
                'bet_filter' => 'bingo',
            ],
        ];
    }

    protected function getTodayBetAmount(int $userId, ?string $gameType = null): float
    {
        $query = DB::table('bets')->where('user_id', $userId)->whereDate('created_at', today());

        if ($gameType) {
            $query->where('game_type', $gameType);
        }
        return (float) $query->sum('amount');
    }

    protected function getTodayBetCount(int $userId): int
    {
        return (int) DB::table('bets')->where('user_id', $userId)->whereDate('created_at', today())->count();
    }
protected function buildDailyTasks(int $userId): array
{
    $definitions = $this->dailyTaskDefinitions();

    $claimedKeys = DailyTaskClaim::where('user_id', $userId)
        ->whereDate('qualified_date', today())
        ->pluck('task_key')
        ->toArray();

    $todayBetTotal = $this->getTodayBetAmount($userId);
    $todayBingoBetTotal = $this->getTodayBetAmount($userId, 'bingo');
    $todayBetCount = $this->getTodayBetCount($userId);

    $tasks = [];

    foreach ($definitions as $task) {
        $progress = 0;

        if ($task['progress_mode'] === 'count') {
            $progress = $todayBetCount;
        } else {
            $progress = $task['bet_filter'] === 'bingo'
                ? $todayBingoBetTotal
                : $todayBetTotal;
        }

        $isClaimed = in_array($task['key'], $claimedKeys);
        $isCompleted = $progress >= $task['required_amount'];

        $status = 'unfinished';
        if ($isClaimed) {
            $status = 'claimed';
        } elseif ($isCompleted) {
            $status = 'claimable';
        }

        $tasks[] = [
            'key' => $task['key'],
            'title' => $task['title'],
            'description' => $task['description'],
            'group' => $task['group'],
            'type' => $task['type'],
            'required_amount' => (float) $task['required_amount'],
            'reward_amount' => (float) $task['reward_amount'],
            'progress' => (float) $progress,
            'status' => $status,
            'progress_mode' => $task['progress_mode'],
        ];
    }

    return $tasks;
}
    // protected function buildDailyTasks(int $userId): array
    // {
    //     $definitions = $this->dailyTaskDefinitions();
    //     $claimedKeys = DailyTaskClaim::where('user_id', $userId)->pluck('task_key')->toArray();

    //     $todayBetTotal = $this->getTodayBetAmount($userId);
    //     $todayBingoBetTotal = $this->getTodayBetAmount($userId, 'bingo');
    //     $todayBetCount = $this->getTodayBetCount($userId);

    //     $tasks = [];

    //     foreach ($definitions as $task) {
    //         $progress = 0;

    //         if ($task['progress_mode'] === 'count') {
    //             $progress = $todayBetCount;
    //         } else {
    //             if ($task['bet_filter'] === 'bingo') {
    //                 $progress = $todayBingoBetTotal;
    //             } else {
    //                 $progress = $todayBetTotal;
    //             }
    //         }

    //         $isClaimed = in_array($task['key'], $claimedKeys);
    //         $isCompleted = $progress >= $task['required_amount'];

    //         $status = 'unfinished';
    //         if ($isClaimed) {
    //             $status = 'claimed';
    //         } elseif ($isCompleted) {
    //             $status = 'claimable';
    //         }

    //         $tasks[] = [
    //             'key' => $task['key'],
    //             'title' => $task['title'],
    //             'description' => $task['description'],
    //             'group' => $task['group'],
    //             'type' => $task['type'],
    //             'required_amount' => (float) $task['required_amount'],
    //             'reward_amount' => (float) $task['reward_amount'],
    //             'progress' => (float) $progress,
    //             'status' => $status,
    //             'progress_mode' => $task['progress_mode'],
    //         ];
    //     }
    //     return $tasks;
    // }

    public function dailyTasks()
    {
        $user = Auth::user();
        return UserSpaPage::render('user/activity/DailyTasks', [
            'tasks' => $this->buildDailyTasks($user->id),
        ]);
    }

    public function dailyTasksClaim(Request $request)
    {
        $request->validate(['task_key' => ['required', 'string'],]);

        $user = Auth::user();
        $taskKey = $request->task_key;

        $definitions = collect($this->dailyTaskDefinitions());
        $task = $definitions->firstWhere('key', $taskKey);

        if (!$task) {
            toast('Invalid task', 'error');
            return back();
        }

        // $alreadyClaimed = DailyTaskClaim::where('user_id', $user->id)
        //     ->where('task_key', $taskKey)
        //     ->exists();
        $alreadyClaimed = DailyTaskClaim::where('user_id', $user->id)
            ->where('task_key', $taskKey)
            ->whereDate('qualified_date', today())
            ->exists();


        if ($alreadyClaimed) {
            toast('Already claimed', 'error');
            return back();
        }
        $progress = 0;
        if ($task['progress_mode'] === 'count') {
            $progress = $this->getTodayBetCount($user->id);
        } else {
            $progress = $this->getTodayBetAmount($user->id, $task['bet_filter']);
        }

        if ($progress < $task['required_amount']) {
            toast('Task not complete', 'error');
            return back();
        }

        DB::transaction(function () use ($user, $task, $progress) {
            DailyTaskClaim::create([
                'user_id' => $user->id,
                'task_key' => $task['key'],
                'task_title' => $task['title'],
                'task_group' => $task['group'],
                'required_amount' => $task['required_amount'],
                'reward_amount' => $task['reward_amount'],
                'completed_amount' => $progress,
                'qualified_date' => today(),
                'claimed_at' => now(),
            ]);
            $user->increment('balance', $task['reward_amount']);
        });
        toast('Reward received', 'success');
        return redirect()->route('user.activity.daily-tasks');
    }

    public function record()
    {
        $user = Auth::user();

        $records = DailyTaskClaim::where('user_id', $user->id)
            ->latest('claimed_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'task_key' => $item->task_key,
                    'task_title' => $item->task_title,
                    'task_group' => $item->task_group, // daily / weekly
                    'required_amount' => (float) $item->required_amount,
                    'reward_amount' => (float) $item->reward_amount,
                    'completed_amount' => (float) $item->completed_amount,
                    'qualified_date' => optional($item->qualified_date)->format('Y-m-d'),
                    'claimed_at' => optional($item->claimed_at)->format('Y-m-d H:i:s'),
                ];
            });

        return UserSpaPage::render('user/activity/Record', [
            'weeklyRecords' => $records->where('task_group', 'weekly')->values(),
            'dailyRecords' => $records->where('task_group', 'daily')->values(),
        ]);
    }


    


    private const LAUNDRY_RATE_PERCENT = 0.01; 

    public function laundry(Request $request): JsonResponse|Response
    {
        $user = $request->user();

        return UserSpaPage::render('user/activity/Laundry', [
            'summary' => $this->buildLaundrySummary($user->id),
            'recentRecords' => $this->getRebateRecords($user->id, 5),
        ]);
    }

    public function laundryRecord(Request $request): JsonResponse|Response
    {
        return UserSpaPage::render('user/activity/LaundryRecord', [
            'records' => $this->getRebateRecords($request->user()->id),
        ]);
    }

    public function claimLaundryRebate(Request $request)
    {
        $userId = $request->user()->id;

        try {
            $rebateAmount = DB::transaction(function () use ($userId) {
                $now = now();

                $lastClaim = RebateHistory::where('user_id', $userId)
                    ->latest('created_at')
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                $lastClaimAt = $lastClaim?->created_at;

                $totalBetAmount = $this->getClaimableBetTotal($userId, $lastClaimAt, $now);
                if ($totalBetAmount <= 0) {
                    throw new \RuntimeException('NO_NEW_BETS');
                }

                $rebateAmount = round($totalBetAmount * (self::LAUNDRY_RATE_PERCENT / 100), 2);

                if ($rebateAmount <= 0) {
                    throw new \RuntimeException('ZERO_REBATE');
                }

                RebateHistory::create([
                    'user_id' => $userId,
                    'tab' => 'all',
                    'claim_date' => $now->toDateString(),
                    'total_bet_amount' => $totalBetAmount,
                    'rebate_rate_percent' => self::LAUNDRY_RATE_PERCENT,
                    'rebate_amount' => $rebateAmount,
                ]);

                $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
                $user->balance = round((float) $user->balance + $rebateAmount, 2);
                $user->turnover_required = round((float) $user->turnover_required + $rebateAmount, 2);
                $user->save();
                
                
                // create transaction record
                $trx = new Transaction();
                $trx->user_id = $userId;
                $trx->amount = $rebateAmount;
                $trx->charge = 0;
                $trx->post_balance = 0; // will update later
                $trx->trx_type = '+';
                $trx->trx = get_trx();
                $trx->details = 'Betting rebate for bets placed between ' . optional($lastClaimAt)->format('Y-m-d H:i:s') . ' and ' . $now->format('Y-m-d H:i:s');
                $trx->remark = 'betting_rebate';
                $trx->save();
                

                return $rebateAmount;
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'NO_NEW_BETS') {
                toast('নতুন কোনো unclaimed bet পাওয়া যায়নি।', 'error');
                return back();
            }

            if ($e->getMessage() === 'ZERO_REBATE') {
                toast('Rebate amount 0.00 হয়েছে।', 'error');
                return back();
            }

            throw $e;
        }

        toast('One-Click Rebate successful! Balance added: ' . number_format($rebateAmount, 2), 'success');

        return redirect()->route('user.activity.main.laundry');
    }

    private function buildLaundrySummary(int $userId): array
    {
        $lastClaim = RebateHistory::where('user_id', $userId)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $lastClaimAt = $lastClaim?->created_at;
        $currentBetTotal = $this->getClaimableBetTotal($userId, $lastClaimAt, now());

        $totalClaimed = (float) RebateHistory::where('user_id', $userId)->sum('rebate_amount');
        $latestClaimed = (float) ($lastClaim?->rebate_amount ?? 0);

        $claimable = round($currentBetTotal * (self::LAUNDRY_RATE_PERCENT / 100), 2);

        $tabData = [
            'today_total_bet' => round($currentBetTotal, 2),
            'claimable' => round($claimable, 2),
            'rate_percent' => self::LAUNDRY_RATE_PERCENT,
            'last_claim_at' => $lastClaimAt?->format('Y-m-d H:i:s'),
        ];

        return [
            'can_claim_now' => $claimable > 0,
            'today_claimed' => round($latestClaimed, 2),
            'total_claimed' => round($totalClaimed, 2),
            'last_claim_at' => $lastClaimAt?->format('Y-m-d H:i:s'),
            'by_tab' => [
                'all' => $tabData,
                'lottery' => $tabData,
                'casino' => [
                    'today_total_bet' => 0,
                    'claimable' => 0,
                    'rate_percent' => self::LAUNDRY_RATE_PERCENT,
                    'last_claim_at' => $lastClaimAt?->format('Y-m-d H:i:s'),
                ],
                'rummy' => [
                    'today_total_bet' => 0,
                    'claimable' => 0,
                    'rate_percent' => self::LAUNDRY_RATE_PERCENT,
                    'last_claim_at' => $lastClaimAt?->format('Y-m-d H:i:s'),
                ],
                'slots' => [
                    'today_total_bet' => 0,
                    'claimable' => 0,
                    'rate_percent' => self::LAUNDRY_RATE_PERCENT,
                    'last_claim_at' => $lastClaimAt?->format('Y-m-d H:i:s'),
                ],
            ],
        ];
    }
    
    private function getClaimableBetTotal(int $userId, $from = null, $to = null): float
    {
        $query = Bet::where('user_id', $userId);

        if ($from) {
            $query->where('created_at', '>', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return (float) $query
             ->selectRaw('COALESCE(SUM(amount), 0) as total') 
            ->value('total');
    }

private function getRebateRecords(int $userId, ?int $limit = null): array
{
    $query = RebateHistory::where('user_id', $userId)
        ->latest('created_at')
        ->latest('id');

    if ($limit) {
        $query->limit($limit);
    }

    return $query->get()->map(function (RebateHistory $item) {
        return [
            'id' => $item->id,
            'tab' => $item->tab,
            'claim_date' => $item->claim_date->format('Y-m-d'),
            'total_bet_amount' => (float) $item->total_bet_amount,
            'rebate_rate_percent' => (float) $item->rebate_rate_percent,
            'rebate_amount' => (float) $item->rebate_amount,
            'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
        ];
    })->toArray();
}










    public function superJackpot()
    {
        return UserSpaPage::render('user/activity/SuperJackpot');
    }

    public function superJackpotRule()
    {
        return UserSpaPage::render('user/activity/SuperJackpotRule');
    }
    public function superJackpotStar(){
        return UserSpaPage::render('user/activity/SuperJackpotStar');
    }

    // Gift Code 
public function redeemGift()
{
    $user = Auth::user();

    $histories = GiftCodeRedemption::with('giftCode:id,code')
        ->where('user_id', $user->id)
        ->latest('redeemed_at')
        ->get()
        ->map(function ($item) {
            return [
                'id'            => $item->id,
                'code'          => optional($item->giftCode)->code,
                'reward_amount' => $item->reward_amount,
                'redeemed_at'   => optional($item->redeemed_at)->format('Y-m-d H:i:s'),
            ];
        });

    return UserSpaPage::render('user/activity/RedeemGift', [
        'histories' => $histories,
        'toast'     => session('toast'),
    ]);
}

public function redeemGiftSubmit(Request $request)
{
    $request->validate([
        'code' => ['required', 'string', 'max:60'],
    ]);

    $user = Auth::user();
    $inputCode = strtoupper(trim($request->code));

    try {
        DB::transaction(function () use ($user, $inputCode) {
            $giftCode = GiftCode::where('code', $inputCode)
                ->lockForUpdate()
                ->first();

            if (!$giftCode) {
                throw new \Exception('Invalid gift code');
            }

            $alreadyRedeemed = GiftCodeRedemption::where('gift_code_id', $giftCode->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyRedeemed) {
                throw new \Exception('Redeemed already');
            }

            if ($giftCode->is_used == 1) {
                throw new \Exception('Locked code');
            }

            if ($giftCode->used_count >= $giftCode->max_users) {
                throw new \Exception('Limit reached');
            }

            GiftCodeRedemption::create([
                'gift_code_id'  => $giftCode->id,
                'user_id'       => $user->id,
                'reward_amount' => $giftCode->reward_amount,
                'redeemed_at'   => now(),
            ]);

            $user->balance += $giftCode->reward_amount;
            $user->turnover_required += $giftCode->reward_amount;
            $user->save();

            $newUsedCount = $giftCode->used_count + 1;

            $giftCode->update([
                'used_count'   => $newUsedCount,
                'is_used'      => $newUsedCount >= $giftCode->max_users ? 1 : 0,
                'last_used_at' => now(),
            ]);

            Transaction::create([
                'user_id'      => $user->id,
                'amount'       => $giftCode->reward_amount,
                'charge'       => 0,
                'post_balance' => round((float) $user->balance, 2),
                'trx_type'     => '+',
                'trx'          => $inputCode,
                'details'      => $giftCode->remarks ? $giftCode->remarks : 'Gift code redemption reward',
                'remark'       => 'gift_code_redeem',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        });

        toast('Gift code redeemed successfully', 'success', 'check');
        return redirect()->route('user.activity.main.redeem-gift');
    } catch (\Exception $e) {
        toast($e->getMessage(), 'error', 'cross');
        return back();
    }
}

protected function dailySignInLevels(): array
{
    return [
        1 => ['required_deposit' => 1000,   'reward' => 30],
        2 => ['required_deposit' => 2000,   'reward' => 60],
        3 => ['required_deposit' => 5000,   'reward' => 200],
        4 => ['required_deposit' => 10000,  'reward' => 450],
        5 => ['required_deposit' => 50000,  'reward' => 2600],
        6 => ['required_deposit' => 200000, 'reward' => 12000],
        7 => ['required_deposit' => 500000, 'reward' => 31000],
    ];
}

protected function dailyDepositTotal(int $userId, ?string $date = null): float
{
    $date = $date ?: now()->toDateString();

    return (float) DB::table('deposits')
        ->where('user_id', $userId)
        ->where('status', 1)
        ->whereDate('created_at', $date)
        ->sum('amount');
}

/**
 * Last consecutive claimed days before today.
 * Example:
 * yesterday claimed day 1, day before yesterday claimed day 2? না, এটা old logic না।
 * আমরা শুধু consecutive claimed dates count করব।
 * If user missed a day, streak resets automatically.
 */
protected function getCurrentDepositStreak(int $userId): int
{
    $claims = DailySignInClaim::where('user_id', $userId)
        ->orderByDesc('qualified_date')
        ->get(['qualified_date']);

    if ($claims->isEmpty()) {
        return 0;
    }

    $streak = 0;
    $expectedDate = now()->copy()->subDay()->toDateString();

    foreach ($claims as $claim) {
        $claimDate = \Carbon\Carbon::parse($claim->qualified_date)->toDateString();

        if ($claimDate === $expectedDate) {
            $streak++;
            $expectedDate = \Carbon\Carbon::parse($expectedDate)->subDay()->toDateString();
        } else {
            break;
        }
    }

    return min($streak, 7);
}

protected function getTodayDepositRewardData(int $userId): array
{
    $levels = $this->dailySignInLevels();
    $today = now()->toDateString();

    $todayDeposit = $this->dailyDepositTotal($userId, $today);

    $todayClaim = DailySignInClaim::where('user_id', $userId)
        ->whereDate('qualified_date', $today)
        ->first();

    // Yesterday পর্যন্ত streak count
    $currentStreak = $this->getCurrentDepositStreak($userId);

    // আজকের expected level
    $currentLevelNo = min($currentStreak + 1, 7);
    $currentLevel = $levels[$currentLevelNo] ?? end($levels);

    $requiredDeposit = (float) ($currentLevel['required_deposit'] ?? 0);
    $reward = (float) ($currentLevel['reward'] ?? 0);

    $canClaim = !$todayClaim && $todayDeposit >= $requiredDeposit;

    $claimedDays = DailySignInClaim::where('user_id', $userId)
        ->pluck('day_no')
        ->toArray();

    $cards = [];

    foreach ($levels as $dayNo => $level) {
        $status = 'locked';

        if ($todayClaim && (int) $todayClaim->day_no === (int) $dayNo) {
            $status = 'claimed';
        } elseif (!$todayClaim && (int) $dayNo === (int) $currentLevelNo && $todayDeposit >= (float) $level['required_deposit']) {
            $status = 'claimable';
        } elseif ((int) $dayNo < (int) $currentLevelNo) {
            $status = 'passed';
        }

        $cards[] = [
            'day_no' => $dayNo,
            'required_bet' => (float) $level['required_deposit'], // Vue compatibility
            'required_deposit' => (float) $level['required_deposit'],
            'reward' => (float) $level['reward'],
            'status' => $status,
        ];
    }

    return [
        // Vue compatibility
        'streak' => $currentLevelNo,
        'today_bet' => $todayDeposit,
        'current_required' => $requiredDeposit,

        // New meaningful fields
        'today' => $today,
        'today_deposit' => $todayDeposit,
        'already_claimed' => (bool) $todayClaim,
        'can_claim' => $canClaim,
        'current_streak' => $currentStreak,
        'current_level_no' => $currentLevelNo,
        'current_required_deposit' => $requiredDeposit,
        'current_reward' => $reward,
        'matched_level' => $canClaim ? [
            'level_no' => $currentLevelNo,
            'required_deposit' => $requiredDeposit,
            'reward' => $reward,
        ] : null,

        'cards' => $cards,
        'claimed_days' => $claimedDays,
    ];
}

public function dailySignIn()
{
    $user = Auth::user();
    $data = $this->getTodayDepositRewardData($user->id);

    return UserSpaPage::render('user/activity/DailySignIn', [
        'summary' => $data,
    ]);
}

public function dailySignInClaim()
{
    $user = Auth::user();
    $data = $this->getTodayDepositRewardData($user->id);
    $levels = $this->dailySignInLevels();

    if ($data['already_claimed']) {
        toast('Today bonus already claimed', 'error');
        return back();
    }

    if (!$data['can_claim']) {
        toast('Today deposit target not met', 'error');
        return back();
    }

    $levelNo = (int) $data['current_level_no'];
    $requiredDeposit = (float) ($levels[$levelNo]['required_deposit'] ?? 0);
    $reward = (float) ($levels[$levelNo]['reward'] ?? 0);
    $todayDeposit = (float) $data['today_deposit'];

    DB::transaction(function () use ($user, $levelNo, $requiredDeposit, $reward, $todayDeposit) {
        DailySignInClaim::create([
            'user_id' => $user->id,
            'day_no' => $levelNo,
            'required_bet' => $requiredDeposit, // DB compatibility
            'reward_amount' => $reward,
            'bet_total' => $todayDeposit, // DB compatibility
            'qualified_date' => now()->toDateString(),
            'claimed_at' => now(),
        ]);

        $user->increment('balance', $reward);
        $user->refresh();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => $reward,
            'charge' => 0,
            'post_balance' => round((float) $user->balance, 2),
            'trx_type' => '+',
            'trx' => get_trx(),
            'details' => 'Daily deposit reward claimed for day ' . $levelNo,
            'remark' => 'daily_deposit_reward',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    toast('Bonus claimed successfully', 'success');
    return redirect()->route('user.activity.main.daily-sign-in');
}

public function dailySignInRules()
{
    $levels = collect($this->dailySignInLevels())->map(function ($item, $level) {
        return [
            'day_no' => $level, // Vue compatibility
            'level_no' => $level,
            'required_bet' => (float) $item['required_deposit'], // Vue compatibility
            'required_deposit' => (float) $item['required_deposit'],
            'reward' => (float) $item['reward'],
        ];
    })->values();

    return UserSpaPage::render('user/activity/DailySignInRules', [
        'levels' => $levels,
    ]);
}

public function dailySignInRecord()
{
    $user = Auth::user();

    $records = DailySignInClaim::where('user_id', $user->id)
        ->latest('claimed_at')
        ->get()
        ->map(function ($row) {
            return [
                'id' => $row->id,
                'day_no' => $row->day_no,
                'level_no' => $row->day_no,
                'required_bet' => (float) $row->required_bet,
                'required_deposit' => (float) $row->required_bet,
                'reward_amount' => (float) $row->reward_amount,
                'bet_total' => (float) $row->bet_total,
                'deposit_total' => (float) $row->bet_total,
                'qualified_date' => optional($row->qualified_date)->format('Y-m-d'),
                'claimed_at' => optional($row->claimed_at)->format('Y-m-d H:i:s'),
            ];
        });

    return UserSpaPage::render('user/activity/DailySignInRecord', [
        'records' => $records,
    ]);
}
}
