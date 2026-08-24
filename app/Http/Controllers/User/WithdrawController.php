<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Models\Withdraw;
use App\Models\WithdrawWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Deposit;


class WithdrawController extends Controller
{
public function index(Request $request)
{
    $user = auth()->user();

    $wallets = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->where('type', '!=', 'USDT')
        ->latest('id')
        ->get();

    $usdtWallet = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('type', 'USDT')
        ->where('is_active', true)
        ->latest('id')
        ->first();

    $selectedWalletId = $request->integer('wallet_id');
    $selectedWallet = $wallets->firstWhere('id', $selectedWalletId) ?: $wallets->first();

    $recentWithdraws = Withdraw::query()->with('wallet')->where('user_id', $user->id)->latest('id')->take(5)->get()->map(fn ($withdraw) => $this->transformWithdraw($withdraw));

    $turnoverRequired = $user->turnover_required;
    $balance = $user->balance;

    $withdrawTodayCount = Withdraw::query()->where('user_id', $user->id)->whereDate('created_at', now()->toDateString())->count();

    $remainingTimes = max(0, 3 - $withdrawTodayCount);
    $perWithdrawTime = 2;

    $minWithdrawAmount = 200;
    $maxWithdrawAmount = 25000;
    $minUSDTWithdrawAmount = 10;
    $maxUSDTWithdrawAmount = 10000;

    $usdtRate = 125;

    return UserSpaPage::render('user/withdraw/Index', [
        'balance' => $balance,
        'selectedWallet' => $selectedWallet ? $this->transformWallet($selectedWallet) : null,
        'usdtWallet' => $usdtWallet ? $this->transformWallet($usdtWallet) : null,
        'walletCount' => $wallets->count(),
        'recentWithdraws' => $recentWithdraws,
        'settings' => [
            'min_amount' => $minWithdrawAmount,
            'max_amount' => $maxWithdrawAmount,
            'min_usdt_amount' => $minUSDTWithdrawAmount,
            'max_usdt_amount' => $maxUSDTWithdrawAmount,
            'withdraw_time' => $perWithdrawTime . ' hours',
            'remaining_times' => $remainingTimes,
            'need_bet_amount' => $turnoverRequired,
            'charge' => 0,
            'usdt_rate' => $usdtRate,
        ],
    ]);
}
public function store(Request $request)
{
    $request->validate([
        'withdraw_wallet_id' => ['required', 'integer'],
        'amount' => ['required', 'numeric', 'min:200'],
        'password' => ['required', 'string'],
    ]);

    try {
        DB::beginTransaction();
        $authUser = auth()->user();
        $user = User::query()->where('id', $authUser->id)->lockForUpdate()->first();
        
    // $hasApprovedDeposit = Deposit::where('user_id', $authUser->id)->where('status', 1)->exists();
    // if (!$hasApprovedDeposit) {
    //         toast('প্রথমবার উইথড্র করার আগে অবশ্যই আপনাকে ডিপোজিট করতে হবে।', 'error', 'cross');
    //         return redirect()->back();
    // }
        
                
        if (!$user) {
            DB::rollBack();
            toast('User not found.', 'error', 'cross');
            return redirect()->back();
        }

        $amount = round((float) $request->amount, 2);
        $usdtRate = 125;
        $charge = 0.00;
        $netAmount = round($amount - $charge, 2);

        if (!Hash::check($request->password, $user->password)) {
            DB::rollBack();
            toast('Incorrect login password.', 'error', 'cross');
            return redirect()->back();
        }

        $wallet = WithdrawWallet::query()->where('id', $request->integer('withdraw_wallet_id'))->where('user_id', $user->id)->where('is_active', true)->lockForUpdate()->first();

        if (!$wallet) {
            DB::rollBack();
            toast('Selected wallet is invalid or inactive.', 'error', 'cross');
            return redirect()->back();
        }

        if ($user->turnover_required > 0) {
            DB::rollBack();
            toast('Complete required turnover', 'error', 'cross');
            return redirect()->back();
        }

        $isUsdt = strtoupper($wallet->type) === 'USDT';
        $payoutCurrencyCode = $isUsdt ? 'USDT' : 'BDT';

        if ($isUsdt) {
            if ($amount < ($usdtRate * 10)) {
                DB::rollBack();
                toast('Minimum withdraw is 10 USDT.', 'error', 'cross');
                return redirect()->back();
            }

            if ($amount > ($usdtRate * 10000)) {
                DB::rollBack();
                toast('Maximum withdraw is 10000 USDT.', 'error', 'cross');
                return redirect()->back();
            }

            $payoutAmount = round($netAmount / $usdtRate, 2);
        } else {
            if ($amount < 200) {
                DB::rollBack();
                toast('Minimum withdraw amount is 200.', 'error', 'cross');
                return redirect()->back();
            }

            if ($amount > 25000) {
                DB::rollBack();
                toast('Maximum withdraw amount is 25000.', 'error', 'cross');
                return redirect()->back();
            }

            $payoutAmount = round($netAmount, 2);
        }

        if ($amount > (float) $user->balance) {
            DB::rollBack();
            toast('Insufficient balance.', 'error', 'cross');
            return redirect()->back();
        }

        $withdrawLimit = Withdraw::query()->where('user_id', $user->id)->whereDate('created_at', now()->toDateString())->lockForUpdate()->count();

        if ($withdrawLimit >= 3) {
            DB::rollBack();
            toast('You have reached the maximum number of withdrawals for today. Please try again tomorrow.', 'error', 'cross');
            return redirect()->back();
        }

        $trxId = 'WD' . now()->format('YmdHis') . strtoupper(Str::random(6));
        $currentBalance = round((float) $user->balance, 2);
        $newBalance = round($currentBalance - $amount, 2);

        // Demo user flow
        if ($user->isDemo()) {
            $user->balance = $newBalance;
            $user->save();

            Withdraw::create([
                'user_id' => $user->id,
                'withdraw_wallet_id' => $wallet->id,
                'payout_currency_code' => $payoutCurrencyCode,
                'amount' => $amount,
                'number' => $wallet->account_number,
                'charge' => $charge,
                'net_amount' => $netAmount,
                'response_id' => null,
                'last_error' => null,
                'information' => [
                    'wallet_type' => $wallet->type,
                    'wallet_name' => $wallet->full_name,
                    'amount_bdt' => $amount,
                    'net_amount_bdt' => $netAmount,
                    'usdt_rate' => $isUsdt ? $usdtRate : null,
                    'payout_amount' => $payoutAmount,
                    'payout_currency_code' => $payoutCurrencyCode,
                    'is_demo' => true,
                ],
                'meta_field' => null,
                'feedback' => null,
                'trx_id' => $trxId,
                'status' => Withdraw::STATUS_COMPLETED,
            ]);

            $trx = new Transaction();
            $trx->user_id = $user->id;
            $trx->amount = $amount;
            $trx->post_balance = $newBalance;
            $trx->charge = $charge;
            $trx->trx_type = '-';
            $trx->details = $isUsdt
                ? 'Demo USDT withdraw completed: ' . number_format($payoutAmount, 2, '.', '') . ' USDT at rate ' . number_format($usdtRate, 2, '.', '') . ' BDT'
                : 'Demo withdraw completed: ' . number_format($netAmount, 2, '.', '') . ' BDT';
            $trx->remark = 'withdraw_request';
            $trx->trx = $trxId;
            $trx->save();

            createUserNotification( $user->id, 'APPLY FOR WITHDRAWAL', 'Your demo withdrawal request has been completed.', 'withdraw_request');
            DB::commit();

            toast('Demo withdrawal completed successfully.', 'success', 'success');
            return redirect()->back();
        }

        // Real user flow
        $user->balance = $newBalance;
        $user->save();

        Withdraw::create([
            'user_id' => $user->id,
            'withdraw_wallet_id' => $wallet->id,
            'payout_currency_code' => $payoutCurrencyCode,
            'amount' => $amount,
            'number' => $wallet->account_number,
            'charge' => $charge,
            'net_amount' => $netAmount,
            'response_id' => null,
            'last_error' => null,
            'information' => [
                'wallet_type' => $wallet->type,
                'wallet_name' => $wallet->full_name,
                'amount_bdt' => $amount,
                'net_amount_bdt' => $netAmount,
                'usdt_rate' => $isUsdt ? $usdtRate : null,
                'payout_amount' => $payoutAmount,
                'payout_currency_code' => $payoutCurrencyCode,
                'is_demo' => false,
            ],
            'meta_field' => null,
            'feedback' => null,
            'trx_id' => $trxId,
            'status' => Withdraw::STATUS_PENDING,
        ]);

        $trx = new Transaction();
        $trx->user_id = $user->id;
        $trx->amount = $amount;
        $trx->post_balance = $newBalance;
        $trx->charge = $charge;
        $trx->trx_type = '-';
        $trx->details = $isUsdt ? 'USDT withdraw requested: ' . number_format($payoutAmount, 2, '.', '') . ' USDT at rate ' . number_format($usdtRate, 2, '.', '') . ' BDT' : 'Withdraw requested: ' . number_format($netAmount, 2, '.', '') . ' BDT';
        $trx->remark = 'withdraw_request';
        $trx->trx = $trxId;
        $trx->save();

        createUserNotification( $user->id, 'APPLY FOR WITHDRAWAL', 'Your withdrawal request has been sent.', 'withdraw_request');
        DB::commit();

        toast('Withdraw request submitted successfully.', 'success', 'success');
        return redirect()->route('user.wallet.WithdrawHistory');
    } catch (\Throwable $e) {
        DB::rollBack();
        report($e);

        toast('Something went wrong. Please try again.' . $e->getMessage(), 'error', 'cross');
        return redirect()->back();
    }
}

public function WithdrawHistory(Request $request)
{
    $user = auth()->user();

    $status = $request->string('status')->toString();
    $method = $request->string('method')->toString();

    $query = Withdraw::query()
        ->with('wallet')
        ->where('user_id', $user->id);

    if ($status !== '' && $status !== 'all') {
        $statusMap = [
            'pending' => Withdraw::STATUS_PENDING,
            'processing' => Withdraw::STATUS_PROCESSING,
            'completed' => Withdraw::STATUS_COMPLETED,
            'rejected' => Withdraw::STATUS_REJECTED,
        ];

        if (array_key_exists($status, $statusMap)) {
            $query->where('status', $statusMap[$status]);
        }
    }

    if ($method !== '' && $method !== 'all') {
        if ($method === 'usdt') {
            $query->whereHas('wallet', function ($q) {
                $q->where('type', 'USDT');
            });
        }

        if ($method === 'ewallet') {
            $query->whereHas('wallet', function ($q) {
                $q->where('type', '!=', 'USDT');
            });
        }
    }

    $withdraws = $query
        ->latest('id')
        ->paginate(15)
        ->through(fn ($withdraw) => $this->transformWithdraw($withdraw))
        ->withQueryString();

    return UserSpaPage::render('user/withdraw/WithdrawHistory', [
        'filters' => [
            'status' => $status ?: 'all',
            'method' => $method ?: 'all',
        ],
        'statuses' => [
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'pending', 'label' => 'Pending'],
            ['key' => 'processing', 'label' => 'Processing'],
            ['key' => 'completed', 'label' => 'Completed'],
            ['key' => 'rejected', 'label' => 'Rejected'],
        ],
        'withdraws' => $withdraws,
    ]);
}


public function welletList()
{
    $user = auth()->user();

    $wallets = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('type', '!=', 'USDT')
        ->latest('id')
        ->get()
        ->map(fn ($wallet) => $this->transformWallet($wallet));

    $usdtWallet = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('type', 'USDT')
        ->where('is_active', true)
        ->latest('id')
        ->first();

    return UserSpaPage::render('user/withdraw/WelletList', [
        'wallets' => $wallets,
        'usdtWallet' => $usdtWallet ? $this->transformWallet($usdtWallet) : null,
    ]);
}

public function selectWellet(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'wallet_id' => ['required', 'integer'],
    ]);

    $wallet = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('type', '!=', 'USDT')
        ->findOrFail($request->wallet_id);

    $walletCount = WithdrawWallet::query()
        ->where('user_id', $user->id)
        ->where('type', '!=', 'USDT')
        ->count();

    if ($walletCount <= 1) {
        toast('At least one e-wallet must remain active.', 'error', 'cross');
        return redirect()->back();
    }

    DB::transaction(function () use ($user, $wallet) {
        WithdrawWallet::query()
            ->where('user_id', $user->id)
            ->where('type', '!=', 'USDT')
            ->where('id', '!=', $wallet->id)
            ->update([
                'is_active' => false,
            ]);

        $wallet->update([
            'is_active' => true,
        ]);
    });

    toast('Wallet selected successfully.', 'success');

    return redirect()->route('user.wallet.Withdraw', [
        'wallet_id' => $wallet->id,
    ]);
}
public function addWellet()
{
    return UserSpaPage::render('user/withdraw/AddWellet', [
        'walletOptions' => ['BKASH', 'NAGAD'],
    ]);
}


public function storeWellet(Request $request)
{
    $user = auth()->user();

    try {
        $request->validate([
            'type' => ['required', 'string', 'max:30', 'in:BKASH,NAGAD,ROCKET'],
            'full_name' => ['required', 'string', 'max:120'],
            'account' => [
                'required',
                'string',
                'max:80',
                'unique:withdraw_wallets,account_number',
            ],
        ]);
    } catch (ValidationException $e) {
        toast($e->validator->errors()->first(), 'error', 'cross');
        return back()->withInput();
    }

    $type = strtoupper(trim($request->type));

    if ($type === 'USDT') {
        toast('You are not allowed to add USDT wallet.', 'error', 'cross');
        return back()->withInput();
    }
    $welletCount = WithdrawWallet::where('user_id', $user->id)->count();
    
    if ($welletCount >= 2) {
        toast('You can add only two wallets.', 'error', 'cross');
        return back()->withInput();
    }

    DB::transaction(function () use ($user, $request, $type, &$wallet) {
        WithdrawWallet::query()
            ->where('user_id', $user->id)
            ->where('type', '!=', 'USDT')
            ->update([
                'is_active' => false,
            ]);

        $wallet = WithdrawWallet::create([
            'user_id' => $user->id,
            'type' => $type,
            'full_name' => $request->full_name,
            'account_number' => $request->account,
            'is_active' => true,
        ]);
    });

    toast('Wallet added successfully.', 'success');

    return redirect()->route('user.wallet.Withdraw', [
        'wallet_id' => $wallet->id,
    ]);
}

protected function transformWallet($wallet): array
{
    return [
        'id' => $wallet->id,
        'type' => $wallet->type,
        'full_name' => $wallet->full_name,
        'account_number' => $wallet->account_number,
        'masked_account' => $this->maskAccount($wallet->account_number),
        'is_active' => (bool) $wallet->is_active,
    ];
}

protected function maskAccount(?string $value): string
{
    $value = (string) $value;

    if (mb_strlen($value) <= 6) {
        return $value;
    }

    return mb_substr($value, 0, 3) . '****' . mb_substr($value, -3);
}


protected function transformWithdraw(Withdraw $withdraw): array
{
    $info = is_array($withdraw->information) ? $withdraw->information : [];

    $isUsdt = strtoupper((string) $withdraw->payout_currency_code) === 'USDT';

    $walletType = $withdraw->wallet?->type ?? ($info['wallet_type'] ?? $withdraw->payout_currency_code);

    $walletName = $withdraw->wallet?->full_name ?? ($info['wallet_name'] ?? 'N/A');

    $walletNumber = $withdraw->wallet ? $this->maskAccount($withdraw->wallet->account_number) : $this->maskAccount($withdraw->number);

    return [
        'id' => $withdraw->id,
        'amount' => number_format((float) $withdraw->amount, 2), 
        'charge' => number_format((float) $withdraw->charge, 2),
       'feedback' => match ((int) $withdraw->status) { 0 => 'Withdraw Pending', 3 => $withdraw->feedback, default => 'Withdraw Completed',},
        'net_amount' => number_format((float) $withdraw->net_amount, 2),
        'payout_amount' => $isUsdt ? number_format((float) ($info['payout_amount'] ?? 0), 2) . ' USDT' : '৳' . number_format((float) $withdraw->net_amount, 2),
        'number' => $withdraw->number,
        'trx_id' => $withdraw->trx_id,
        'status' => $withdraw->status,
        'status_label' => $withdraw->status_label,
        'status_class' => $withdraw->status_class,
        'created_at' => $withdraw->created_at?->format('Y-m-d H:i:s'),
        'wallet_type' => strtoupper((string) $walletType),
        'wallet_name' => $walletName,
        'wallet_number' => $walletNumber,
        'is_usdt' => $isUsdt,
    ];
}
}
