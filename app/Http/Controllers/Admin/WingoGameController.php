<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SettleWingoRound;
use App\Models\Bet;
use App\Models\GameControl;
use App\Models\GameRound;
use App\Support\WingoRules;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WingoGameController extends Controller
{
    private int $defaultQueueBuffer = 10;
    private int $maxFutureResultsPerRequest = 500;

    public function index(Request $request)
    {
        $pageTitle = "Game Rounds Summary";

        $roundBetSummary = Bet::query()
            ->join('users', 'users.id', '=', 'bets.user_id')
            ->select('bets.round_id')
            ->selectRaw("
                COUNT(bets.id) as total_bets,
                COUNT(DISTINCT bets.user_id) as total_users,
                COUNT(DISTINCT CASE WHEN users.role = 'user' THEN bets.user_id END) as total_real_users,
                COUNT(DISTINCT CASE WHEN users.role = 'demo' THEN bets.user_id END) as total_demo_users,
                COALESCE(SUM(CASE WHEN users.role = 'user' THEN bets.amount ELSE 0 END), 0) as total_real_bet,
                COALESCE(SUM(CASE WHEN users.role = 'demo' THEN bets.amount ELSE 0 END), 0) as total_demo_bet,
                COALESCE(SUM(CASE WHEN users.role = 'user' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as total_real_payout,
                COALESCE(SUM(CASE WHEN users.role = 'demo' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as total_demo_payout
            ")
            ->groupBy('bets.round_id');

        $roundsQuery = GameRound::query()
            ->joinSub($roundBetSummary, 'agg', function ($join) {
                $join->on('agg.round_id', '=', 'game_rounds.id');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where('game_rounds.period_id', 'like', "%{$search}%");
            })
            ->when($request->filled('game_type'), function ($q) use ($request) {
                $q->where('game_rounds.game_type', $request->game_type);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('game_rounds.status', $request->status);
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('game_rounds.start_time', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('game_rounds.start_time', '<=', $request->date_to);
            });

        $overview = (clone $roundsQuery)
            ->selectRaw("
                COUNT(game_rounds.id) as total_rounds,
                COALESCE(SUM(agg.total_real_bet), 0) as total_real_bet,
                COALESCE(SUM(agg.total_demo_bet), 0) as total_demo_bet,
                COALESCE(SUM(agg.total_real_payout), 0) as total_real_payout,
                COALESCE(SUM(agg.total_demo_payout), 0) as total_demo_payout,
                COALESCE(SUM(agg.total_real_users), 0) as total_real_users,
                COALESCE(SUM(agg.total_demo_users), 0) as total_demo_users
            ")
            ->first();

        $rounds = (clone $roundsQuery)
            ->select([
                'game_rounds.id',
                'game_rounds.period_id',
                'game_rounds.game_type',
                'game_rounds.start_time',
                'game_rounds.end_time',
                'game_rounds.result_number',
                'game_rounds.result_color',
                'game_rounds.result_bigsmall',
                'game_rounds.status',
                'agg.total_bets',
                'agg.total_users',
                'agg.total_real_users',
                'agg.total_demo_users',
                'agg.total_real_bet',
                'agg.total_demo_bet',
                'agg.total_real_payout',
                'agg.total_demo_payout',
            ])
            ->selectRaw('(agg.total_real_bet - agg.total_real_payout) as real_profit')
            ->orderByDesc('game_rounds.id')
            ->simplePaginate(20)
            ->withQueryString();

        return view('admin.wingo.index', compact('pageTitle', 'overview', 'rounds'));
    }

    public function show(Request $request, $id)
    {
        $round = GameRound::findOrFail($id);
        $pageTitle = "Round Details - {$round->period_id}";

        $baseBetQuery = Bet::query()
            ->join('users', 'users.id', '=', 'bets.user_id')
            ->where('bets.round_id', $round->id);

        $stats = (clone $baseBetQuery)
            ->selectRaw("
                COUNT(bets.id) as total_bets,
                COUNT(DISTINCT bets.user_id) as total_users,
                COUNT(DISTINCT CASE WHEN users.role = 'user' THEN bets.user_id END) as total_real_users,
                COUNT(DISTINCT CASE WHEN users.role = 'demo' THEN bets.user_id END) as total_demo_users,
                COALESCE(SUM(CASE WHEN users.role = 'user' THEN bets.amount ELSE 0 END), 0) as total_real_bet,
                COALESCE(SUM(CASE WHEN users.role = 'demo' THEN bets.amount ELSE 0 END), 0) as total_demo_bet,
                COALESCE(SUM(CASE WHEN users.role = 'user' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as total_real_payout,
                COALESCE(SUM(CASE WHEN users.role = 'demo' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as total_demo_payout
            ")
            ->first();

        $breakdowns = (clone $baseBetQuery)
            ->select('bets.bet_type', 'bets.bet_value')
            ->selectRaw("
                COUNT(bets.id) as total_bets,
                COALESCE(SUM(CASE WHEN users.role = 'user' THEN bets.amount ELSE 0 END), 0) as real_amount,
                COALESCE(SUM(CASE WHEN users.role = 'demo' THEN bets.amount ELSE 0 END), 0) as demo_amount,
                COALESCE(SUM(CASE WHEN users.role = 'user' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as real_payout,
                COALESCE(SUM(CASE WHEN users.role = 'demo' AND bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as demo_payout
            ")
            ->groupBy('bets.bet_type', 'bets.bet_value')
            ->orderBy('bets.bet_type')
            ->orderBy('bets.bet_value')
            ->get();

        $userSummary = (clone $baseBetQuery)
            ->select('users.id', 'users.name', 'users.username', 'users.role')
            ->selectRaw("
                COUNT(bets.id) as total_bets,
                COALESCE(SUM(bets.amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN bets.status = 'win' THEN bets.payout ELSE 0 END), 0) as total_payout
            ")
            ->groupBy('users.id', 'users.name', 'users.username', 'users.role')
            ->orderByDesc('total_amount')
            ->get();

        $bets = (clone $baseBetQuery)
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->where('users.role', $request->role);
            })
            ->when($request->filled('bet_type'), function ($q) use ($request) {
                $q->where('bets.bet_type', $request->bet_type);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('bets.status', $request->status);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);

                $q->where(function ($sub) use ($search) {
                    $sub->where('bets.bet_value', 'like', "%{$search}%")
                        ->orWhere('users.name', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $sub->orWhere('users.id', $search);
                    }
                });
            })
            ->select([
                'bets.id',
                'bets.user_id',
                'bets.round_id',
                'bets.bet_type',
                'bets.bet_value',
                'bets.amount',
                'bets.multiplier',
                'bets.qty',
                'bets.payout',
                'bets.status',
                'bets.game_type',
                'bets.created_at',
                'users.name as user_name',
                'users.username as user_username',
                'users.role as user_role',
            ])
            ->orderByDesc('bets.id')
            ->simplePaginate(50)
            ->withQueryString();

        $betTypes = $breakdowns->pluck('bet_type')->unique()->values();

        return view('admin.wingo.show', compact(
            'pageTitle',
            'round',
            'stats',
            'breakdowns',
            'userSummary',
            'bets',
            'betTypes'
        ));
    }

    public function details($type)
    {
        $type = strtolower($type);

        if (!in_array($type, ['30s', '1m', '3m', '5m'], true)) {
            abort(404);
        }

        $pageTitle = 'WinGo ' . strtoupper($type) . ' Rounds';

        return view('admin.wingo.rounds', compact('type', 'pageTitle'));
    }

public function liveData(Request $request, $type)
{
    $type = strtolower($type);

    if (!in_array($type, ['30s', '1m', '3m', '5m'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid game type'
        ], 422);
    }

    $queueCount = max(1, min((int) $request->query('queue_count', 20), 500));

    // IMPORTANT:
    // এখানে কোনও ensure / create / promote / dispatch হবে না
    $round = GameRound::query()
        ->where('game_type', $type)
        ->where('status', 'running')
        ->orderBy('start_time')
        ->first();

    $control = GameControl::firstOrCreate(
        ['game_type' => $type],
        [
            'mode' => 'random',
            'manual_number' => null,
            'profit_margin' => 0
        ]
    );

    $upcomingRounds = GameRound::query()
        ->where('game_type', $type)
        ->where('status', 'queued')
        ->orderBy('start_time')
        ->limit($queueCount)
        ->get()
        ->map(fn ($r) => [
            'id' => $r->id,
            'period_id' => $r->period_id,
            'start_time' => optional($r->start_time)->timestamp,
            'end_time' => optional($r->end_time)->timestamp,
            'status' => $r->status,
            'preset_number' => $r->result_number,
        ])
        ->values();

    $nextPrediction = $upcomingRounds->first()['preset_number'] ?? null;

    $payoutColumnExists = Schema::hasColumn('bets', 'payout_amount');

    $summary = [];
    $totals = [
        'total_bet_amount' => 0,
        'demo_total_bet'   => 0,
        'real_total_bet'   => 0,
        'total_payout'     => 0,
    ];
    $liveBets = collect();

    // running round থাকলে তবেই stats/totals/live bets দেখাবে
    if ($round) {
        $summaryQuery = Bet::leftJoin('users', 'bets.user_id', '=', 'users.id')
            ->where('bets.round_id', $round->id)
            ->where('bets.bet_type', 'number')
            ->groupBy('bets.bet_value')
            ->select(
                'bets.bet_value',
                DB::raw('SUM(bets.amount * COALESCE(bets.qty, 1)) as total_amount'),
                DB::raw("SUM(CASE WHEN users.role = 'demo' THEN bets.amount * COALESCE(bets.qty, 1) ELSE 0 END) as demo_total_amount"),
                DB::raw("SUM(CASE WHEN users.role = 'user' THEN bets.amount * COALESCE(bets.qty, 1) ELSE 0 END) as real_total_amount"),
                DB::raw('COUNT(*) as total_bets'),
                DB::raw('COUNT(DISTINCT bets.user_id) as total_users')
            );

        if ($payoutColumnExists) {
            $summaryQuery->addSelect(DB::raw('SUM(COALESCE(bets.payout_amount, 0)) as total_payout'));
        } else {
            $summaryQuery->addSelect(DB::raw('0 as total_payout'));
        }

        $summary = $summaryQuery->get()->mapWithKeys(function ($row) {
            return [
                (string) $row->bet_value => [
                    'total_amount'      => (float) $row->total_amount,
                    'demo_total_amount' => (float) $row->demo_total_amount,
                    'real_total_amount' => (float) $row->real_total_amount,
                    'total_payout'      => (float) $row->total_payout,
                    'total_bets'        => (int) $row->total_bets,
                    'total_users'       => (int) $row->total_users,
                ]
            ];
        });

        $totalsQuery = Bet::leftJoin('users', 'bets.user_id', '=', 'users.id')
            ->where('bets.round_id', $round->id)
            ->select(
                DB::raw('SUM(bets.amount * COALESCE(bets.qty, 1)) as total_bet_amount'),
                DB::raw("SUM(CASE WHEN users.role = 'demo' THEN bets.amount * COALESCE(bets.qty, 1) ELSE 0 END) as demo_total_bet"),
                DB::raw("SUM(CASE WHEN users.role = 'user' THEN bets.amount * COALESCE(bets.qty, 1) ELSE 0 END) as real_total_bet")
            );

        if ($payoutColumnExists) {
            $totalsQuery->addSelect(DB::raw('SUM(COALESCE(bets.payout_amount, 0)) as total_payout'));
        } else {
            $totalsQuery->addSelect(DB::raw('0 as total_payout'));
        }

        $totalsRow = $totalsQuery->first();

        $totals = [
            'total_bet_amount' => (float) ($totalsRow->total_bet_amount ?? 0),
            'demo_total_bet'   => (float) ($totalsRow->demo_total_bet ?? 0),
            'real_total_bet'   => (float) ($totalsRow->real_total_bet ?? 0),
            'total_payout'     => (float) ($totalsRow->total_payout ?? 0),
        ];

        $liveBets = Bet::with('user:id,username,phone,game_balance,role')
            ->where('round_id', $round->id)
            ->latest('id')
            ->limit(50)
            ->get();
    }

    return response()->json([
        'success' => true,
        'round' => $round ? [
            'id' => $round->id,
            'period_id' => $round->period_id,
            'game_type' => $round->game_type,
            'start_time' => optional($round->start_time)->timestamp,
            'end_time' => optional($round->end_time)->timestamp,
            'server_time' => now()->timestamp,
            'status' => $round->status,
        ] : null,
        'control' => [
            'id' => $control->id,
            'game_type' => $control->game_type,
            'mode' => $control->mode,
            'manual_number' => $control->manual_number,
            'profit_margin' => $control->profit_margin,
        ],
        'next_prediction' => $nextPrediction,
        'upcoming_rounds' => $upcomingRounds,
        'totals' => $totals,
        'summary' => $summary,
        'live_bets' => $liveBets,
    ]);
}

    /**
     * Set only NEXT queued round result.
     */
public function setManual(Request $request)
{
    $request->validate([
        'game_type' => ['required', 'in:30s,1m,3m,5m'],
        'number' => ['required', 'integer', 'min:0', 'max:9'],
    ]);

    $round = DB::transaction(function () use ($request) {
        $type = $request->game_type;
        $number = (int) $request->number;

        // আগে running round খুঁজি
        $running = GameRound::query()
            ->where('game_type', $type)
            ->where('status', 'running')
            ->orderBy('start_time')
            ->lockForUpdate()
            ->first();

        // running round থাকলে next queued round-এ set করবো
        if ($running) {
            $queued = GameRound::query()
                ->where('game_type', $type)
                ->where('status', 'queued')
                ->orderBy('start_time')
                ->lockForUpdate()
                ->first();

            if (!$queued) {
                $queued = $this->createRoundAfter($type, $running, 'queued');
            }

            $queued->result_number = $number;
            $queued->save();

            return $queued;
        }

        // running না থাকলে নতুন running round create করে সেটাতেই set করবো
        $running = $this->createNewRound($type);
        $running->result_number = $number;
        $running->save();

        $delaySeconds = max(1, now()->diffInSeconds($running->end_time, false));
        SettleWingoRound::dispatch($running->id)->delay(now()->addSeconds($delaySeconds));

        return $running;
    });

    return response()->json([
        'success' => true,
        'message' => 'Manual result set successfully',
        'round' => [
            'id' => $round->id,
            'period_id' => $round->period_id,
            'preset_number' => $round->result_number,
            'status' => $round->status,
        ],
    ]);
}
    /**
     * Clear only NEXT queued round result.
     */
    public function unsetManual(Request $request)
    {
        $request->validate([
            'game_type' => ['required', 'in:30s,1m,3m,5m'],
        ]);

        $round = DB::transaction(function () use ($request) {
            $round = GameRound::query()
                ->where('game_type', $request->game_type)
                ->where('status', 'queued')
                ->orderBy('start_time')
                ->lockForUpdate()
                ->first();

            if (!$round) {
                abort(422, 'No queued round available');
            }

            $round->result_number = null;
            $round->save();

            return $round;
        });

        return response()->json([
            'success' => true,
            'message' => 'Next round prediction cleared successfully',
            'round' => [
                'id' => $round->id,
                'period_id' => $round->period_id,
                'preset_number' => null,
            ],
        ]);
    }

    /**
     * Apply serial future results on queued rounds.
     * Example: 2,3,4,5 -> next queued rounds serially
     */
    public function setFutureResults(Request $request)
    {
        $request->validate([
            'game_type' => ['required', 'in:30s,1m,3m,5m'],
            'results' => ['required', 'array', 'min:1', 'max:' . $this->maxFutureResultsPerRequest],
            'results.*' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $updated = DB::transaction(function () use ($request) {
            $need = count($request->results);

            $this->ensurePipeline($request->game_type, $need);

            $rounds = GameRound::query()
                ->where('game_type', $request->game_type)
                ->where('status', 'queued')
                ->orderBy('start_time')
                ->lockForUpdate()
                ->limit($need)
                ->get();

            if ($rounds->count() < $need) {
                abort(422, 'Not enough queued rounds available');
            }

            $updated = [];

            foreach ($rounds as $i => $round) {
                $round->result_number = (int) $request->results[$i];
                $round->save();

                $updated[] = [
                    'id' => $round->id,
                    'period_id' => $round->period_id,
                    'preset_number' => $round->result_number,
                ];
            }

            return $updated;
        });

        return response()->json([
            'success' => true,
            'message' => 'Future results applied successfully',
            'count' => count($updated),
            'rounds' => $updated,
        ]);
    }

    /**
     * Set specific queued round result from table row action.
     */
    public function setRoundResult(Request $request)
    {
        $request->validate([
            'round_id' => ['required', 'integer', 'exists:game_rounds,id'],
            'number' => ['required', 'integer', 'min:0', 'max:9'],
        ]);

        $round = DB::transaction(function () use ($request) {
            $round = GameRound::query()
                ->whereKey($request->round_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($round->status !== 'queued') {
                abort(422, 'Only queued rounds can be updated');
            }

            $round->result_number = (int) $request->number;
            $round->save();

            return $round;
        });

        return response()->json([
            'success' => true,
            'message' => 'Future round result set successfully',
            'round' => [
                'id' => $round->id,
                'period_id' => $round->period_id,
                'preset_number' => $round->result_number,
            ],
        ]);
    }

    /**
     * Clear specific queued round result from table row action.
     */
    public function clearFutureRound(Request $request)
    {
        $request->validate([
            'round_id' => ['required', 'integer', 'exists:game_rounds,id'],
        ]);

        $round = DB::transaction(function () use ($request) {
            $round = GameRound::query()
                ->whereKey($request->round_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($round->status !== 'queued') {
                abort(422, 'Only queued rounds can be cleared');
            }

            $round->result_number = null;
            $round->save();

            return $round;
        });

        return response()->json([
            'success' => true,
            'message' => 'Future round cleared successfully',
            'round' => [
                'id' => $round->id,
                'period_id' => $round->period_id,
                'preset_number' => null,
            ],
        ]);
    }

    /**
     * Optional: clear all queued preset results.
     */
    public function clearAllFutureResults(Request $request)
    {
        $request->validate([
            'game_type' => ['required', 'in:30s,1m,3m,5m'],
        ]);

        $count = DB::transaction(function () use ($request) {
            $rounds = GameRound::query()
                ->where('game_type', $request->game_type)
                ->where('status', 'queued')
                ->whereNotNull('result_number')
                ->lockForUpdate()
                ->get();

            foreach ($rounds as $round) {
                $round->result_number = null;
                $round->save();
            }

            return $rounds->count();
        });

        return response()->json([
            'success' => true,
            'message' => 'All future predictions cleared successfully',
            'count' => $count,
        ]);
    }

    /**
     * Ensure:
     * - exactly one running round exists (or first queued promoted to running)
     * - enough queued rounds exist after it
     */
    private function ensurePipeline(string $type, ?int $targetQueueCount = null): GameRound
    {
        $targetQueueCount = ($targetQueueCount && $targetQueueCount > 0)
            ? $targetQueueCount
            : $this->defaultQueueBuffer;

        return DB::transaction(function () use ($type, $targetQueueCount) {
            $running = GameRound::query()
                ->where('game_type', $type)
                ->where('status', 'running')
                ->orderBy('start_time')
                ->lockForUpdate()
                ->first();

            if (!$running) {
                $queued = GameRound::query()
                    ->where('game_type', $type)
                    ->where('status', 'queued')
                    ->orderBy('start_time')
                    ->lockForUpdate()
                    ->first();

                if ($queued) {
                    $queued->status = 'running';
                    $queued->save();
                    $running = $queued;
                } else {
                    $running = $this->createNewRound($type);
                }

                $delaySeconds = max(1, now()->diffInSeconds($running->end_time, false));
                SettleWingoRound::dispatch($running->id)->delay(now()->addSeconds($delaySeconds));
            }

            $queuedCount = GameRound::query()
                ->where('game_type', $type)
                ->where('status', 'queued')
                ->count();

            $last = GameRound::query()
                ->where('game_type', $type)
                ->orderByDesc('end_time')
                ->lockForUpdate()
                ->first();

            while ($queuedCount < $targetQueueCount) {
                $last = $this->createRoundAfter($type, $last, 'queued');
                $queuedCount++;
            }

            return $running;
        });
    }

private function ensureRunningRoundOnly(string $type): GameRound
{
    return DB::transaction(function () use ($type) {
        $running = GameRound::query()
            ->where('game_type', $type)
            ->where('status', 'running')
            ->orderBy('start_time')
            ->lockForUpdate()
            ->first();

        if ($running) {
            return $running;
        }

        $queued = GameRound::query()
            ->where('game_type', $type)
            ->where('status', 'queued')
            ->orderBy('start_time')
            ->lockForUpdate()
            ->first();

        if ($queued) {
            $queued->status = 'running';
            $queued->save();

            $delaySeconds = max(1, now()->diffInSeconds($queued->end_time, false));
            SettleWingoRound::dispatch($queued->id)->delay(now()->addSeconds($delaySeconds));

            return $queued;
        }

        $running = $this->createNewRound($type);

        $delaySeconds = max(1, now()->diffInSeconds($running->end_time, false));
        SettleWingoRound::dispatch($running->id)->delay(now()->addSeconds($delaySeconds));

        return $running;
    });
}
    private function createNewRound(string $type): GameRound
    {
        $start = now();

        return $this->createRoundAt($type, $start, 'running');
    }

    private function createRoundAfter(string $type, ?GameRound $prev, string $status): GameRound
    {
        $start = $prev?->end_time?->copy() ?? now();

        if ($start->lessThan(now())) {
            $start = now();
        }

        return $this->createRoundAt($type, $start, $status);
    }

    private function createRoundAt(string $type, CarbonInterface $start, string $status): GameRound
    {
        $end = $start->copy()->addSeconds(WingoRules::durationSeconds($type));

        return GameRound::create([
            'period_id' => $this->buildNextPeriodId($type, $start),
            'game_type' => $type,
            'start_time' => $start,
            'end_time' => $end,
            'result_number' => null,
            'result_color' => null,
            'result_bigsmall' => null,
            'status' => $status,
        ]);
    }

    /**
     * Format:
     * 30s => 2026033101000242
     * 1m  => 2026033102000121
     * 3m  => 2026033103000039
     * 5m  => 2026033104000023
     */
    private function buildNextPeriodId(string $gameType, CarbonInterface $start): string
    {
        $date = $start->format('Ymd');
        $typeCode = $this->gameTypeCode($gameType);

        $latest = GameRound::query()
            ->where('game_type', $gameType)
            ->where('period_id', 'like', $date . $typeCode . '%')
            ->orderByDesc('period_id')
            ->lockForUpdate()
            ->first();

        $prevSeq = 0;

        if ($latest && preg_match('/^\d{8}\d{2}(\d{6})$/', (string) $latest->period_id, $m)) {
            $prevSeq = (int) $m[1];
        }

        $nextSeq = $prevSeq + 1;

        return sprintf('%s%s%06d', $date, $typeCode, $nextSeq);
    }

    private function gameTypeCode(string $gameType): string
    {
        return match ($gameType) {
            '30s' => '01',
            '1m'  => '02',
            '3m'  => '03',
            '5m'  => '04',
            default => '99',
        };
    }
}