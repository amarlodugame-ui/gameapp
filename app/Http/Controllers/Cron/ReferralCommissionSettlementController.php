<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\CommissionLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralCommissionSettlementController extends Controller
{
    public function __invoke(): JsonResponse
    {

        // Prevent concurrent execution
        $lock = Cache::lock('daily-referral-commission-settlement', 3600);

        if (! $lock->get()) {
            return response()->json([
                'status'  => 'locked',
                'message' => 'Settlement already running.',
            ], 423);
        }

        try {
            // Today start এর আগের সব pending logs settle করবে
            // এতে কোনোদিন cron miss হলেও পরে catch-up হয়ে যাবে
            $cutoff = now()->startOfDay();

            $lastUserId      = 0;
            $chunkSize       = 500;

            $processedUsers  = 0;
            $processedLogs   = 0;
            $totalSettled    = 0;

            do {
                // pending users in chunks
                $userIds = CommissionLog::query()
                    ->where('is_given', 0)
                    ->whereNotNull('to_id')
                    ->where('created_at', '<', $cutoff)
                    ->where('to_id', '>', $lastUserId)
                    ->groupBy('to_id')
                    ->orderBy('to_id')
                    ->limit($chunkSize)
                    ->pluck('to_id');

                if ($userIds->isEmpty()) {
                    break;
                }

                DB::transaction(function () use (
                    $userIds,
                    $cutoff,
                    &$processedUsers,
                    &$processedLogs,
                    &$totalSettled
                ) {
                    // Sum pending commission user-wise
                    $totals = CommissionLog::query()
                        ->select('to_id', DB::raw('SUM(amount) as total_amount'))
                        ->where('is_given', 0)
                        ->whereNotNull('to_id')
                        ->where('created_at', '<', $cutoff)
                        ->whereIn('to_id', $userIds)
                        ->groupBy('to_id')
                        ->get()
                        ->keyBy('to_id');

                    if ($totals->isEmpty()) {
                        return;
                    }

                    // Lock user rows
                    $users = User::query()
                        ->whereIn('id', $totals->keys())
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    if ($users->isEmpty()) {
                        return;
                    }

                    $now = now();
                    $transactionRows = [];
                    $validUserIds = [];

                    foreach ($totals as $toId => $row) {
                        $user = $users->get($toId);

                        if (! $user) {
                            continue;
                        }

                        $amount = (float) $row->total_amount;

                        if ($amount <= 0) {
                            continue;
                        }

                        $oldBalance = (float) $user->balance;
                        $newBalance = $oldBalance + $amount;

                        // balance update
                        $user->balance = $newBalance;
                        $user->save();

                        $validUserIds[] = $user->id;

                        $transactionRows[] = [
                            'user_id'       => $user->id,
                            'amount'        => $amount,
                            'post_balance'  => $newBalance,
                            'charge'        => 0,
                            'trx_type'      => '+',
                            'details'       => 'Daily referral commission',
                            'remark'        => 'daily_referral_settlement',
                            'trx'           => strtoupper(Str::random(12)),
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ];

                        $processedUsers++;
                        $totalSettled += $amount;
                    }

                    if (! empty($transactionRows)) {
                        Transaction::insert($transactionRows);
                    }

                    if (! empty($validUserIds)) {
                        $updated = CommissionLog::query()
                            ->where('is_given', 0)
                            ->where('created_at', '<', $cutoff)
                            ->whereIn('to_id', $validUserIds)
                            ->update([
                                'is_given'   => 1,
                                'updated_at' => $now,
                            ]);

                        $processedLogs += $updated;
                    }
                }, 3);

                $lastUserId = $userIds->max();
            } while (true);

            return response()->json([
                'status'          => 'success',
                'message'         => 'Daily referral commission settled successfully.',
                'cutoff'          => $cutoff->toDateTimeString(),
                'processed_users' => $processedUsers,
                'processed_logs'  => $processedLogs,
                'total_settled'   => number_format($totalSettled, 8, '.', ''),
            ]);
        } finally {
            optional($lock)->release();
        }
    }
}