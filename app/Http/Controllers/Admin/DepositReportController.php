<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DepositReportController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Deposit Reports';

        $status = $request->input('status', '');
        $userId = trim((string) $request->input('user_id', ''));
        $trxId = trim((string) $request->input('trx_id', ''));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $baseQuery = Deposit::query()
            ->with('user:id,name,username,email,phone,role')
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'demo');
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', (int) $status);
            })
            ->when($userId !== '', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($trxId !== '', function ($query) use ($trxId) {
                $query->where('trx_id', 'like', '%' . $trxId . '%');
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        $deposits = (clone $baseQuery)
            ->latest('id')
            ->paginate($perPage)
            ->appends($request->all());

        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $realUserDeposits = Deposit::query()
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'demo');
            });

        $stats = [
            'today_total' => (clone $realUserDeposits)
                ->whereDate('created_at', $today)
                ->where('status', 1)
                ->sum('amount'),

            'yesterday_total' => (clone $realUserDeposits)
                ->whereDate('created_at', $yesterday)
                ->where('status', 1)
                ->sum('amount'),

            'this_month_total' => (clone $realUserDeposits)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->where('status', 1)
                ->sum('amount'),

            'pending_count' => (clone $realUserDeposits)->where('status', 0)->count(),
            'success_count' => (clone $realUserDeposits)->where('status', 1)->count(),
            'awaiting_count' => (clone $realUserDeposits)->where('status', 2)->count(),
            'rejected_count' => (clone $realUserDeposits)->where('status', 3)->count(),

            'pending_total' => (clone $realUserDeposits)->where('status', 0)->sum('amount'),
            'success_total' => (clone $realUserDeposits)->where('status', 1)->sum('amount'),
            'awaiting_total' => (clone $realUserDeposits)->where('status', 2)->sum('amount'),
            'rejected_total' => (clone $realUserDeposits)->where('status', 3)->sum('amount'),
        ];

        return view('admin.deposits.report', compact(
            'pageTitle',
            'deposits',
            'stats',
            'status'
        ));
    }
}