<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Bet;
use App\Models\Deposit;
use App\Models\CommissionLog;
use Illuminate\Support\Facades\Schema;

use App\Models\AviatorBet;
use App\Models\Transaction;

use Carbon\Carbon;
use Inertia\Response;
use App\Models\TeamPartnerReword;
use Illuminate\Support\Facades\DB;


class PromotionController extends Controller
{
    
    
    
    public function teamPartner()
{
    $user = auth()->user();

    $baseUrl = rtrim(config('app.url'), '/');
    $invitationLink = $baseUrl . '/user/register?invitationCode=' . $user->referral_code;

    $requiredTurnover = 1000;
    $bonusAmount = 100;

    $completedTurnover = (float) $user->turnover_completed;
    $remainingTurnover = max($requiredTurnover - $completedTurnover, 0);

    $alreadyClaimed = TeamPartnerReword::where('user_id', $user->id)->exists();

    $canClaim = $completedTurnover >= $requiredTurnover && !$alreadyClaimed;

    $rewardHistories = TeamPartnerReword::where('user_id', $user->id)
        ->latest()
        ->get();

    $invitationTotalBonus = TeamPartnerReword::where('user_id', $user->id)
        ->sum('bonus_amount');
$invitationCount = $user->referrals()->count();

return UserSpaPage::render('user/promotion/TeamPartner', [
    'invitationLink' => $invitationLink,
    'invitationCount' => $invitationCount,

    'requiredTurnover' => number_format($requiredTurnover, 2),
    'completedTurnover' => number_format($completedTurnover, 2),
    'remainingTurnover' => number_format($remainingTurnover, 2),
    'bonusAmount' => number_format($bonusAmount, 2),
    'canClaim' => $canClaim,
    'alreadyClaimed' => $alreadyClaimed,
    'invitationTotalBonus' => number_format($invitationTotalBonus, 2),
    'rewardHistories' => $rewardHistories,
]);
}
public function claimTeamPartnerReward()
{
    $user = auth()->user();

    $requiredTurnover = 1000;
    $bonusAmount = 100;

    if ((float) $user->turnover_completed < $requiredTurnover) {
        toast('You need to complete ৳1,000 turnover first.', 'error', 'cross');
        return back();
    }

    $alreadyClaimed = TeamPartnerReword::where('user_id', $user->id)->exists();

    if ($alreadyClaimed) {
        toast('You have already claimed this reward.', 'error', 'cross');
        return back();
    }

    DB::transaction(function () use ($user, $bonusAmount) {
        $user->increment('balance', $bonusAmount);

        $trx = get_trx();

        Transaction::create([
            'user_id' => $user->id,
            'amount' => $bonusAmount,
            'charge' => 0,
            'post_balance' => $user->fresh()->balance,
            'trx_type' => '+',
            'trx' => $trx,
            'details' => 'Turnover reward bonus',
            'remark' => 'team_partner_reward',
        ]);

        TeamPartnerReword::create([
            'user_id' => $user->id,
            'bonus_amount' => $bonusAmount,
            'current_turnover' => $user->turnover_completed,
        ]);
    });

    toast('Reward claimed successfully.', 'success', 'success');
    return back();
}








public function teamPartnerInvitation()
{
    $user = auth()->user();

    $histories = TeamPartnerReword::where('user_id', $user->id)
        ->latest()
        ->paginate(10)
        ->through(function ($item) {
            return [
                'id' => $item->id,
                'bonus_amount' => number_format((float) $item->bonus_amount, 2),
                'current_turnover' => number_format((float) $item->current_turnover, 2),
                'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                'status_label' => 'Claimed',
                'status_class' => 'success',
            ];
        });

    return UserSpaPage::render('user/promotion/TeamPartnerInvitation', [
        'histories' => $histories,
    ]);
}

    
    
    
    
    
    
    
    
    
    
    
    
    
        public function turntable()
        {
            return UserSpaPage::render('user/promotion/Turntable',[
                'userName' => auth()->user()->name,
            ]);
        }
public function index()
{
    $user = auth()->user();
    $stats = promotion_summary($user, 6);

    $baseUrl = config('app.url');

    $invitationLink = $user->referral_code;
    // $invitationLink = $baseUrl . '/user/register?invitationCode=' . $user->referral_code;

    return UserSpaPage::render('user/promotion/Index', [
        'userRefcode' => $user->referral_code,
        'invitationLink' => $invitationLink,
        'stats' => $stats,
    ]);
}
    public function subordinate()
    {
        return UserSpaPage::render('user/promotion/Subordinate');
    }

    
    
public function promotionShare()
{
    $user = auth()->user();
    $baseUrl = rtrim(config('app.url'), '/');
    $invitationLink = $baseUrl . '/user/register?invitationCode=' . $user->referral_code;

    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=164x164&data=' . urlencode($invitationLink);

    $posters = [
        [
            'id' => 'share0',
            'title' => 'Poster 1',
        ],
        [
            'id' => 'share1',
            'title' => 'Poster 2',
        ],
        [
            'id' => 'share2',
            'title' => 'Poster 3',
        ],
    ];

    return UserSpaPage::render('user/promotion/PromotionShare', [
        'invitationLink' => $invitationLink,
        'referralCode' => $user->referral_code,
        'qrImage' => $qrImage,
        'posters' => $posters,
    ]);
}
public function teamReport(Request $request)
{
    $user = auth()->user();

    $search = trim((string) $request->get('search', ''));
    $tier   = (string) $request->get('tier', 'all');

    $timeZone = 'Asia/Dhaka';
    $now = Carbon::now($timeZone);
    $maxReportDate = $now->copy()->subDay()->startOfDay();
    $defaultDate = $maxReportDate->format('Y-m-d');

    $date = trim((string) $request->get('date', $defaultDate));

    try {
        $inputDate = Carbon::createFromFormat('Y-m-d', $date, $timeZone)->startOfDay();
    } catch (\Throwable $e) {
        $inputDate = $maxReportDate->copy();
    }
    if ($inputDate->gt($maxReportDate)) {
        $inputDate = $maxReportDate->copy();
    }

    $date = $inputDate->format('Y-m-d');
    $reportDate = $inputDate->copy();

    $from = $reportDate->copy()->startOfDay();
    $to   = $reportDate->copy()->endOfDay();

    $levels = promotion_referral_levels($user->id, 6);

    if ($tier === 'all') {
        $subordinateIds = collect($levels)->flatten()->unique()->values()->all();
    } else {
        $tierNo = max(1, min(6, (int) $tier));
        $subordinateIds = $levels[$tierNo] ?? [];
    }

    $subordinatesQuery = User::query()->whereIn('id', $subordinateIds);

    if ($search !== '') {
        $subordinatesQuery->where(function ($q) use ($search) {
            $q->where('referral_code', 'like', '%' . $search . '%')
              ->orWhere('username', 'like', '%' . $search . '%')
              ->orWhere('name', 'like', '%' . $search . '%');
        });
    }

    $subordinates = $subordinatesQuery
        ->select('id', 'name', 'username', 'referral_code')
        ->orderBy('id', 'desc')
        ->get();

    $commissionColumn = promotion_commission_amount_column();
    $selectedDateString = $reportDate->format('Y-m-d');

    $subordinates = $subordinates->map(function ($subordinate) use ($user, $from, $to, $levels, $commissionColumn, $selectedDateString) {
        $subordinateId = $subordinate->id;
        $level = promotion_find_user_level($levels, $subordinateId);

        $depositQuery = Deposit::query()
            ->success()
            ->where('user_id', $subordinateId)
            ->whereBetween('created_at', [$from, $to]);

        $betQuery = Bet::query()
            ->where('user_id', $subordinateId)
            ->whereBetween('created_at', [$from, $to]);

        $commission = CommissionLog::query()
            ->where('to_id', $user->id)
            ->where('from_id', $subordinateId)
            ->whereBetween('created_at', [$from, $to])
            ->sum($commissionColumn);

        $firstDeposit = Deposit::query()
            ->success()
            ->where('user_id', $subordinateId)
            ->orderBy('created_at')
            ->first();

        $firstDepositIsOnSelectedDate = $firstDeposit ? $firstDeposit->created_at->between($from, $to) : false;

        return [
            'id' => $subordinate->id,
            'uid' => $subordinate->referral_code,
            'name' => $subordinate->name,
            'username' => $subordinate->username,
            'referral_code' => $subordinate->referral_code,
            'level' => $level,
            'deposit_number' => (clone $depositQuery)->count(),
            'deposit_amount' => (float) (clone $depositQuery)->sum('amount'),
            'number_of_bettors' => (clone $betQuery)->exists() ? 1 : 0,
            'total_bet' => (float) (clone $betQuery)->sum('amount'),
            'first_deposit_people' => $firstDepositIsOnSelectedDate ? 1 : 0,
            'first_deposit_amount' => $firstDepositIsOnSelectedDate ? (float) $firstDeposit->amount : 0,
            'commission' => (float) $commission,
            'time' => $selectedDateString,
        ];
        })->filter(function ($item) use ($search) {
            if ($search !== '') {
                return true;
            }

            return $item['deposit_amount'] > 0
                || $item['total_bet'] > 0
                || $item['commission'] > 0;
        })->sortBy([
        ['deposit_amount', 'desc'],
        ['total_bet', 'desc'],
        ['level', 'asc'],
    ])->values();

    $summary = [
        // 'deposit_number' => $subordinates->sum('deposit_number'),
        'deposit_number' => $subordinates->where('deposit_number', '>', 0)->count(),
        'deposit_amount' => round($subordinates->sum('deposit_amount'), 2),
        'number_of_bettors' => $subordinates->sum('number_of_bettors'),
        'total_bet' => round($subordinates->sum('total_bet'), 2),
        'first_deposit_people' => $subordinates->sum('first_deposit_people'),
        'first_deposit_amount' => round($subordinates->sum('first_deposit_amount'), 2),
        'commission' => round($subordinates->sum('commission'), 2),
    ];

    return UserSpaPage::render('user/promotion/TeamReport', [
        'filters' => [
            'search' => $search,
            'date' => $date,
            'tier' => $tier,
        ],
        'summary' => $summary,
        'subordinates' => $subordinates,
    ]);
}
    
    
    
// public function teamReport(Request $request)
// {
//     $user = auth()->user();

//     $search = trim((string) $request->get('search', ''));
//     $tier   = (string) $request->get('tier', 'all');
//     $date   = (string) $request->get('date', now('Asia/Dhaka')->format('Y-m-d'));

//     $timeZone = 'Asia/Dhaka';
//     $now = Carbon::now($timeZone);

//     try {
//         $inputDate = Carbon::createFromFormat('Y-m-d', $date, $timeZone);
//     } catch (\Throwable $e) {
//         $inputDate = $now->copy();
//         $date = $inputDate->format('Y-m-d');
//     }

//     // future block
//     if ($inputDate->gt($now)) {
//         $inputDate = $now->copy();
//         $date = $inputDate->format('Y-m-d');
//     }

//     // searched date-এর আগের দিনের data
//     $reportDate = $inputDate->copy()->subDay();

//     $from = $reportDate->copy()->startOfDay();
//     $to   = $reportDate->copy()->endOfDay();

//     $levels = promotion_referral_levels($user->id, 6);

//     if ($tier === 'all') {
//         $subordinateIds = collect($levels)->flatten()->unique()->values()->all();
//     } else {
//         $tierNo = max(1, min(6, (int) $tier));
//         $subordinateIds = $levels[$tierNo] ?? [];
//     }

//     $subordinatesQuery = User::query()->whereIn('id', $subordinateIds);

//     if ($search !== '') {
//         $subordinatesQuery->where(function ($q) use ($search) {
//             $q->where('referral_code', 'like', '%' . $search . '%')
//               ->orWhere('username', 'like', '%' . $search . '%')
//               ->orWhere('name', 'like', '%' . $search . '%');
//         });
//     }

//     $subordinates = $subordinatesQuery
//         ->select('id', 'name', 'username', 'referral_code')
//         ->orderBy('id', 'desc')
//         ->get();

//     $commissionColumn = promotion_commission_amount_column();
//     $selectedDateString = $reportDate->format('Y-m-d');

//     $subordinates = $subordinates->map(function ($subordinate) use ($user, $from, $to, $levels, $commissionColumn, $selectedDateString) {
//         $subordinateId = $subordinate->id;
//         $level = promotion_find_user_level($levels, $subordinateId);

//         $depositQuery = Deposit::query()
//             ->success()
//             ->where('user_id', $subordinateId)
//             ->whereBetween('created_at', [$from, $to]);

//         $betQuery = Bet::query()
//             ->where('user_id', $subordinateId)
//             ->whereBetween('created_at', [$from, $to]);

//         $commission = CommissionLog::query()
//             ->where('to_id', $user->id)
//             ->where('from_id', $subordinateId)
//             ->whereBetween('created_at', [$from, $to])
//             ->sum($commissionColumn);

//         $firstDeposit = Deposit::query()
//             ->success()
//             ->where('user_id', $subordinateId)
//             ->orderBy('created_at')
//             ->first();

//         $firstDepositIsOnSelectedDate = $firstDeposit
//             ? $firstDeposit->created_at->between($from, $to)
//             : false;

//         return [
//             'id' => $subordinate->id,
//             'uid' => $subordinate->referral_code,
//             'name' => $subordinate->name,
//             'username' => $subordinate->username,
//             'referral_code' => $subordinate->referral_code,
//             'level' => $level,
//             'deposit_number' => (clone $depositQuery)->count(),
//             'deposit_amount' => (float) (clone $depositQuery)->sum('amount'),
//             'number_of_bettors' => (clone $betQuery)->exists() ? 1 : 0,
//             'total_bet' => (float) (clone $betQuery)->sum('amount'),
//             'first_deposit_people' => $firstDepositIsOnSelectedDate ? 1 : 0,
//             'first_deposit_amount' => $firstDepositIsOnSelectedDate ? (float) $firstDeposit->amount : 0,
//             'commission' => (float) $commission,
//             'time' => $selectedDateString,
//         ];
//     })->sortBy([
//         ['total_bet', 'desc'],
//         ['level', 'asc'],
//     ])->values();

//     $summary = [
//         'deposit_number' => $subordinates->sum('deposit_number'),
//         'deposit_amount' => round($subordinates->sum('deposit_amount'), 2),
//         'number_of_bettors' => $subordinates->sum('number_of_bettors'),
//         'total_bet' => round($subordinates->sum('total_bet'), 2),
//         'first_deposit_people' => $subordinates->sum('first_deposit_people'),
//         'first_deposit_amount' => round($subordinates->sum('first_deposit_amount'), 2),
//         'commission' => round($subordinates->sum('commission'), 2),
//     ];

//     return Inertia::render('user/promotion/TeamReport', [
//         'filters' => [
//             'search' => $search,
//             'date' => $date, // picker-এ searched date থাকবে
//             'tier' => $tier,
//         ],
//         'summary' => $summary,
//         'subordinates' => $subordinates,
//     ]);
// }
public function myCommission(Request $request): JsonResponse|Response
{
    $user = auth()->user();
    $timeZone = 'Asia/Dhaka';

    // আজ পর্যন্ত selectable
    $maxSelectableDate = Carbon::now($timeZone)->toDateString();

    $inputDate = trim((string) $request->get('date', ''));

    if ($inputDate !== '') {
        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $inputDate, $timeZone);
        } catch (\Throwable $e) {
            $selectedDate = Carbon::createFromFormat('Y-m-d', $maxSelectableDate, $timeZone);
        }
    } else {
        $selectedDate = Carbon::createFromFormat('Y-m-d', $maxSelectableDate, $timeZone);
    }

    // future date block
    $maxDateCarbon = Carbon::createFromFormat('Y-m-d', $maxSelectableDate, $timeZone);
    if ($selectedDate->gt($maxDateCarbon)) {
        $selectedDate = $maxDateCarbon->copy();
    }

    // searched date-এর আগের দিনের report
    $reportDate = $selectedDate->copy()->subDay();

    $dayStart = $reportDate->copy()->startOfDay();
    $dayEnd   = $reportDate->copy()->endOfDay();

    // settlement searched date-এ হবে
    $settlementStart = $selectedDate->copy()->startOfDay();
    $settlementEnd   = $selectedDate->copy()->endOfDay();

    // 6 level team ids
    $teamIds = promotion_team_user_ids($user, 6, true);

    // Wingo bettors
    $wingoBettorIds = Bet::query()
        ->whereIn('user_id', $teamIds)
        ->whereBetween('created_at', [$dayStart, $dayEnd])
        ->distinct()
        ->pluck('user_id');

    // Aviator bettors
    $aviatorBettorIds = AviatorBet::query()
        ->whereIn('user_id', $teamIds)
        ->whereBetween('created_at', [$dayStart, $dayEnd])
        ->distinct()
        ->pluck('user_id');

    $numberOfBettors = $wingoBettorIds
        ->merge($aviatorBettorIds)
        ->unique()
        ->count();

    $wingoBetAmount = (float) Bet::query()
        ->whereIn('user_id', $teamIds)
        ->whereBetween('created_at', [$dayStart, $dayEnd])
        ->sum('amount');

    $aviatorBetAmount = (float) AviatorBet::query()
        ->whereIn('user_id', $teamIds)
        ->whereBetween('created_at', [$dayStart, $dayEnd])
        ->sum('amount');

    $betAmount = $wingoBetAmount + $aviatorBetAmount;

    // selected date-এ settlement transaction
    $settlementTransaction = Transaction::query()
        ->where('user_id', $user->id)
        ->where('remark', 'daily_referral_settlement')
        ->whereBetween('created_at', [$settlementStart, $settlementEnd])
        ->latest('created_at')
        ->first();

    // fallback: reportDate-এর commission logs
   $commissionPayout = (float) promotion_commission_between($user, $dayStart, $dayEnd);

    $settlementTime = $settlementTransaction
        ? Carbon::parse($settlementTransaction->created_at)
            ->setTimezone($timeZone)
            ->format('Y-m-d H:i:s')
        : null;

    $levels = promotion_referral_levels($user->id, 6);
    $levelWiseTeam = collect(range(1, 6))->map(function ($level) use ($levels) {
        return [
            'level' => $level,
            'count' => count($levels[$level] ?? []),
        ];
    })->values()->all();

    return UserSpaPage::render('user/promotion/MyCommission', [
        'filters' => [
            // picker-এ searched date-টাই থাকবে
            'date' => $selectedDate->format('Y-m-d'),
        ],

        'maxSelectableDate' => $maxSelectableDate,

        'summary' => [
            'statusText'        => $settlementTransaction ? 'Settlement successful' : 'Pending settlement',
            'settlementTime'    => $settlementTime,
            'numberOfBettors'   => $numberOfBettors,
            'betAmount'         => number_format($betAmount, 2, '.', ''),
            'commissionPayout'  => number_format($commissionPayout, 2, '.', ''),

            // data যেই দিনের দেখানো হচ্ছে
            'reportDate'        => $reportDate->format('Y-m-d'),

            // user যেই date search করেছে
            'date'      => $selectedDate->format('Y-m-d'),
        ],

        'levelWiseTeam' => $levelWiseTeam,
    ]);
}



public function subordinateData(Request $request)
{
    $user = auth()->user();
    $range = $request->get('range', 'today');

    $users = promotion_users_at_level($user, 1);

    $filtered = $users->filter(function ($sub) use ($range) {
        if (! $sub->created_at) {
            return false;
        }

        if ($range === 'today') {
            return $sub->created_at->isToday();
        }

        if ($range === 'yesterday') {
            return $sub->created_at->isYesterday();
        }

        if ($range === 'month') {
            return $sub->created_at->format('Y-m') === now()->format('Y-m');
        }

        return false;
    })->sortByDesc('created_at')->values();

    return response()->json([
        'data' => $filtered->map(function ($sub) {
            return [
                'phone' => $sub->phone ?? $sub->mobile ?? '',
                'uid' => $sub->referral_code,
                'joined_at' => optional($sub->created_at)->format('Y-m-d H:i:s'),
            ];
        })->all(),
    ]);
}
    public function promotionRule()
    {
        return UserSpaPage::render('user/promotion/PromotionRule');
    }
    public function rebateRatio()
    {
        return UserSpaPage::render('user/promotion/RebateRatio');
    }
public function support()
{
    $data = [
        'title' => 'Online Service',
        'backUrl' => route('user.wingo.game'),

        // 'banner' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/123906719-287113-工单黄2.png',

'services' => [
    // [
    //     'title' => 'Deposit Not Received',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124103211-287118-11.png',
    //     'type' => 'image',
    //     'link' => 'https://t.me/+75aEr6Nlcg9lYjhk',
    // ],
    // [
    //     'title' => 'Withdrawal problem',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124120691-287119-08.png',
    //     'type' => 'image',
    //     'link' => 'https://t.me/+V7kJEz_eOztkZTg0',
    // ],
    // [
    //     'title' => 'Change ID Login Password',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124150663-287122-01.png',
    //     'type' => 'image',
    //     'link' => 'https://t.me/+uWOU6s2mjps1ZmZk',
    // ],
    // [
    //     'title' => 'Modify E-Wallet Information',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124204060-287123-06.png',
    //     'type' => 'image',
    //     'link' => 'https://t.me/+1oj3PnotrBo2Yjk0',
    // ],
    // [
    //     'title' => 'Add USDT Address',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124241163-287125-05.png',
    //     'type' => 'image',
    //     'link' => 'https://t.me/+HQo3WnSoN4oxYjA0',
    // ],
    // [
    //     'title' => 'Check DKWIN Official Website',
    //     'icon' => 'https://worktracking-imgs.oss-ap-southeast-1.aliyuncs.com/Prod/System/124338477-287129-16.png',
    //     'type' => 'image',
    //     'link' => '#',
    // ],
    [
        'title' => 'Online service',
        'icon' => 'icon-custom_service',
        'type' => 'svg',
        'link' => 'https://direct.lc.chat/19615340/',
    ],
],

        // 'tipsTitle' => 'Kind tips',
        // 'tips' => [
        //     '1. Please select the corresponding question and submit it for review. After successful submission, the customer service specialist will handle it for you immediately. Please wait patiently.',
        //     '2. After submitting for review, you can use [Progress Query] to view the review results of the work order you submitted.',
        // ],

        // 'progressButtonText' => 'Progress Query',
        // 'progressButtonColor' => 'rgb(242, 202, 49)',
    ];

    return UserSpaPage::render('user/promotion/Support', $data);
}
}
