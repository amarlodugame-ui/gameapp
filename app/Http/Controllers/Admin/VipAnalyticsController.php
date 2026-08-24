<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VipLevel;
use App\Models\VipMonthlyClaim;
use App\Models\VipMonthlyState;
use App\Models\VipRewardHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VipAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $currentPeriod = $this->currentClaimPeriod();

        $levels = VipLevel::query()->orderBy('vip', 'asc')->get();

        abort_if($levels->isEmpty(), 404, 'No VIP levels found.');
        $selectedLevel = $levels->firstWhere('id', (int) $request->integer('level_id')) ?? $levels->firstWhere('monthly_reward_enabled', 1) ?? $levels->first();
        $expSub = $this->buildTotalExpSubquery();
        $stateSub = VipMonthlyState::query()
            ->select([
                'user_id',
                'vip_level_id',
                'is_activated',
                'first_claimed_period',
                'last_claimed_period',
                'next_claim_period',
                'monthly_due_exp',
                'outstanding_due_exp',
                'last_bet_checkpoint_at',
                'created_at',
                'updated_at',
            ])
            ->where('vip_level_id', $selectedLevel->id);

        $claimSub = VipMonthlyClaim::query()
            ->select([
                'user_id',
                DB::raw('1 as claimed_this_month'),
                DB::raw('MAX(claimed_at) as last_claimed_this_month_at'),
            ])
            ->where('vip_level_id', $selectedLevel->id)
            ->where('claim_period', $currentPeriod)
            ->groupBy('user_id');

        $perPage = (int) min(max($request->integer('per_page', 20), 10), 100);

        $users = User::query()
            ->select([
                'users.id',
                'users.username',
                'users.name',
                'users.referral_code',
                'users.phone',
                'users.balance',
                'users.created_at',
            ])
            ->leftJoinSub($expSub, 'vip_exp', function ($join) {
                $join->on('vip_exp.user_id', '=', 'users.id');
            })
            ->leftJoinSub($stateSub, 'selected_state', function ($join) {
                $join->on('selected_state.user_id', '=', 'users.id');
            })
            ->leftJoinSub($claimSub, 'selected_claim', function ($join) {
                $join->on('selected_claim.user_id', '=', 'users.id');
            })
            ->addSelect([
                DB::raw('COALESCE(vip_exp.total_exp, 0) as total_exp'),
                DB::raw('selected_state.user_id as state_exists'),
                DB::raw('COALESCE(selected_state.is_activated, 0) as state_is_activated'),
                DB::raw('selected_state.first_claimed_period as state_first_claimed_period'),
                DB::raw('selected_state.last_claimed_period as state_last_claimed_period'),
                DB::raw('selected_state.next_claim_period as state_next_claim_period'),
                DB::raw('COALESCE(selected_state.monthly_due_exp, 0) as state_monthly_due_exp'),
                DB::raw('COALESCE(selected_state.outstanding_due_exp, 0) as state_outstanding_due_exp'),
                DB::raw('selected_state.last_bet_checkpoint_at as state_last_bet_checkpoint_at'),
                DB::raw('COALESCE(selected_claim.claimed_this_month, 0) as claimed_this_month_flag'),
                DB::raw('selected_claim.last_claimed_this_month_at as last_claimed_this_month_at'),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->q);

                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('users.username', 'like', "%{$term}%")
                        ->orWhere('users.name', 'like', "%{$term}%")
                        ->orWhere('users.referral_code', 'like', "%{$term}%")
                        ->orWhere('users.phone', 'like', "%{$term}%");
                });
            })
            ->orderByDesc(DB::raw('COALESCE(vip_exp.total_exp, 0)'))
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($users->items())->map(function ($user) use ($levels, $selectedLevel, $currentPeriod) {
            $totalExp = round((float) ($user->total_exp ?? 0), 2);
            $currentVip = $this->resolveCurrentVipLevel($levels, $totalExp);

            $stateRow = null;
            if (!is_null($user->state_exists)) {
                $stateRow = (object) [
                    'is_activated' => (bool) $user->state_is_activated,
                    'first_claimed_period' => $user->state_first_claimed_period,
                    'last_claimed_period' => $user->state_last_claimed_period,
                    'next_claim_period' => $user->state_next_claim_period,
                    'monthly_due_exp' => (float) $user->state_monthly_due_exp,
                    'outstanding_due_exp' => (float) $user->state_outstanding_due_exp,
                    'last_bet_checkpoint_at' => $user->state_last_bet_checkpoint_at,
                ];
            }

            $snapshot = $this->buildAdminMonthlySnapshot(
                (int) $user->id,
                $selectedLevel,
                $totalExp,
                $stateRow,
                $currentPeriod,
                (bool) $user->claimed_this_month_flag
            );

            return [
                'id' => (int) $user->id,
                'username' => $user->username ?: '-',
                'name' => $user->name ?: '-',
                'referral_code' => $user->referral_code ?: '-',
                'phone' => $user->phone ?: '-',
                'balance' => round((float) $user->balance, 2),
                'joined_at' => optional($user->created_at)->format('Y-m-d H:i'),
                'total_exp' => $totalExp,
                'current_vip' => (int) ($currentVip->vip ?? 0),
                'analysis' => $snapshot,
            ];
        });

        $users->setCollection($rows);

        $cards = $this->buildOverviewCards($selectedLevel, $currentPeriod, $expSub);

        $recentClaims = VipMonthlyClaim::query()
            ->with(['user:id,username,name', 'vipLevel:id,vip'])
            ->where('vip_level_id', $selectedLevel->id)
            ->where('claim_period', $currentPeriod)
            ->latest('claimed_at')
            ->take(10)
            ->get();

        return view('admin.vip.index', [
            'pageTitle' => 'VIP Analytics',
            'levels' => $levels,
            'selectedLevel' => $selectedLevel,
            'cards' => $cards,
            'users' => $users,
            'recentClaims' => $recentClaims,
            'currentPeriod' => $currentPeriod,
        ]);
    }

    public function show(User $user)
    {
        $currentPeriod = $this->currentClaimPeriod();

        $levels = VipLevel::query()
            ->orderBy('vip', 'asc')
            ->get();

        $totalExp = $this->getTotalVipExp($user->id);
        $currentVip = $this->resolveCurrentVipLevel($levels, $totalExp);

        $states = VipMonthlyState::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('vip_level_id');

        $currentMonthClaims = VipMonthlyClaim::query()
            ->where('user_id', $user->id)
            ->where('claim_period', $currentPeriod)
            ->get()
            ->keyBy('vip_level_id');

        $levelUpClaimKeys = VipRewardHistory::query()
            ->where('user_id', $user->id)
            ->where('reward_type', 'level_up')
            ->pluck('claim_key')
            ->flip();

        $levelRows = $levels->map(function ($level) use ($user, $states, $currentMonthClaims, $currentPeriod, $totalExp, $levelUpClaimKeys) {
            $state = $states->get($level->id);

            $stateRow = $state ? (object) [
                'is_activated' => (bool) $state->is_activated,
                'first_claimed_period' => $state->first_claimed_period,
                'last_claimed_period' => $state->last_claimed_period,
                'next_claim_period' => $state->next_claim_period,
                'monthly_due_exp' => (float) $state->monthly_due_exp,
                'outstanding_due_exp' => (float) $state->outstanding_due_exp,
                'last_bet_checkpoint_at' => $state->last_bet_checkpoint_at,
            ] : null;

            $snapshot = $this->buildAdminMonthlySnapshot(
                $user->id,
                $level,
                $totalExp,
                $stateRow,
                $currentPeriod,
                $currentMonthClaims->has($level->id)
            );

            return [
                'level_id' => (int) $level->id,
                'vip' => (int) $level->vip,
                'require_exp' => (float) $level->require_exp,
                'level_up_claimed' => $levelUpClaimKeys->has("level_up:{$user->id}:{$level->id}"),
                'snapshot' => $snapshot,
            ];
        });

        $monthlyClaims = VipMonthlyClaim::query()
            ->with('vipLevel:id,vip')
            ->where('user_id', $user->id)
            ->latest('claimed_at')
            ->take(30)
            ->get();

        return view('admin.vip.show', [
            'pageTitle' => 'VIP User Details',
            'user' => $user,
            'totalExp' => $totalExp,
            'currentVip' => (int) ($currentVip->vip ?? 0),
            'currentPeriod' => $currentPeriod,
            'levelRows' => $levelRows,
            'monthlyClaims' => $monthlyClaims,
        ]);
    }

    protected function buildOverviewCards(VipLevel $selectedLevel, string $currentPeriod, $expSub): array
    {
        $totalUsers = User::query()->count();

        $unlockedUsers = User::query()
            ->leftJoinSub($expSub, 'vip_exp', function ($join) {
                $join->on('vip_exp.user_id', '=', 'users.id');
            })
            ->whereRaw('COALESCE(vip_exp.total_exp, 0) >= ?', [(float) $selectedLevel->require_exp])
            ->count();

        $claimedThisMonth = VipMonthlyClaim::query()
            ->where('vip_level_id', $selectedLevel->id)
            ->where('claim_period', $currentPeriod)
            ->distinct('user_id')
            ->count('user_id');

        $activeStates = VipMonthlyState::query()
            ->where('vip_level_id', $selectedLevel->id)
            ->where('is_activated', 1)
            ->count();

        $outstandingUsers = VipMonthlyState::query()
            ->where('vip_level_id', $selectedLevel->id)
            ->where('outstanding_due_exp', '>', 0)
            ->count();

        return [
            [
                'title' => 'Total Users',
                'value' => number_format($totalUsers),
                'icon' => 'las la-users',
                'class' => 'primary',
            ],
            [
                'title' => "Unlocked VIP{$selectedLevel->vip}",
                'value' => number_format($unlockedUsers),
                'icon' => 'las la-unlock',
                'class' => 'success',
            ],
            [
                'title' => 'Claimed This Month',
                'value' => number_format($claimedThisMonth),
                'icon' => 'las la-gift',
                'class' => 'info',
            ],
            [
                'title' => 'Activated States',
                'value' => number_format($activeStates),
                'icon' => 'las la-layer-group',
                'class' => 'warning',
            ],
            [
                'title' => 'Users With Stored Due',
                'value' => number_format($outstandingUsers),
                'icon' => 'las la-exclamation-circle',
                'class' => 'danger',
            ],
        ];
    }

    protected function buildAdminMonthlySnapshot(
        int $userId,
        VipLevel $level,
        float $totalExp,
        ?object $stateRow,
        string $currentPeriod,
        bool $claimedThisMonth = false
    ): array {
        $requireExp = round((float) $level->require_exp, 2);
        $monthlyEnabled = (bool) $level->monthly_reward_enabled
            && (
                (float) $level->monthly_reward_money > 0
                || (int) $level->monthly_reward_love > 0
            );

        $base = [
            'monthly_enabled' => $monthlyEnabled,
            'unlocked' => $totalExp >= $requireExp,
            'claimed_this_month' => $claimedThisMonth,
            'activated' => (bool) ($stateRow->is_activated ?? false),
            'monthly_due_exp' => $this->getMonthlyDueExp($level),
            'stored_outstanding_due' => round((float) ($stateRow->outstanding_due_exp ?? 0), 2),
            'required_bet_now' => 0,
            'qualified_bet_now' => 0,
            'remaining_due' => 0,
            'effective_progress' => min($totalExp, $requireExp),
            'progress_percent' => $requireExp > 0 ? round((min($totalExp, $requireExp) / $requireExp) * 100, 2) : 0,
            'status_text' => 'N/A',
            'status_class' => 'secondary',
            'available_from_text' => '-',
            'next_claim_period' => $stateRow->next_claim_period ?? $currentPeriod,
            'last_claimed_period' => $stateRow->last_claimed_period ?? null,
            'claimable_now' => false,
        ];

        if (!$monthlyEnabled) {
            $base['status_text'] = 'No monthly reward';
            return $base;
        }

        if ($totalExp < $requireExp) {
            $base['status_text'] = 'Not unlocked';
            $base['status_class'] = 'dark';
            $base['available_from_text'] = 'After unlock';
            $base['effective_progress'] = round(min($totalExp, $requireExp), 2);
            $base['progress_percent'] = $requireExp > 0 ? round((min($totalExp, $requireExp) / $requireExp) * 100, 2) : 0;
            return $base;
        }

        if (!$stateRow || !(bool) $stateRow->is_activated) {
            $base['status_text'] = $claimedThisMonth ? 'Claimed this month' : 'First claim available';
            $base['status_class'] = $claimedThisMonth ? 'success' : 'primary';
            $base['available_from_text'] = $claimedThisMonth
                ? 'Next month 1st day'
                : 'Now';
            $base['claimable_now'] = !$claimedThisMonth;
            $base['effective_progress'] = $requireExp;
            $base['progress_percent'] = 100.00;
            return $base;
        }

        $tempState = new VipMonthlyState();
        $tempState->is_activated = (bool) $stateRow->is_activated;
        $tempState->next_claim_period = $stateRow->next_claim_period;
        $tempState->outstanding_due_exp = (float) $stateRow->outstanding_due_exp;
        $tempState->monthly_due_exp = (float) $stateRow->monthly_due_exp;
        $tempState->last_bet_checkpoint_at = $stateRow->last_bet_checkpoint_at;

        $preview = $this->makeMonthlyPreview($tempState, $level, $userId, $currentPeriod);

        $effectiveProgress = round(max(0, $requireExp - (float) $preview['remainingBet']), 2);
        $progressPercent = $requireExp > 0
            ? round(($effectiveProgress / $requireExp) * 100, 2)
            : 0;

        $base['required_bet_now'] = round((float) $preview['requiredBet'], 2);
        $base['qualified_bet_now'] = round((float) $preview['qualifiedBet'], 2);
        $base['remaining_due'] = round((float) $preview['remainingBet'], 2);
        $base['effective_progress'] = $effectiveProgress;
        $base['progress_percent'] = $progressPercent;
        $base['next_claim_period'] = $preview['nextClaimPeriod'] ?? $base['next_claim_period'];

        if ($claimedThisMonth) {
            $base['status_text'] = 'Claimed this month';
            $base['status_class'] = 'success';
            $base['available_from_text'] = 'Next month 1st day';
            $base['claimable_now'] = false;
            return $base;
        }

        if ($preview['claimable']) {
            $base['status_text'] = 'Can claim now';
            $base['status_class'] = 'success';
            $base['available_from_text'] = 'Now';
            $base['claimable_now'] = true;
            return $base;
        }

        if ($this->comparePeriods($currentPeriod, (string) $base['next_claim_period']) < 0) {
            $base['status_text'] = 'Waiting for next period';
            $base['status_class'] = 'warning';
            $base['available_from_text'] = Carbon::createFromFormat('Y-m', $base['next_claim_period'])->startOfMonth()->format('d M Y');
            $base['claimable_now'] = false;
            return $base;
        }

        $base['status_text'] = 'Need refill';
        $base['status_class'] = 'danger';
        $base['available_from_text'] = 'After refill: ' . number_format($base['remaining_due'], 2);
        $base['claimable_now'] = false;

        return $base;
    }

    protected function buildTotalExpSubquery()
    {
        $betRows = DB::table('bets')
            ->select('user_id', DB::raw('SUM(amount) as total_exp'))
            ->whereIn('status', ['win', 'lose'])
            ->groupBy('user_id');

        $aviatorRows = DB::table('aviator_bets')
            ->select('user_id', DB::raw('SUM(amount) as total_exp'))
            ->where('is_demo', 0)
            ->whereNotNull('settled_at')
            ->whereIn('status', ['won', 'lost', 'cashed_out', 'win', 'lose', 'cashout'])
            ->groupBy('user_id');

        $unionRows = $betRows->unionAll($aviatorRows);

        return DB::query()
            ->fromSub($unionRows, 'exp_rows')
            ->select('user_id', DB::raw('SUM(total_exp) as total_exp'))
            ->groupBy('user_id');
    }

    protected function currentClaimPeriod(): string
    {
        return now()->format('Y-m');
    }

    protected function nextPeriod(string $period): string
    {
        return Carbon::createFromFormat('Y-m', $period)
            ->startOfMonth()
            ->addMonth()
            ->format('Y-m');
    }

    protected function comparePeriods(string $a, string $b): int
    {
        $aDate = Carbon::createFromFormat('Y-m', $a)->startOfMonth();
        $bDate = Carbon::createFromFormat('Y-m', $b)->startOfMonth();

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

    protected function getValidBetSince(int $userId, ?Carbon $from = null): float
    {
        $normalBetQuery = DB::table('bets')
            ->where('user_id', $userId)
            ->whereIn('status', ['win', 'lose']);

        if ($from) {
            $normalBetQuery->where('created_at', '>=', $from);
        }

        $aviatorBetQuery = DB::table('aviator_bets')
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
                    ? Carbon::parse($state->last_bet_checkpoint_at)
                    : null
            ),
            2
        );

        $nextClaimPeriod = $state->next_claim_period ?: $currentPeriod;
        $periodCursor = $nextClaimPeriod;

        $requiredBet = $storedOutstanding;
        $missedPeriods = 0;
        $currentCycleAdded = false;

        while ($this->comparePeriods($periodCursor, $currentPeriod) < 0) {
            $requiredBet += $monthlyDueExp;
            $missedPeriods++;
            $periodCursor = $this->nextPeriod($periodCursor);
        }

        if ($this->comparePeriods($periodCursor, $currentPeriod) === 0) {
            $requiredBet += $monthlyDueExp;
            $currentCycleAdded = true;
        }

        $qualifiedBet = round(min($requiredBet, $rawRefill), 2);
        $remainingBet = round(max(0, $requiredBet - $rawRefill), 2);

        return [
            'currentPeriod' => $currentPeriod,
            'nextClaimPeriod' => $nextClaimPeriod,
            'monthlyDueExp' => $monthlyDueExp,
            'storedOutstanding' => $storedOutstanding,
            'rawRefill' => $rawRefill,
            'requiredBet' => round($requiredBet, 2),
            'qualifiedBet' => $qualifiedBet,
            'remainingBet' => $remainingBet,
            'carryAfterSettlement' => $remainingBet,
            'missedPeriods' => $missedPeriods,
            'currentCycleAdded' => $currentCycleAdded,
            'claimable' => $this->comparePeriods($nextClaimPeriod, $currentPeriod) <= 0
                && $remainingBet <= 0,
        ];
    }
}