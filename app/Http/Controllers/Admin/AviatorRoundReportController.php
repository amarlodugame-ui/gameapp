<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AviatorBet;
use App\Models\AviatorRound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AviatorRoundReportController extends Controller
{
public function index(Request $request)
{
    $pageTitle = 'Aviator Game Rounds Summary';

    $perPage = (int) $request->input('per_page', 20);
    $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

    $includeDemo = (int) $request->input('include_demo', 1);
    $roundNo = trim((string) $request->input('round_no', ''));
    $dateFrom = $request->input('date_from');
    $dateTo = $request->input('date_to');

    $role = $request->input('role');     // real | demo | null
    $slot = $request->input('slot');     // 1 | 2 | null
    $status = $request->input('status'); // queued | pending | cashed_out | lost | cancelled | null

    $betsBase = AviatorBet::query()
        ->when($role === 'demo', fn ($q) => $q->where('is_demo', true))
        ->when($role === 'real', fn ($q) => $q->where('is_demo', false))
        ->when(!$role && !$includeDemo, fn ($q) => $q->where('is_demo', false))
        ->when($slot, fn ($q) => $q->where('slot', (int) $slot))
        ->when($status, fn ($q) => $q->where('status', $status));

    $roundIdsQuery = (clone $betsBase)
        ->select('round_id')
        ->distinct();

    $rounds = AviatorRound::query()
        ->whereIn('id', $roundIdsQuery)
        ->when($roundNo !== '', function ($query) use ($roundNo) {
            $query->where('round_no', $roundNo);
        })
        ->when($dateFrom, function ($query) use ($dateFrom) {
            $query->whereDate('betting_starts_at', '>=', $dateFrom);
        })
        ->when($dateTo, function ($query) use ($dateTo) {
            $query->whereDate('betting_starts_at', '<=', $dateTo);
        })
        ->select('aviator_rounds.*')
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COALESCE(SUM(amount), 0)')
                ->whereColumn('round_id', 'aviator_rounds.id'),
            'total_bet'
        )
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COALESCE(SUM(cashout_amount), 0)')
                ->whereColumn('round_id', 'aviator_rounds.id'),
            'total_win_payout'
        )
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COUNT(DISTINCT user_id)')
                ->whereColumn('round_id', 'aviator_rounds.id'),
            'total_users'
        )
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COUNT(*)')
                ->whereColumn('round_id', 'aviator_rounds.id')
                ->where('status', 'cashed_out'),
            'cashed_out_count'
        )
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COUNT(*)')
                ->whereColumn('round_id', 'aviator_rounds.id')
                ->where('status', 'lost'),
            'lost_count'
        )
        ->selectSub(
            (clone $betsBase)
                ->selectRaw('COUNT(*)')
                ->whereColumn('round_id', 'aviator_rounds.id')
                ->where('status', 'cancelled'),
            'cancelled_count'
        )
        ->orderByDesc('round_no')
        ->paginate($perPage)
        ->appends($request->all());

    return view('admin.aviator_reports.index', compact(
        'pageTitle',
        'rounds',
        'includeDemo',
        'role',
        'slot',
        'status'
    ));
}
    public function show(Request $request, AviatorRound $round)
    {
        $pageTitle = 'Aviator Round Details';
        $includeDemo = (int) $request->input('include_demo', 1);
        $status = trim((string) $request->input('status', ''));
        $slot = trim((string) $request->input('slot', ''));
        $perPage = (int) $request->input('per_page', 30);
        $perPage = in_array($perPage, [10, 30, 50, 100], true) ? $perPage : 30;

        $betsQuery = AviatorBet::query()
            ->with('user:id,name,username,email,role')
            ->where('round_id', $round->id)
            ->when(!$includeDemo, function ($query) {
                $query->where('is_demo', false);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($slot !== '', function ($query) use ($slot) {
                $query->where('slot', $slot);
            });

        $bets = (clone $betsQuery)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());

        $summary = [
            'total_bet' => (clone $betsQuery)->sum('amount'),
            'total_payout' => (clone $betsQuery)->sum('cashout_amount'),
            'total_users' => (clone $betsQuery)->distinct('user_id')->count('user_id'),
            'total_bets' => (clone $betsQuery)->count(),
            'cashed_out_count' => (clone $betsQuery)->where('status', 'cashed_out')->count(),
            'lost_count' => (clone $betsQuery)->where('status', 'lost')->count(),
            'cancelled_count' => (clone $betsQuery)->where('status', 'cancelled')->count(),
            'pending_count' => (clone $betsQuery)->where('status', 'pending')->count(),
            'queued_count' => (clone $betsQuery)->where('status', 'queued')->count(),
            'auto_cashout_count' => (clone $betsQuery)->where('auto_cashout_enabled', true)->count(),
            'demo_bets_count' => (clone $betsQuery)->where('is_demo', true)->count(),
            'real_bets_count' => (clone $betsQuery)->where('is_demo', false)->count(),
            'slot_1_count' => (clone $betsQuery)->where('slot', 1)->count(),
            'slot_2_count' => (clone $betsQuery)->where('slot', 2)->count(),
        ];

        $summary['profit'] = (float) $summary['total_bet'] - (float) $summary['total_payout'];

        return view('admin.aviator_reports.show', compact(
            'pageTitle',
            'round',
            'bets',
            'summary',
            'includeDemo'
        ));
    }
}