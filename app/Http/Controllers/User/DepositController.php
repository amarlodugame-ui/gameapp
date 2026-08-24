<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Lib\StargoHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;



use Illuminate\Support\Facades\Log;
use Throwable;


class DepositController extends Controller
{
public function index()
{
    $user = auth()->user();

    $methods = [
        ['key' => 'NAGAD', 'label' => 'Nagad', 'image' => '/assets/png/paynagod.png'],
        ['key' => 'BKASH', 'label' => 'bKash', 'image' => '/assets/png/paybkash.png'],
        // ['key' => 'USDT', 'label' => 'USDT', 'image' => '/assets/png/paytrxtw.png'],
    ];

    $channels = [
        'NAGAD' => [
            ['name' => 'GoPay-Nagad', 'min' => 100, 'max' => 25000],
            ['name' => 'RolezPay-nagad', 'min' => 100, 'max' => 30000],
        ],
        'BKASH' => [
            ['name' => 'GoPay-bKash', 'min' => 100, 'max' => 25000],
            ['name' => 'FastPay-bKash', 'min' => 100, 'max' => 30000],
        ],
    ];

    $commissionRows = DB::table('deposit_method_commissions')
        ->select('method_key', 'commission_percent', 'is_active')
        ->get();

    $commissionMap = [];

    foreach ($commissionRows as $row) {
        $commissionMap[$row->method_key] = [
            'percent' => (float) $row->commission_percent,
            'active' => (bool) $row->is_active,
        ];
    }

    // inactive method hide
    $methods = array_values(array_filter($methods, function ($method) use ($commissionMap) {
        return isset($commissionMap[$method['key']])
            && $commissionMap[$method['key']]['active'];
    }));

    $recentDeposits = Deposit::query()
        ->where('user_id', $user->id)
        ->latest('id')
        ->take(5)
        ->get()
        ->map(fn ($deposit) => $this->transformDeposit($deposit));

    return UserSpaPage::render('user/deposit/Deposit', [
        'balance' => (float) $user->balance,
        'methods' => $methods,
        'channels' => $channels,
        'commissionMap' => $commissionMap,
        'quickAmounts' => [200, 400, 500, 5000, 10000, 50000],
        'recentDeposits' => $recentDeposits,
        'instructions' => [
            'If the transfer time is up, please fill out the deposit form again.',
            'The transfer amount must match the order you created, otherwise the money cannot be credited successfully.',
            'If you transfer the wrong amount, our company will not be responsible for the lost amount!',
            'Note: do not cancel the deposit order after the money has been transferred.',
        ],
    ]);
}




public function store(Request $request)
{
    $data = $request->validate([
        'payment_method_currency' => ['required', 'in:NAGAD,BKASH'],
        'channel_name' => ['required', 'string', 'max:100'],
        'amount' => ['required', 'integer', 'min:200', 'max:250000'],
    ]);

    $user = auth()->user();

    if ($user->isDemo()) {
        return response()->json([
            'status' => false,
            'message' => 'Deposits are not allowed for demo users.',
        ], 403);
    }

    $host = env('PAYIN_HOST');
    $mchId = env('PAYIN_MCH_ID');
    $secretKey = env('PAYIN_SECRET_KEY');

    $notifyUrl = route('payment.deposit.callback');
    $returnUrl = route('user.wallet.recharge');

    $trxId = 'RC' . now()->format('YmdHis') . random_int(100000, 999999) . Str::lower(Str::random(1));
    $outTradeNo = 'DEP' . now()->format('YmdHis') . rand(1000, 9999);
    $payType = $data['payment_method_currency'] === 'BKASH' ? 'BKASH' : 'NAGAD';

    $deposit = Deposit::create([
        'user_id' => $user->id,
        'payment_method_currency' => 'BDT',
        'amount' => (float) $data['amount'],
        'charge' => 0,
        'payable_amount' => (float) $data['amount'],
        'payment_id' => $outTradeNo,
        'trx_id' => $trxId,
        'information' => [
            'method' => $payType,
            'channel' => $data['channel_name'],
        ],
        'gateway_notify_response' => null,
        'note' => null,
        'status' => 0,
    ]);

    $payload = [
        'mchId' => $mchId,
        'currency' => 'BDT',
        'out_trade_no' => $outTradeNo,
        'pay_type' => $payType,
        'money' => (int) $data['amount'],
        'attach' => (string) $deposit->id,
        'notify_url' => $notifyUrl,
        'returnUrl' => $returnUrl,
    ];

    $payload['sign'] = $this->generateCollectSign($payload, $secretKey);

    try {
        $url = rtrim(env('PAYIN_BASE_URL', "https://{$host}"), '/') . '/v1/Collect';

        Log::info('Collect Request', [
            'url' => $url,
            'payload' => $payload,
            'sign_string_preview' => $this->buildSignString($payload, $secretKey),
        ]);

        $response = Http::asForm()
            ->timeout(30)
            ->post($url, $payload);

        $body = $response->body();
        $json = $response->json();

        $deposit->gateway_notify_response = $body;
        $deposit->save();

        if (! $response->successful()) {
            $deposit->note = 'Gateway HTTP error: ' . $response->status();
            $deposit->save();

            return response()->json([
                'status' => false,
                'message' => 'Payment create failed. Gateway returned ' . $response->status(),
                'data' => $json,
            ], 422);
        }

        if (($json['code'] ?? null) !== 0) {
            $deposit->note = $json['msg'] ?? 'Gateway returned failed response';
            $deposit->save();

            return response()->json([
                'status' => false,
                'message' => 'Payment create failed: ' . ($json['msg'] ?? 'Unknown gateway error'),
                'data' => $json,
            ], 422);
        }

        $paymentUrl = $json['data']['url'] ?? null;

        if (! $paymentUrl) {
            $deposit->note = 'Payment URL missing from gateway response';
            $deposit->save();

            return response()->json([
                'status' => false,
                'message' => 'Payment URL not found from gateway.',
                'data' => $json,
            ], 422);
        }

        $deposit->status = 2;
        $deposit->gateway_notify_response = $body;
        $deposit->save();

        createUserNotification(
            $user->id,
            'Recharge Arrival Notice',
            "Your deposit request of amount {$data['amount']} has been created. Please proceed to payment.",
            'deposit_created'
        );

        return response()->json([
            'status' => true,
            'message' => 'Deposit request created successfully.',
            'redirect_url' => $paymentUrl,
            'data' => $json,
        ]);

    } catch (\Throwable $e) {
        Log::error('Deposit Collect API Error', [
            'message' => $e->getMessage(),
            'payload' => $payload,
        ]);

        $deposit->note = $e->getMessage();
        $deposit->save();

        return response()->json([
            'status' => false,
            'message' => 'Payment gateway request failed.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

private function buildSignString(array $data, string $secretKey): string
{
    unset($data['sign']);

    $clean = [];

    foreach ($data as $key => $value) {
        if ($value === null) {
            continue;
        }

        if (is_array($value) || is_object($value)) {
            continue;
        }

        $value = (string) $value;

        if ($value === '') {
            continue;
        }

        $clean[$key] = $value;
    }

    ksort($clean, SORT_STRING);

    $stringA = '';

    foreach ($clean as $key => $value) {
        $stringA .= $key . '=' . $value . '&';
    }

    $stringA = rtrim($stringA, '&');

    return $stringA . '&key=' . $secretKey;
}

private function generateCollectSign(array $data, string $secretKey): string
{
    return strtolower(md5($this->buildSignString($data, $secretKey)));
}

// public function store(Request $request)
// {
//     try {
//         $user = auth()->user();

//         $data = $request->validate([
//             'payment_method_currency' => ['required', 'in:NAGAD,BKASH'],
//             'channel_name' => ['required', 'string', 'max:100'],
//             'amount' => ['required', 'numeric', 'min:100', 'max:25000'],
//         ]);

//         Log::info('Deposit request validated', [
//             'user_ip' => $request->ip(),
//             'user_id' => $user->id,
//             'payment_method_currency' => $data['payment_method_currency'],
//             'channel_name' => $data['channel_name'],
//             'amount' => $data['amount'],
//         ]);

//         $stargoHelper = new StargoHelper();

//         $payMethod = $stargoHelper->payMethodFromUi((string) $data['payment_method_currency']);
//         if ($payMethod === '') {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Invalid payment method',
//             ], 422);
//         }

//         $merOrderNo = $stargoHelper->newMerOrderNo();

//         if ($user->isDemo()) {
//             $user->balance += (float) $data['amount'];
//             $user->save();
        
//             Deposit::create([
//                 'user_id' => $user->id,
//                 'payment_method_currency' => 'BDT', 
//                 'amount' => (float) $data['amount'],
//                 'charge' => 0,
//                 'payable_amount' => (float) $data['amount'],
//                 'trx_id' => $merOrderNo,
//                 'status' => Deposit::STATUS_SUCCESS,
//                 'information' => [
//                     'method' => (string) $data['payment_method_currency'],
//                     'channel' => (string) $data['channel_name'],
//                     'currency' => 'BDT',
//                     'demo' => true,
//                 ],
//                 'note' => 'Demo user deposit auto approved',
//             ]);
        
//             return response()->json([
//                 'status' => true,
//                 'message' => 'Demo deposit added successfully.',
//                 'redirect_url' => route('user.wallet.RechargeHistory'),
//             ]);
//         }

//         $deposit = DB::transaction(function () use ($user, $data, $merOrderNo) {
//             return Deposit::create([
//                 'user_id' => $user->id,
//                 'payment_method_currency' => $data['payment_method_currency'], 
//                 'amount' => $data['amount'],
//                 'charge' => 0,
//                 'payable_amount' => $data['amount'],
//                 'trx_id' => $merOrderNo,
//                 'status' => Deposit::STATUS_PENDING,
//                 'information' => [
//                     'method' => $data['payment_method_currency'],
//                     'channel' => $data['channel_name'],
//                     'currency' => 'BDT', 
//                 ],
//             ]);
//         });

//         $mobile = preg_replace('/[^\d+]/', '', (string) ($user->phone ?? ''));
//         $mobile = trim($mobile);

//         if ($mobile === '') {
//             Log::warning('Deposit blocked due to missing mobile number', [
//                 'user_id' => $user->id,
//                 'deposit_id' => $deposit->id ?? null,
//             ]);

//             return response()->json([
//                 'status' => false,
//                 'message' => 'Mobile number is required',
//             ], 422);
//         }

//         $payload = [
//             'appId' => $stargoHelper->appId(),
//             'merOrderNo' => $merOrderNo,
//             'notifyUrl' => $stargoHelper->notifyUrl(),
//             'currency' => 'BDT',
//             'amount' => (string) $data['amount'],
//             'payMethod' => $payMethod,
//             'extra' => [
//                 'accountName' => (string) ($user->name ?? 'user'),
//                 'mobile' => $mobile,
//                 'email' => (string) ($user->username . '@gmail.com'),
//             ],
//             'attach' => 'deposit',
//         ];
//         Log::info('Stargo Gateway Payload', $payload);
//         $payload['sign'] = $stargoHelper->sign($payload);
//         $url = $stargoHelper->host() . '/api/v2/payment/order/create';
        
//         $response = Http::timeout(15)
//             ->acceptJson()
//             ->withHeaders([
//                 'Content-Type' => 'application/json;charset=utf-8',
//                 'User-Agent' => 'Laravel-StarPago-Client/1.0',
//             ])
//             ->retry(2, 200, throw: false)
//             ->post($url, $payload);
        
//         $rawBody = $response->body();
//         $resp = $response->json();
        
//         Log::info('StarPago raw response', [
//             'url' => $url,
//             'status' => $response->status(),
//             'headers' => $response->headers(),
//             'body' => $rawBody,
//             'json' => $resp,
//         ]);
        
//         if (!is_array($resp)) {
//             $resp = [
//                 'code' => $response->status(),
//                 'msg' => 'Gateway returned non-JSON response',
//                 'raw_body' => $rawBody,
//             ];
//         }

//         $code = (int) ($resp['code'] ?? -1);

//         if ($code !== 0) {
//             Log::error('Payment gateway returned failure', [
//                 'user_id' => $user->id,
//                 'deposit_id' => $deposit->id,
//                 'response_code' => $code,
//                 'response_msg' => $resp['msg'] ?? null,
//                 'response' => $resp,
//             ]);

//             $info = $deposit->information ?? [];
//             $info['create_error'] = $resp;

//             $deposit->update([
//                 'information' => $info,
//                 'status' => Deposit::STATUS_REJECTED,
//             ]);

//             return response()->json([
//                 'status' => false,
//                 'message' => $resp['msg'] ?? 'Create failed',
//                 'resp' => $resp,
//             ], 422);
//         }

//         $orderNo = (string) ($resp['data']['orderNo'] ?? '');
//         $paymentLink = (string) ($resp['data']['params']['paymentLink'] ?? '');

//         $info = $deposit->information ?? [];
//         $info['create_resp'] = $resp;
//         $info['payMethod'] = $payMethod;

//         // $deposit->update([
//         //     'trx_id' => $orderNo ?: $deposit->trx_id,
//         //     'information' => $info,
//         //     'status' => Deposit::STATUS_TO_BE_PAID,
//         // ]);
        
//         $deposit->update([
//             'payment_id' => $orderNo ?: $deposit->payment_id,
//             'information' => $info,
//             'status' => Deposit::STATUS_TO_BE_PAID,
//         ]);
//         if (!empty($paymentLink)) {
//             return response()->json([
//                 'status' => true,
//                 'message' => 'Deposit request created successfully.',
//                 'redirect_url' => $paymentLink,
//             ]);
//         }

//         return response()->json([
//             'status' => true,
//             'message' => 'Deposit request created successfully.',
//             'redirect_url' => route('user.wallet.RechargeHistory'),
//         ]);
//     } catch (Throwable $e) {
//         Log::error('Deposit store exception', [
//             'user_id' => auth()->id(),
//             'message' => $e->getMessage(),
//             'file' => $e->getFile(),
//             'line' => $e->getLine(),
//             'trace' => $e->getTraceAsString(),
//         ]);

//         return response()->json([
//             'status' => false,
//             'message' => 'Something went wrong. Please try again.' . $e,
//         ], 500);
//     }
// }


public function RechargeHistory(Request $request)
{
    $user = auth()->user();

    $status = $request->string('status')->toString();
    $method = strtoupper($request->string('method')->toString());

    $query = Deposit::query()->where('user_id', $user->id);

    if ($status !== '' && $status !== 'all') {
        $statusMap = [
            'pending' => 0,
            'success' => 1,
            'tobepaid' => 2,
            'rejected' => 3,
        ];

        if (array_key_exists($status, $statusMap)) {
            $query->where('status', $statusMap[$status]);
        }
    }

    if ($method !== '' && $method !== 'ALL') {
        $query->where('information->method', $method);
    }

    $deposits = $query
        ->latest('id')
        ->paginate(15)
        ->through(fn ($deposit) => $this->transformDeposit($deposit))
        ->withQueryString();

    return UserSpaPage::render('user/deposit/RechargeHistory', [
        'filters' => [
            'status' => $status ?: 'all',
            'method' => $method ? strtolower($method) : 'all',
        ],
        'methods' => [
            [
                'key' => 'all',
                'label' => 'All',
                'icon_type' => 'svg',
                'icon' => 'icon-all',
            ],
            [
                'key' => 'nagad',
                'label' => 'Nagad',
                'icon_type' => 'image',
                'icon' => '/assets/png/paynagod.png',
            ],
            [
                'key' => 'bkash',
                'label' => 'bKash',
                'icon_type' => 'image',
                'icon' => '/assets/png/paybkash.png',
            ],
            // [
            //     'key' => 'usdt',
            //     'label' => 'USDT',
            //     'icon_type' => 'image',
            //     'icon' => '/assets/png/paytrxtw.png',
            // ],
        ],
        'statuses' => [
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'tobepaid', 'label' => 'To Be Paid'],
            ['key' => 'success', 'label' => 'Complete'],
            ['key' => 'rejected', 'label' => 'Failed'],
            ['key' => 'pending', 'label' => 'Pending'],
        ],
        'deposits' => $deposits,
    ]);
}

protected function transformDeposit(Deposit $deposit): array
{
    $rawInformation = $deposit->information;

    if (is_array($rawInformation)) {
        $information = $rawInformation;
    } elseif (is_object($rawInformation)) {
        $information = (array) $rawInformation;
    } elseif (is_string($rawInformation)) {
        $information = json_decode($rawInformation, true) ?: [];
    } else {
        $information = [];
    }

    return [
        'id' => $deposit->id,
        'amount' => number_format((float) $deposit->amount, 2),
        'charge' => number_format((float) $deposit->charge, 2),
        'payable_amount' => number_format((float) $deposit->payable_amount, 2),
        'payment_method_currency' => $deposit->payment_method_currency,
        'payment_id' => $deposit->payment_id,
        'trx_id' => $deposit->trx_id,
        'status' => $deposit->status,
        'status_label' => $deposit->status_label,
        'status_class' => $deposit->status_class,
        'created_at' => $deposit->created_at?->format('Y-m-d H:i:s'),
        'channel_name' => $information['channel_name'] ?? $deposit->payment_id ?? 'N/A',
        'type_label' => $deposit->payment_method_currency ?: 'N/A',
        'note' => $deposit->note,
    ];
}
}
