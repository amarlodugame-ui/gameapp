<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WithdrawWallet;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class WithdrawReportController extends Controller
{
    protected array $walletTypes = ['BKASH', 'NAGAD', 'ROCKET', 'USDT'];

    public function walletindex(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $selectedUserId = $request->integer('user_id');
        $page = max(1, (int) $request->get('page', 1));

        $users = User::query()
            ->select('id', 'name', 'username', 'referral_code', 'phone', 'balance')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('referral_code', 'like', "%{$q}%")
                        ->orWhere('username', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest('id')
            ->paginate(15, ['*'], 'page', $page);

        $users->appends([
            'q' => $q,
            'user_id' => $selectedUserId,
        ]);

        $selectedUser = null;
        $wallets = collect();

        if ($selectedUserId) {
            $selectedUser = User::query()
                ->select('id', 'name', 'username', 'referral_code', 'phone', 'balance')
                ->find($selectedUserId);
        }

        if (!$selectedUser && $users->count() > 0) {
            $selectedUser = $users->first();
        }

        if ($selectedUser) {
            $wallets = WithdrawWallet::query()
                ->where('user_id', $selectedUser->id)
                ->latest('id')
                ->get();
        }

        $pageTitle = 'Withdraw Wallets';

        return view('admin.withdraws.walletindex', [
            'q' => $q,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'wallets' => $wallets,
            'walletTypes' => $this->walletTypes,
            'pageTitle' => $pageTitle,
            'currentPage' => $users->currentPage(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', Rule::in($this->walletTypes)],
            'full_name' => ['required', 'string', 'max:120'],
            'account_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('withdraw_wallets', 'account_number'),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['type'] = strtoupper($data['type']);
        $isActive = (bool) ($data['is_active'] ?? false);

        DB::transaction(function () use ($data, $isActive) {
            if ($isActive) {
                $this->deactivateWalletGroup($data['user_id'], $data['type']);
            }

            WithdrawWallet::create([
                'user_id' => $data['user_id'],
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'account_number' => $data['account_number'],
                'is_active' => $isActive,
            ]);
        });

      $notifi[] = ['success', 'Wallet added successfully.'];


        return redirect()->route('admin.withdraws.report.withdraw-wallets.index', [
            'user_id' => $data['user_id'],
            'q' => $request->get('q'),
            'page' => max(1, (int) $request->get('page', 1)),
        ])->withNotify($notifi);
    }

    public function update(Request $request, WithdrawWallet $wallet)
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in($this->walletTypes)],
            'full_name' => ['required', 'string', 'max:120'],
            'account_number' => [
                'required',
                'string',
                'max:120',
                Rule::unique('withdraw_wallets', 'account_number')->ignore($wallet->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['type'] = strtoupper($data['type']);
        $isActive = (bool) ($data['is_active'] ?? false);

        DB::transaction(function () use ($wallet, $data, $isActive) {
            if ($isActive) {
                $this->deactivateWalletGroup($wallet->user_id, $data['type'], $wallet->id);
            }

            $wallet->update([
                'type' => $data['type'],
                'full_name' => $data['full_name'],
                'account_number' => $data['account_number'],
                'is_active' => $isActive,
            ]);
        });

      $notifi[] = ['success', 'Wallet updated successfully.'];

        return redirect()->route('admin.withdraws.report.withdraw-wallets.index', [
            'user_id' => $wallet->user_id,
            'q' => $request->get('q'),
            'page' => max(1, (int) $request->get('page', 1)),
        ])->withNotify($notifi);
    }

    public function activate(Request $request, WithdrawWallet $wallet)
    {
        DB::transaction(function () use ($wallet) {
            $this->deactivateWalletGroup($wallet->user_id, $wallet->type, $wallet->id);

            $wallet->update([
                'is_active' => true,
            ]);
        });

      $notifi[] = ['success', 'Wallet activated successfully.'];

        return redirect()->route('admin.withdraws.report.withdraw-wallets.index', [
            'user_id' => $wallet->user_id,
            'q' => $request->get('q'),
            'page' => max(1, (int) $request->get('page', 1)),
        ])->withNotify($notifi);
    }

    public function destroy(Request $request, WithdrawWallet $wallet)
    {
        $userId = $wallet->user_id;
        $type = $wallet->type;
        $wasActive = (bool) $wallet->is_active;

        DB::transaction(function () use ($wallet, $userId, $type, $wasActive) {
            $wallet->delete();

            if ($wasActive) {
                $replacement = WithdrawWallet::query()
                    ->where('user_id', $userId)
                    ->when(
                        strtoupper($type) === 'USDT',
                        fn ($q) => $q->where('type', 'USDT'),
                        fn ($q) => $q->where('type', '!=', 'USDT')
                    )
                    ->latest('id')
                    ->first();

                if ($replacement) {
                    $replacement->update([
                        'is_active' => true,
                    ]);
                }
            }
        });

        $notifi[] = ['success', 'Wallet deleted successfully.'];

        return redirect()->route('admin.withdraws.report.withdraw-wallets.index', [
            'user_id' => $userId,
            'q' => $request->get('q'),
            'page' => max(1, (int) $request->get('page', 1)),
        ])->withNotify($notifi);
    }

    protected function deactivateWalletGroup(int $userId, string $type, ?int $exceptId = null): void
    {
        $query = WithdrawWallet::query()->where('user_id', $userId);

        if (strtoupper($type) === 'USDT') {
            $query->where('type', 'USDT');
        } else {
            $query->where('type', '!=', 'USDT');
        }

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update([
            'is_active' => false,
        ]);
    }


    public function processing($id)
    {
        $withdraw = Withdraw::findOrFail($id);
    
        if ((int) $withdraw->status !== \App\Models\Withdraw::STATUS_PENDING) {
            $notifi[] = ['error', 'Withdraw already processed.'];
            return back()->withNotify($notifi);
        }
    
    
        if (strtoupper((string) $withdraw->payout_currency_code) === 'USDT') {
            $withdraw->update([
                'status' => Withdraw::STATUS_COMPLETED,
                'response_id' => $withdraw->trx_id,
                'feedback' => 'USDT withdraw approved',
                'last_error' => null,
                'meta_field' => [
                    'auto_approved' => true,
                    'currency' => $withdraw->payout_currency_code,
                    'approved_at' => now()->toDateTimeString(),
                    'approved_by' => 'system',
                    'note' => 'USDT withdraw skipped payout gateway and marked as completed automatically',
                ],
            ]);
    
            return response()->json([
                'status' => true,
                'message' => 'USDT withdraw auto approved successfully',
                'gateway_response' => null,
            ]);
        }
    
    // try {
    //     $response = $this->hendelStarpagoAutoWithdraw(
    //         $withdraw,
    //         $withdraw->net_amount,
    //         $withdraw->user,
    //         $withdraw->number,
    //         (string) data_get($withdraw->information, 'wallet_type'),
    //         $withdraw->payout_currency_code
    //     );
    
    //     if (($response['ok'] ?? false) === true) {
    //         $notifi[] = ['success', 'Withdraw moved to processing.'];
    //     } else {
    //         $notifi[] = ['error', 'Withdraw payout failed.'];
    //     }
    // } catch (\Throwable $e) {
    //     $notifi[] = ['error', $e->getMessage()];
    // }
        
        //OKEY Pay OUt created 
        $response = $this->send($id);
        $responseData = method_exists($response, 'getData') ? $response->getData(true) : [];
        if (($responseData['status'] ?? false) === true) {
            $notifi[] = ['success', $responseData['message'] ?? 'Withdraw moved to processing.'];
        } else {
            $notifi[] = ['error', $responseData['message'] ?? 'Withdraw payout failed.'];
        }
        return back()->withNotify($notifi);
    }




//STerGO AutoPay OUt Function And Heldeler
public function hendelStarpagoAutoWithdraw(Withdraw $withdraw, $amount, User $user, string $account, string $methodType, string $currencyCode): array
{
    $host   = rtrim(env('STARPAGO_HOST'), '/');
    $appId  = env('STARPAGO_APP_ID');
    $secret = env('STARPAGO_APP_SECRET');
    $notify = env('STARPAGO_PAYOUT_NOTIFY_URL');

    if (!$host || !$appId || !$secret || !$notify) {
        throw new \RuntimeException('Starpago credentials missing.');
    }

    $rawType = strtoupper((string) $methodType);
    $map = [
        'BKASH' => 'BD_BKASH',
        'NAGAD' => 'BD_NAGAD',
    ];

    $payMethod = $map[$rawType] ?? null;
    if (!$payMethod) {
        throw new \RuntimeException('Unsupported payout type: ' . $rawType);
    }

    $amountStr = (string) (is_numeric($amount) ? (int) round((float) $amount) : $amount);
    $phone = preg_replace('/\s+/', '', (string) ($user->phone ?? ''));

    $url = $host . '/api/v2/payout/order/create';

    $attempts = 0;
    $lastBody = null;

    while ($attempts < 2) {
        $attempts++;
        [$withdrawId, $merOrderNo] = DB::transaction(function () use ($withdraw, $account) {
            $w = Withdraw::where('id', $withdraw->id)->lockForUpdate()->firstOrFail();

            if (in_array((int) $w->status, [Withdraw::STATUS_COMPLETED, Withdraw::STATUS_REJECTED], true)) {
                return [
                    $w->id,
                    (string) ($w->trx_id ?: ('WD' . now()->format('YmdHis') . Str::upper(Str::random(6)))),
                ];
            }
            if (!$w->trx_id) {
                $w->trx_id = 'WD' . now()->format('YmdHis') . Str::upper(Str::random(6));
            }
            if (!$w->number && $account !== '') {
                $w->number = $account;
            }

            $w->save();

            return [$w->id, (string) $w->trx_id];
        });

        $payload = [
            'appId'      => (string) $appId,
            'merOrderNo' => (string) $merOrderNo,
            'currency'   => (string) $currencyCode,
            'amount'     => (string) $amountStr,
            'notifyUrl'  => (string) $notify,
            'payMethod'  => (string) $payMethod,
            'extra' => [
                'email'       => (string) ($user->username.'@gmail.com' ?? ''),
                'mobile'      => (string) $phone,
                'accountNo'   => (string) $account,
                'accountName' => (string) (data_get($withdraw->information, 'wallet_name')?? data_get($withdraw->information, 'full_name')?? $user->username?? $user->name?? 'N/A'),
            ],
            'attach' => (string) $withdrawId,
        ];

        $payload['sign'] = $this->starpagoSign($payload, $secret);

        $res = Http::timeout(30)->acceptJson()->asJson()->post($url, $payload);

        $body = $res->json();
        $lastBody = $body;

        if (!is_array($body)) {
            throw new \RuntimeException('Starpago invalid JSON: ' . $res->body());
        }

        if (!$res->ok()) {
            throw new \RuntimeException('Starpago HTTP error: ' . json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $code = (int) ($body['code'] ?? -999);
        if ($code !== 0) {
            $msg = (string) ($body['msg'] ?? $body['error'] ?? 'Unknown error');

            if ($attempts < 2 && stripos($msg, 'duplicate') !== false && stripos($msg, 'merOrderNo') !== false) {
                DB::transaction(function () use ($withdrawId) {
                    $w = Withdraw::where('id', $withdrawId)->lockForUpdate()->firstOrFail();
                    $w->trx_id = 'WD' . now()->format('YmdHis') . Str::upper(Str::random(8));
                    $w->save();
                });

                continue;
            }

            DB::transaction(function () use ($withdrawId, $msg) {
                $w = Withdraw::where('id', $withdrawId)->lockForUpdate()->firstOrFail();
                $w->last_error = $msg;
                $w->status = Withdraw::STATUS_REJECTED;

                $meta = $w->meta_field ?? [];
                $meta['create_error'] = $msg;
                $w->meta_field = $meta;

                $w->save();
            });

            throw new \RuntimeException('Starpago create failed: ' . $msg);
        }

        $orderNo = (string) data_get($body, 'data.orderNo', '');
        $orderStatus = (int) data_get($body, 'data.orderStatus', 0);
        DB::transaction(function () use ($withdrawId, $orderNo, $orderStatus, $body, $attempts, $payMethod) {
            $w = Withdraw::where('id', $withdrawId)->lockForUpdate()->firstOrFail();

            $meta = $w->meta_field ?? [];
            $meta['gateway'] = 'starpago';
            $meta['pay_method'] = $payMethod;
            $meta['create_attempt'] = $attempts;
            $meta['create_response'] = $body;

            $w->response_id = $orderNo ?: $w->response_id;
            $w->last_error = null;
            $w->meta_field = $meta;
            $w->status = Withdraw::STATUS_PROCESSING;
            $w->feedback = 'Withdraw sent to Starpago';

            if (in_array($orderStatus, [2, 3], true)) {
                $w->status = Withdraw::STATUS_COMPLETED;
                $w->feedback = 'Withdraw completed instantly';
            }

            $w->save();
        });

        return [
            'ok' => true,
            'merOrderNo' => $merOrderNo,
            'orderNo' => $orderNo,
            'orderStatus' => $orderStatus,
            'raw' => $body,
        ];
    }

    throw new \RuntimeException('Starpago create failed after retry: ' . json_encode($lastBody, JSON_UNESCAPED_UNICODE));
}

public function payoutHandleNotify(Request $request)
{
    $payload = $request->all();

    Log::info('STARPAGO_NOTIFY_PAYLOAD', [
        'payload' => $payload,
    ]);

    try {
        $secret = env('STARPAGO_APP_SECRET');
        if (!$secret) {
            return response()->json(['ok' => false, 'msg' => 'secret missing'], 500);
        }

        $receivedSign = (string) data_get($payload, 'sign', '');
        if ($receivedSign === '') {
            Log::warning('STARPAGO_NOTIFY_SIGN_MISSING', ['payload' => $payload]);
            return response()->json(['ok' => false, 'msg' => 'sign missing'], 400);
        }

        $calcA = $this->starpagoSign($this->removeSignField($payload), $secret);

        $calcB = null;
        if (isset($payload['data']) && is_array($payload['data'])) {
            $calcB = $this->starpagoSign($this->removeSignField($payload['data']), $secret);
        }

        $ok = hash_equals(strtolower($receivedSign), strtolower($calcA))
            || ($calcB && hash_equals(strtolower($receivedSign), strtolower($calcB)));

        if (!$ok) {
            Log::warning('STARPAGO_NOTIFY_SIGN_INVALID', [
                'received' => $receivedSign,
                'calcA' => $calcA,
                'calcB' => $calcB,
                'payload_keys' => array_keys($payload),
            ]);

            return response()->json(['ok' => false, 'msg' => 'invalid sign'], 400);
        }

        $data = data_get($payload, 'data', $payload);

        $merOrderNo  = (string) data_get($data, 'merOrderNo', data_get($payload, 'merOrderNo', ''));
        $orderNo     = (string) data_get($data, 'orderNo', data_get($payload, 'orderNo', ''));
        $orderStatus = (int) data_get($data, 'orderStatus', data_get($payload, 'orderStatus', 0));
        $message     = (string) data_get($data, 'message', data_get($payload, 'message', ''));

        Log::info('STARPAGO_NOTIFY_PARSED', [
            'merOrderNo' => $merOrderNo,
            'orderNo' => $orderNo,
            'orderStatus' => $orderStatus,
            'message' => $message,
        ]);

        if ($merOrderNo === '') {
            Log::warning('STARPAGO_NOTIFY_MERORDERNO_MISSING', ['payload' => $payload]);
            return response()->json(['ok' => false, 'msg' => 'merOrderNo missing'], 400);
        }

        DB::transaction(function () use ($merOrderNo, $orderNo, $orderStatus, $message, $payload) {

            $withdraw = Withdraw::where('trx_id', $merOrderNo)
                ->lockForUpdate()
                ->with('user')
                ->first();

            if (!$withdraw) {
                Log::error('STARPAGO_NOTIFY_WITHDRAW_NOT_FOUND', [
                    'lookup_field' => 'withdraws.trx_id',
                    'merOrderNo' => $merOrderNo,
                ]);
                return;
            }

            Log::info('STARPAGO_NOTIFY_WITHDRAW_FOUND', [
                'withdraw_id' => $withdraw->id,
                'current_status' => (int) $withdraw->status,
            ]);
            if (in_array((int) $withdraw->status, [Withdraw::STATUS_COMPLETED, Withdraw::STATUS_REJECTED], true)) {
                Log::info('STARPAGO_NOTIFY_ALREADY_FINAL', [
                    'withdraw_id' => $withdraw->id,
                    'status' => (int) $withdraw->status,
                ]);
                return;
            }

            $meta = $withdraw->meta_field ?? [];
            $meta['gateway'] = 'starpago';
            $meta['notify_payload'] = $payload;
            $meta['notify_order_status'] = $orderStatus;
            $meta['notified_at'] = now()->toDateTimeString();

            $withdraw->response_id = $orderNo ?: $withdraw->response_id;
            $withdraw->meta_field = $meta;

            if (in_array($orderStatus, [2, 3], true)) {
                $withdraw->status = Withdraw::STATUS_COMPLETED;
                $withdraw->last_error = null;
                $withdraw->feedback = $message ?: 'Withdraw completed';
                $withdraw->save();

                Log::info('STARPAGO_NOTIFY_MARK_SUCCESS', [
                    'withdraw_id' => $withdraw->id,
                    'orderStatus' => $orderStatus,
                ]);

                return;
            }

            if ($orderStatus < 0) {
                $refundAmount = (float) $withdraw->net_amount + (float) $withdraw->charge;
                $user = User::where('id', $withdraw->user_id)->lockForUpdate()->first();

                if (!$user) {
                    Log::error('STARPAGO_NOTIFY_USER_NOT_FOUND', [
                        'withdraw_id' => $withdraw->id,
                        'user_id' => $withdraw->user_id,
                    ]);
                    return;
                }

                $withdraw->status = Withdraw::STATUS_REJECTED;
                $withdraw->last_error = $message ?: ('Withdraw failed: ' . $orderStatus);
                $withdraw->feedback = $message ?: 'Withdraw rejected';
                $withdraw->save();

                Log::warning('STARPAGO_NOTIFY_MARK_FAILED_REFUNDED', [
                    'withdraw_id' => $withdraw->id,
                    'orderStatus' => $orderStatus,
                    'refund' => $refundAmount,
                    'user_id' => $user->id,
                ]);

                return;
            }

            $withdraw->status = Withdraw::STATUS_PROCESSING;
            $withdraw->last_error = null;
            $withdraw->feedback = $message ?: 'Withdraw is processing';
            $withdraw->save();

            Log::info('STARPAGO_NOTIFY_IN_PROGRESS', [
                'withdraw_id' => $withdraw->id,
                'orderStatus' => $orderStatus,
            ]);
        });

        Log::info('STARPAGO_NOTIFY_DONE', ['merOrderNo' => $merOrderNo]);
        return response()->json(['ok' => true]);

    } catch (\Throwable $e) {
        Log::error('STARPAGO_NOTIFY_EXCEPTION', [
            'msg' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json(['ok' => false, 'msg' => 'server error'], 500);
    }
}
private function starpagoSign(array $data, string $secret): string
{
    unset($data['sign']);
    $data = $this->normalizeForStarpago($data);
    $pairs = $this->buildPairsForStarpago($data);
    $pairs[] = 'key=' . $secret;
    return hash('sha256', implode('&', $pairs));
}
private function normalizeForStarpago(array $data): array
{
    $clean = [];

    foreach ($data as $k => $v) {
        if ($v === null || $v === '') {
            continue;
        }

        if (is_array($v)) {
            if ($k === 'extra') {
                $clean[$k] = $this->normalizeForStarpago($v);
            } else {
                $clean[$k] = $v;
            }
        } else {
            $clean[$k] = (string) $v;
        }
    }

    return $clean;
}
private function buildPairsForStarpago(array $data): array
{
    ksort($data, SORT_STRING);

    $pairs = [];

    foreach ($data as $k => $v) {
        if ($k === 'extra' && is_array($v)) {
            ksort($v, SORT_STRING);

            $extraParts = [];
            foreach ($v as $ek => $ev) {
                if ($ev === null || $ev === '') {
                    continue;
                }
                $extraParts[] = $ek . '=' . (string) $ev;
            }

            $pairs[] = 'extra=' . implode('&', $extraParts);
            continue;
        }

        if (!is_array($v)) {
            $pairs[] = $k . '=' . (string) $v;
        }
    }

    return $pairs;
}
private function removeSignField(array $payload): array
{
    unset($payload['sign']);
    if (isset($payload['data']) && is_array($payload['data'])) {
        unset($payload['data']['sign']);
    }
    return $payload;
}





//OKEY Pay Out Create funciton
public function send($id)
{
    $withdraw = Withdraw::find($id);

    if (!$withdraw) {
        \Log::warning('Withdraw not found', ['withdraw_id' => $id]);

        return response()->json([
            'status' => false,
            'message' => 'Withdraw not found',
        ], 404);
    }

    if (!in_array((int) $withdraw->status, [ Withdraw::STATUS_PENDING, Withdraw::STATUS_REJECTED,])) {
        \Log::warning('Withdraw not eligible for payout', [
            'withdraw_id' => $withdraw->id,
            'current_status' => $withdraw->status,
        ]);

        return response()->json([
            'status' => false,
            'message' => 'This withdraw is not eligible for payout',
        ], 422);
    }

    try {
        $info = is_array($withdraw->information) ? $withdraw->information : [];

        $payType = strtoupper($info['wallet_type']);
        if (!in_array($payType, ['BKASH', 'NAGAD'])) {
            \Log::error('Invalid payout wallet type', [
                'withdraw_id' => $withdraw->id,
                'pay_type' => $payType,
            ]);

            $withdraw->update([
                'status' => Withdraw::STATUS_REJECTED,
                'feedback' => 'Invalid payout wallet type',
                'last_error' => 'Invalid payout wallet type',
                'meta_field' => ['error' => 'Invalid payout wallet type','pay_type' => $payType,],
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Invalid payout wallet type',
            ], 422);
        }

        $payload = [
            'mchId'        => env('PAYIN_MCH_ID'),
            'currency'     => 'BDT',
            'out_trade_no' => $withdraw->trx_id,
            'pay_type'     => $payType,
            'account'      => $withdraw->number,
            'userName'     => $info['wallet_name'] ?? $info['userName'] ?? $withdraw->user?->name ?? 'Customer',
            'money'        => (int) $withdraw->net_amount,
            'attach'       => (string) $withdraw->id,
            'notify_url'   => env('PAYOUT_CALLBACK_URL'),
        ];

        $payload['sign'] = $this->makeSign($payload);
        
        $host = env('PAYIN_HOST');
        $url = rtrim(env('PAYIN_BASE_URL', "https://{$host}"), '/') . '/v1/Payout';

        $response = \Illuminate\Support\Facades\Http::asForm()
            ->timeout(60)
            ->post($url, $payload);

        $rawBody = $response->body();
        $json = $response->json();

        $metaFieldData = [
            'request_url' => $url,
            'request_payload' => $payload,
            'http_status' => $response->status(),
            'raw_body' => $rawBody,
            'json_response' => $json,
            'response_headers' => $response->headers(),
            'requested_at' => now()->toDateTimeString(),
        ];

        $feedbackMessage = null;

        if (is_array($json)) {
            $feedbackMessage =
                $json['msg']
                ?? $json['message']
                ?? ($json['data']['msg'] ?? null)
                ?? ($json['data']['message'] ?? null);
        }

        $feedbackMessage = $feedbackMessage ?: ($rawBody ?: 'Gateway response received');

        $providerSuccess = false;

        if (is_array($json)) {
            if (isset($json['code']) && (string) $json['code'] === '0') {
                $providerSuccess = true;
            } elseif (isset($json['status']) && in_array(strtolower((string) $json['status']), ['success', 'processing', 'pending'])) {
                $providerSuccess = true;
            }
        }

        if ($response->successful() && $providerSuccess) {
            $withdraw->update([
                'status' => Withdraw::STATUS_COMPLETED,
                'response_id' => $json['data']['orderNo'] ?? $json['orderNo'] ?? $json['trade_no'] ?? null,
                'meta_field' => $metaFieldData,
                'feedback' => $feedbackMessage,
                'last_error' => null,
            ]);

            // \Log::info('Withdraw updated after successful gateway submit', [
            //     'withdraw_id' => $withdraw->id,
            //     'db_status' => Withdraw::STATUS_COMPLETED,
            //     'response_id' => $json['data']['orderNo'] ?? $json['orderNo'] ?? $json['trade_no'] ?? null,
            //     'feedback' => $feedbackMessage,
            // ]);

            // \Log::info('================ Payout Send Success End ================');

            return response()->json([
                'status' => true,
                'message' => 'Payout submitted successfully',
                'gateway_response' => is_array($json) ? $json : $rawBody,
            ]);
        }

        $withdraw->update([
            'status' => Withdraw::STATUS_REJECTED,
            'response_id' => is_array($json) ? ($json['data']['orderNo'] ?? $json['orderNo'] ?? $json['trade_no'] ?? null) : null,
            'meta_field' => $metaFieldData,
            'feedback' => $feedbackMessage,
            'last_error' => $rawBody,
        ]);

        return response()->json([
            'status' => false,
            'message' => $feedbackMessage ?: 'Gateway payout failed',
            'gateway_response' => is_array($json) ? $json : $rawBody,
        ], 422);

    } catch (\Throwable $e) {
        \Log::error('Payout send exception', [
            'withdraw_id' => $withdraw->id ?? $id,
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (!empty($withdraw)) {
            $existingMeta = is_array($withdraw->meta_field) ? $withdraw->meta_field : [];

            $withdraw->update([
                'status' => Withdraw::STATUS_REJECTED,
                'feedback' => $e->getMessage(),
                'last_error' => $e->getMessage(),
                'meta_field' => array_merge($existingMeta, [
                    'exception' => [
                        'message' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile(),
                    ],
                ]),
            ]);
        }

        \Log::info('================ Payout Send Exception End ================');

        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
private function makeSign(array $data): string
{
    unset($data['sign']);

    ksort($data);

    $pairs = [];
    foreach ($data as $key => $value) {
        if ($value !== null && $value !== '') {
            $pairs[] = $key . '=' . $value;
        }
    }

    $signString = implode('&', $pairs) . '&key=' . env('PAYIN_SECRET_KEY');

    // \Log::info('Sign string generated', [
    //     'sign_string' => $signString,
    // ]);

    return md5($signString);
}
    
    
    
    
    
    
    public function index(Request $request)
{
    $pageTitle = 'Withdraw Reports';

    $status   = $request->input('status', '');
    $userId   = trim((string) $request->input('user_id', ''));
    $trxId    = trim((string) $request->input('trx_id', ''));
    $dateFrom = $request->input('date_from');
    $dateTo   = $request->input('date_to');

    $perPage = (int) $request->input('per_page', 20);
    $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

    $baseQuery = Withdraw::query()
        ->with('user:id,name,username,email,phone,role,referral_code,created_at')
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

    $withdraws = (clone $baseQuery)
        ->latest('id')
        ->paginate($perPage)
        ->appends($request->all());

    $rows = $withdraws->getCollection();

    foreach ($rows as $withdraw) {
        $withdraw->multi_bet_detected = false;
        $withdraw->multi_bet_text = null;
        $withdraw->multi_bet_details = [];

        $previousWithdraw = Withdraw::query()
            ->where('user_id', $withdraw->user_id)
            ->where(function ($q) use ($withdraw) {
                $q->where('created_at', '<', $withdraw->created_at)
                  ->orWhere(function ($qq) use ($withdraw) {
                      $qq->where('created_at', $withdraw->created_at)
                         ->where('id', '<', $withdraw->id);
                  });
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // first withdraw হলে account create time থেকে check হবে
        $fromTime = $previousWithdraw
            ? $previousWithdraw->created_at
            : optional($withdraw->user)->created_at;

        // current withdraw পর্যন্ত check হবে
        $toTime = $withdraw->created_at;

        $aviatorRounds = $this->getAviatorMultiBetRounds($withdraw->user_id, $fromTime, $toTime);
        $wingoRounds   = $this->getWingoMultiBetRounds($withdraw->user_id, $fromTime, $toTime);

        $details = [];

        if ($aviatorRounds->isNotEmpty()) {
            foreach ($aviatorRounds as $row) {
                $details[] = [
                    'game'         => 'Aviator',
                    'round_id'     => $row->round_id,
                    'round_no'     => $row->round_no,
                    'bet_count'    => (int) $row->bet_count,
                    'total_amount' => (float) $row->total_amount,
                    'bets'         => $row->bet_details,
                    'text'         => 'Aviator | Round #' . ($row->round_no ?? $row->round_id)
                        . ' | Bets: ' . $row->bet_count
                        . ' | Total: ' . $row->total_amount
                        . ' | Details: ' . ($row->bet_details ?: '-'),
                ];
            }
        }

        if ($wingoRounds->isNotEmpty()) {
            foreach ($wingoRounds as $row) {
                $details[] = [
                    'game'         => 'Wingo',
                    'round_id'     => $row->round_id,
                    'period_id'    => $row->period_id,
                    'game_type'    => $row->game_type,
                    'bet_count'    => (int) $row->bet_count,
                    'total_amount' => (float) $row->total_amount,
                    'bets'         => $row->bet_details,
                    'text'         => 'Wingo | ' . ($row->game_type ?? 'N/A')
                        . ' | Period: ' . ($row->period_id ?? ('Round #' . $row->round_id))
                        . ' | Bets: ' . $row->bet_count
                        . ' | Total: ' . $row->total_amount
                        . ' | Details: ' . ($row->bet_details ?: '-'),
                ];
            }
        }

        if (!empty($details)) {
            $withdraw->multi_bet_detected = true;
            $withdraw->multi_bet_details = $details;
            $withdraw->multi_bet_text = collect($details)->pluck('text')->implode(' || ');
        }
    }

    $withdraws->setCollection($rows);

    $today      = Carbon::today();
    $yesterday  = Carbon::yesterday();
    $monthStart = Carbon::now()->startOfMonth();
    $monthEnd   = Carbon::now()->endOfMonth();

    $realUserWithdraws = Withdraw::query()
        ->whereHas('user', function ($query) {
            $query->where('role', '!=', 'demo');
        });

    $stats = [
        'today_total' => (clone $realUserWithdraws)
            ->whereDate('created_at', $today)
            ->where('status', 2)
            ->sum('amount'),

        'yesterday_total' => (clone $realUserWithdraws)
            ->whereDate('created_at', $yesterday)
            ->where('status', 2)
            ->sum('amount'),

        'this_month_total' => (clone $realUserWithdraws)
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 2)
            ->sum('amount'),

        'pending_count'    => (clone $realUserWithdraws)->where('status', 0)->count(),
        'processing_count' => (clone $realUserWithdraws)->where('status', 1)->count(),
        'completed_count'  => (clone $realUserWithdraws)->where('status', 2)->count(),
        'rejected_count'   => (clone $realUserWithdraws)->where('status', 3)->count(),

        'pending_total'    => (clone $realUserWithdraws)->where('status', 0)->sum('amount'),
        'processing_total' => (clone $realUserWithdraws)->where('status', 1)->sum('amount'),
        'completed_total'  => (clone $realUserWithdraws)->where('status', 2)->sum('amount'),
        'rejected_total'   => (clone $realUserWithdraws)->where('status', 3)->sum('amount'),
    ];

    return view('admin.withdraws.report', compact(
        'pageTitle',
        'withdraws',
        'stats',
        'status'
    ));
}
private function getWingoMultiBetRounds($userId, $fromTime = null, $toTime = null)
{
    $query = DB::table('bets as b')
        ->leftJoin('game_rounds as gr', 'gr.id', '=', 'b.round_id')
        ->where('b.user_id', $userId);

    if ($fromTime) {
        $query->where('b.created_at', '>=', $fromTime);
    }

    if ($toTime) {
        $query->where('b.created_at', '<=', $toTime);
    }

    $rows = $query
        ->select([
            'b.id',
            'b.round_id',
            'gr.period_id',
            'gr.game_type',
            'b.bet_type',
            'b.bet_value',
            'b.amount',
            'b.status',
            'b.created_at',
        ])
        ->orderBy('b.id', 'asc')
        ->get();

    $grouped = $rows->groupBy(function ($row) {
        return implode('|', [
            $row->round_id,
            $row->period_id,
            $row->game_type,
        ]);
    });

    $results = collect();

    foreach ($grouped as $groupRows) {
        $first = $groupRows->first();

        $sizeBets = [];
        $colorBets = [];

        foreach ($groupRows as $row) {
            $value = strtolower(trim((string) $row->bet_value));

            if (in_array($value, ['big', 'small'], true)) {
                $sizeBets[] = $row;
            }

            if (in_array($value, ['red', 'green', 'violet'], true)) {
                $colorBets[] = $row;
            }
        }

        $illegalGroups = [];

        $distinctSizeValues = collect($sizeBets)
            ->pluck('bet_value')
            ->map(fn ($v) => strtolower(trim((string) $v)))
            ->unique()
            ->values();

        if ($distinctSizeValues->count() >= 2) {
            $illegalGroups[] = [
                'group_name' => 'size',
                'values' => $distinctSizeValues->all(),
                'bets' => $sizeBets,
            ];
        }

        $distinctColorValues = collect($colorBets)
            ->pluck('bet_value')
            ->map(fn ($v) => strtolower(trim((string) $v)))
            ->unique()
            ->values();

        if ($distinctColorValues->count() >= 2) {
            $illegalGroups[] = [
                'group_name' => 'color',
                'values' => $distinctColorValues->all(),
                'bets' => $colorBets,
            ];
        }

        foreach ($illegalGroups as $illegal) {
            $betDetails = collect($illegal['bets'])
                ->map(function ($bet) {
                    return 'Bet#' . $bet->id
                        . ' Type:' . ($bet->bet_type ?? '-')
                        . ' Value:' . ($bet->bet_value ?? '-')
                        . ' Amount:' . $bet->amount
                        . ' Status:' . ($bet->status ?? '-');
                })
                ->implode(' | ');

            $results->push((object) [
                'round_id'     => $first->round_id,
                'period_id'    => $first->period_id,
                'game_type'    => $first->game_type,
                'group_name'   => $illegal['group_name'],
                'bet_count'    => count($illegal['bets']),
                'total_amount' => collect($illegal['bets'])->sum('amount'),
                'bet_details'  => $betDetails,
                'picked_values'=> implode(', ', $illegal['values']),
            ]);
        }
    }

    return $results->values();
}
private function getAviatorMultiBetRounds($userId, $fromTime = null, $toTime = null)
{
    $invalidStatuses = ['cancelled'];

    $query = DB::table('aviator_bets as ab')
        ->leftJoin('aviator_rounds as ar', 'ar.id', '=', 'ab.round_id')
        ->where('ab.user_id', $userId)
        ->where('ab.is_demo', 0)
        // cancelled bet count হবে না
        ->where(function ($q) use ($invalidStatuses) {
            $q->whereNull('ab.status')
              ->orWhereNotIn('ab.status', $invalidStatuses);
        });

    if ($fromTime) {
        $query->where('ab.created_at', '>=', $fromTime);
    }

    if ($toTime) {
        $query->where('ab.created_at', '<=', $toTime);
    }

    return $query
        // round level না, same slot duplicate ধরবে
        ->groupBy('ab.round_id', 'ar.round_no', 'ab.slot')
        ->havingRaw('COUNT(ab.id) >= 2')
        ->orderByDesc(DB::raw('MAX(ab.id)'))
        ->select([
            'ab.round_id',
            'ar.round_no',
            'ab.slot',
            DB::raw('COUNT(ab.id) as bet_count'),
            DB::raw('SUM(ab.amount) as total_amount'),
            DB::raw("
                GROUP_CONCAT(
                    CONCAT(
                        'Bet#', ab.id,
                        ' Amount:', ab.amount,
                        ' Slot:', COALESCE(ab.slot, '-'),
                        ' Status:', COALESCE(ab.status, '-')
                    )
                    ORDER BY ab.id ASC SEPARATOR ' | '
                ) as bet_details
            "),
        ])
        ->get();
}






public function approve($id)
{
    $withdraw = Withdraw::findOrFail($id);

    if ($withdraw->status == 2) {
        return back()->with('error', 'Already completed.');
    }

    $withdraw->status = 2;
    $withdraw->save();
    createUserNotification(
        $withdraw->user_id,
        'Withdrawal Approved',
        'Your withdrawal has been approved and the funds have been transferred. Please note that delivery may be delayed on public holidays.',
        'withdrawal_processed'
    );

    $notifi[] = ['success', 'Withdraw approved successfully.'];
    return back()->withNotify($notifi);
}

public function reject(Request $request, $id)
{
    DB::beginTransaction();
    $request->validate([
        'feedback' => 'required|string|max:500',
    ]);

    try {

        $withdraw = Withdraw::findOrFail($id);

        if ($withdraw->status == 3) {
            $notifi[] = ['error', 'Already rejected.'];
            return back()->withNotify($notifi);
        }

        $user = $withdraw->user;

        // refund balance
        $user->balance += $withdraw->amount;
        $user->save();

        $withdraw->status = 3;
        $withdraw->feedback = $request->feedback;
        $withdraw->save();

        // log trx
        $trx = new Transaction();
        $trx->user_id = $user->id;
        $trx->amount = $withdraw->amount;
        $trx->post_balance = (float) $user->balance;
        $trx->charge = 0;
        $trx->trx_type = '+';
        $trx->details = $request->feedback;;
        $trx->remark = 'withdraw_reject_refund';
        $trx->trx = get_trx();
        $trx->save();   

        createUserNotification(
            $user->id,
            'Withdrawal Rejected',
            "Your withdrawal request has been rejected. Reason: {$request->feedback}. The amount has been refunded to your account.",
            'withdrawal_rejected'
        );

        DB::commit();
        $notifi[] = ['success', 'Withdraw rejected and amount refunded.'];
        return back()->withNotify($notifi);

    } catch (\Exception $e) {

        DB::rollBack();

        $notifi[] = ['error', 'Something went wrong.'];
        return back()->withNotify($notifi);
    }
}





}