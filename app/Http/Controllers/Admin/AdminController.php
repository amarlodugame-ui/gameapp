<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Deposit;
use App\Models\GameRound;
use App\Models\GiftCode;
use App\Models\GiftCodeRedemption;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
public function dashboard()
{
    $pageTitle = 'Dashboard';

    $stats = Cache::remember('admin.dashboard.stats.v1', now()->addSeconds(30), function () {
        $startOfToday = now()->startOfDay();
        $endOfToday   = now()->endOfDay();

        $users = User::query()
            ->where('role', '!=', 'demo')
            ->selectRaw("
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as real_users,
                0 as demo_users,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as blocked_users,
                COALESCE(SUM(balance), 0) as total_balance,
                COALESCE(SUM(game_balance), 0) as total_game_balance,
                COALESCE(SUM(strong_box_balance), 0) as total_strong_box_balance,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_new_users
            ", [$startOfToday, $endOfToday])
            ->first();

        $deposits = Deposit::query()
            ->whereHas('user', function ($q) {
                $q->where('role', '!=', 'demo');
            })
            ->selectRaw("
                COUNT(*) as total_deposits,

                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success_count,
                COALESCE(SUM(CASE WHEN status = 1 THEN amount ELSE 0 END), 0) as success_amount,

                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status = 0 THEN amount ELSE 0 END), 0) as pending_amount,

                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as awaiting_count,
                COALESCE(SUM(CASE WHEN status = 2 THEN amount ELSE 0 END), 0) as awaiting_amount,

                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as rejected_count,
                COALESCE(SUM(CASE WHEN status = 3 THEN amount ELSE 0 END), 0) as rejected_amount,

                COALESCE(SUM(CASE WHEN status = 1 AND created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as today_success_amount
            ", [$startOfToday, $endOfToday])
            ->first();

        $withdraws = Withdraw::query()
            ->whereHas('user', function ($q) {
                $q->where('role', '!=', 'demo');
            })
            ->selectRaw("
                COUNT(*) as total_withdraws,

                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count,
                COALESCE(SUM(CASE WHEN status = 0 THEN amount ELSE 0 END), 0) as pending_amount,

                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as processing_count,
                COALESCE(SUM(CASE WHEN status = 1 THEN amount ELSE 0 END), 0) as processing_amount,

                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as completed_count,
                COALESCE(SUM(CASE WHEN status = 2 THEN amount ELSE 0 END), 0) as completed_amount,

                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as rejected_count,
                COALESCE(SUM(CASE WHEN status = 3 THEN amount ELSE 0 END), 0) as rejected_amount,

                COALESCE(SUM(CASE WHEN status = 2 AND created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as today_completed_amount
            ", [$startOfToday, $endOfToday])
            ->first();

        $giftCodes = GiftCode::query()
            ->selectRaw("
                COUNT(*) as total_codes,
                SUM(CASE WHEN is_used = 1 THEN 1 ELSE 0 END) as fully_used_codes,
                SUM(CASE WHEN is_used = 0 THEN 1 ELSE 0 END) as available_codes,
                COALESCE(SUM(reward_amount), 0) as total_reward_budget,
                COALESCE(SUM(used_count), 0) as total_used_count
            ")
            ->first();

        $giftRedemptions = GiftCodeRedemption::query()
            ->selectRaw("
                COUNT(*) as total_redemptions,
                COALESCE(SUM(reward_amount), 0) as total_redeemed_amount,
                COALESCE(SUM(CASE WHEN redeemed_at BETWEEN ? AND ? THEN reward_amount ELSE 0 END), 0) as today_redeemed_amount
            ", [$startOfToday, $endOfToday])
            ->first();

        $bets = Bet::query()
            ->whereHas('user', function ($q) {
                $q->where('role', '!=', 'demo');
            })
            ->selectRaw("
                COUNT(*) as total_bets,
                COALESCE(SUM(amount), 0) as total_bet_amount,
                COALESCE(SUM(payout), 0) as total_payout_amount,
                (COALESCE(SUM(amount), 0) - COALESCE(SUM(payout), 0)) as gross_revenue,

                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_bets,
                COALESCE(SUM(CASE WHEN created_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as today_bet_amount
            ", [$startOfToday, $endOfToday, $startOfToday, $endOfToday])
            ->first();

        $rounds = GameRound::query()
            ->selectRaw("
                COUNT(*) as total_rounds,
                SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running_rounds,
                SUM(CASE WHEN status = 'ended' THEN 1 ELSE 0 END) as ended_rounds,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_rounds
            ", [$startOfToday, $endOfToday])
            ->first();

        $walletTotal = (float) $users->total_balance
            + (float) $users->total_game_balance
            + (float) $users->total_strong_box_balance;

        return [
            'users' => $users,
            'deposits' => $deposits,
            'withdraws' => $withdraws,
            'giftCodes' => $giftCodes,
            'giftRedemptions' => $giftRedemptions,
            'bets' => $bets,
            'rounds' => $rounds,
            'walletTotal' => $walletTotal,
        ];
    });

    return view('admin.dashboard', compact('pageTitle', 'stats'));
}
}