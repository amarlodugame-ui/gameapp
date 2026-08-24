<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Lib\StargoHelper;
use App\Models\Withdraw;

class StarpagoPaymentController extends Controller
{
// OKPay payoutCallback functions 
    public function payoutCallback(Request $request)
    {
        // Log::info('================ Payout Callback Start ================');

        $rawBody = $request->getContent();
        $data = $request->all();

        // Log::info('Callback raw request received', [
        //     'raw_body' => $rawBody,
        //     'parsed_data' => $data,
        //     'headers' => $request->headers->all(),
        //     'ip' => $request->ip(),
        // ]);

        try {
            // sign verify
            $trxId = $data['out_trade_no'] ?? null;
            $providerOrderId = $data['trade_no'] ?? $data['orderNo'] ?? null;
            $callbackStatus = strtoupper($data['status'] ?? ($data['trade_status'] ?? ''));

            // Log::info('Callback basic values parsed', [
            //     'trx_id' => $trxId,
            //     'provider_order_id' => $providerOrderId,
            //     'callback_status' => $callbackStatus,
            // ]);

            if (!$trxId) {
                Log::error('Callback missing out_trade_no', [
                    'data' => $data,
                ]);

                return response('missing out_trade_no', 400);
            }

            $withdraw = Withdraw::where('trx_id', $trxId)->first();

            if (!$withdraw) {
                Log::error('Callback withdraw not found', [
                    'trx_id' => $trxId,
                    'data' => $data,
                ]);

                return response('withdraw not found', 404);
            }

            // Log::info('Withdraw found for callback', [
            //     'withdraw_id' => $withdraw->id,
            //     'withdraw_data' => $withdraw->toArray(),
            // ]);

            DB::beginTransaction();

            $oldMeta = is_array($withdraw->meta_field) ? $withdraw->meta_field : json_decode($withdraw->meta_field, true);

            $newMeta = [
                'old_meta' => $oldMeta,
                'callback_raw_body' => $rawBody,
                'callback_data' => $data,
                'callback_headers' => $request->headers->all(),
            ];

            $feedbackMessage = $data['msg'] ?? $data['message'] ?? $data['remark'] ?? $data['reason'] ?? 'Callback received';

            if ((int)$withdraw->status === 2) {
                $withdraw->update([
                    'meta_field' => $newMeta,
                    'feedback' => $feedbackMessage,
                ]);
                // Log::info('Duplicate callback ignored because already completed', [
                //     'withdraw_id' => $withdraw->id,
                //     'trx_id' => $withdraw->trx_id,
                // ]);
                DB::commit();

                // Log::info('================ Payout Callback Duplicate End ================');

                return response('success', 200);
            }

            if (in_array($callbackStatus, ['SUCCESS', 'COMPLETED', 'SUCCESSFUL'])) {
                $withdraw->update([
                    'status' => \App\Models\Withdraw::STATUS_COMPLETED,
                    'response_id' => $providerOrderId,
                    'meta_field' => $newMeta,
                    'feedback' => $feedbackMessage
                ]);
                
            // created trx log
                $user = User::find($withdraw->user_id);
                if ($user) {
                    Transaction::create([
                        'user_id' => $user->id,
                        'amount' => $withdraw->amount,
                        'post_balance' => (float) $user->balance,
                        'charge' => 0,
                        'trx_type' => '-',
                        'details' => 'Withdraw processed: ' . $feedbackMessage,
                        'remark' => 'withdraw',
                        'trx' => $withdraw->trx_id,
                    ]);
                }
            createUserNotification(
                    $withdraw->user_id,
                    'Notification of cash...',
                    'Your withdrawal has been approved and the funds have been transferred. Please check this. The arrival of funds will be delayed on public holidays.',
                    'withdrawal_processed'
                );
    
            } elseif (in_array($callbackStatus, ['PROCESSING', 'PENDING', 'IN_PROGRESS'])) {
                $withdraw->update([
                    'status' => \App\Models\Withdraw::STATUS_PROCESSING,
                    'response_id' => $providerOrderId,
                    'meta_field' => $newMeta,
                    'feedback' => $feedbackMessage,
                ]);
            } else {
                $withdraw->update([
                    'status' => Withdraw::STATUS_REJECTED,
                    'response_id' => $providerOrderId,
                    'meta_field' => $newMeta,
                    'feedback' => $feedbackMessage,
                    'last_error' => $feedbackMessage,
                ]);
                $user = User::find($withdraw->user_id);
                if ($user) {
                    $user->increment('balance', $withdraw->amount);
                }
            }

            DB::commit();

            // Log::info('================ Payout Callback Success End ================');

            return response('success', 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Log::error('Payout callback exception', [
            //     'message' => $e->getMessage(),
            //     'line' => $e->getLine(),
            //     'file' => $e->getFile(),
            //     'trace' => $e->getTraceAsString(),
            //     'raw_body' => $rawBody,
            //     'data' => $data,
            // ]);

            // Log::info('================ Payout Callback Exception End ================');

            return response('error', 500);
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

        // Log::info('Sign string generated', [
        //     'sign_string' => $signString,
        // ]);

        return md5($signString);
    }
    private function verifyCallbackSign(array $data): bool
    {
        $receivedSign = $data['sign'] ?? '';
        $generatedSign = $this->makeSign($data);

        Log::info('Callback sign verification check', [
            'received_sign' => $receivedSign,
            'generated_sign' => $generatedSign,
            'match' => strtolower($receivedSign) === strtolower($generatedSign),
        ]);

        return strtolower($receivedSign) === strtolower($generatedSign);
    }



/// OKPay Deposit success Notification function
public function callback(Request $request)
{
    $raw = $request->getContent();
    $data = $request->all();

    if (empty($data) && !empty($raw)) {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $data = $decoded;
        } else {
            parse_str($raw, $data);
        }
    }

    Log::info('Deposit Callback Received', [
        'ip' => $request->ip(),
        'data' => $data,
        'raw' => $raw,
    ]);

    $secretKey = env('PAYIN_SECRET_KEY');

    $outTradeNo = trim((string) ($data['out_trade_no'] ?? ''));
    $status     = trim((string) ($data['status'] ?? ''));
    $money      = (float) ($data['money'] ?? 0);
    $payMoney   = (float) ($data['pay_money'] ?? 0);
    $realMoney  = (float) ($data['real_money'] ?? 0);
    $attach     = trim((string) ($data['attach'] ?? ''));
    $sign       = trim((string) ($data['sign'] ?? ''));

    if (!$outTradeNo) {
        Log::warning('Callback missing out_trade_no', ['data' => $data]);
        return response('fail', 400);
    }



    DB::beginTransaction();

    try {
        $deposit = Deposit::where('payment_id', $outTradeNo)->lockForUpdate()->first();

        if (!$deposit && $attach) {
            $deposit = Deposit::where('id', $attach)
                ->lockForUpdate()
                ->first();
        }

        if (!$deposit) {
            DB::rollBack();

            Log::warning('Deposit not found', [
                'out_trade_no' => $outTradeNo,
                'attach' => $attach,
            ]);
            return response('ok', 200);
        }

        $deposit->gateway_callback_response = json_encode([
            'raw' => $raw,
            'parsed' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $deposit->note = 'Gateway callback received';
        $deposit->save();

        // already success
        if ((int) $deposit->status === 1) {
            DB::commit();

            Log::info('Duplicate callback ignored', [
                'deposit_id' => $deposit->id,
                'out_trade_no' => $outTradeNo,
            ]);

            return response('ok', 200);
        }

        // not successful status
        if ($status !== '1') {
            $deposit->note = 'Callback received but status not success: ' . $status;
            $deposit->save();

            DB::commit();
            return response('ok', 200);
        }

        $paidAmount = $payMoney > 0 ? $payMoney : $money;
        $creditAmount = $paidAmount;

        if (round($paidAmount, 2) !== round((float) $deposit->amount, 2)) {
            $deposit->note = 'Amount mismatch';
            $deposit->save();

            DB::commit();

            Log::warning('Amount mismatch', [
                'deposit_id' => $deposit->id,
                'out_trade_no' => $outTradeNo,
                'order_amount' => (float) $deposit->amount,
                'paid_amount' => $paidAmount,
                'real_money' => $realMoney,
            ]);

            return response('fail', 200);
        }

        $user = User::where('id', $deposit->user_id)
            ->lockForUpdate()
            ->first();

        if (!$user) {
            DB::rollBack();

            Log::warning('User not found', [
                'deposit_id' => $deposit->id,
                'user_id' => $deposit->user_id,
            ]);

            return response('fail', 404);
        }

        $user->balance = round((float) $user->balance + $creditAmount, 2);
        $user->turnover_required = round((float) $user->turnover_required + $creditAmount, 2);
        
        Log::info('Before save', [
            'user_id' => $user->id,
            'credit_amount' => $creditAmount,
            'balance_before_save' => $user->balance,
            'turnover_before_save' => $user->turnover_required,
        ]);



        $user->save();
        
        // levelCommission($user, $creditAmount, 'deposit_commission', $outTradeNo);
        // selfDepositCommission($user, $creditAmount, $outTradeNo);
        depositcommissionCridet($user, $creditAmount, $deposit->information->method ?? null);


        $deposit->status = 1;
        $deposit->payable_amount = $paidAmount;
        $deposit->note = 'Deposit successful via callback';
        $deposit->save();


        // create transaction log
        $trx = new Transaction();
        $trx->user_id = $user->id;
        $trx->amount = $creditAmount;
        $trx->post_balance = (float) $user->balance;
        $trx->charge = 0;
        $trx->trx_type = '+';
        $trx->details = 'Deposit successful via callback';
        $trx->remark = 'deposit';
        $trx->trx = $outTradeNo;
        $trx->save();  
        
        createUserNotification($user->id,'Recharge Arrival Notice',"You have received a recharge of {$creditAmount}",'deposit');
        DB::commit();

        Log::info('Deposit callback success', [
            'deposit_id' => $deposit->id,
            'out_trade_no' => $outTradeNo,
            'paid_amount' => $paidAmount,
            'credit_amount' => $creditAmount,
            'user_id' => $user->id,
        ]);

        return response('success', 200);
    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Deposit callback exception', [
            'message' => $e->getMessage(),
            'out_trade_no' => $outTradeNo,
            'trace' => $e->getTraceAsString(),
        ]);

        return response('fail', 500);
    }
}
private function generateCollectSign(array $data, string $secretKey): string
{
    $clean = [];

    foreach ($data as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $clean[$key] = $value;
    }

    ksort($clean);

    $signString = urldecode(http_build_query($clean)) . '&key=' . $secretKey;

    return md5($signString);
}
    
    
    
    
    
    
    
    
    /// SterGO Deposit Success NOtify FUnction
    public function notify(Request $request)
    {
        $traceId = (string) Str::uuid();
        $payload = $request->all();
        // Log::info('starpago.notify.hit', [ 'trace_id' => $traceId, 'ip' => $request->ip(), 'ua' => (string) $request->userAgent(), 'path' => $request->path(), 'merOrderNo' => (string) ($payload['merOrderNo'] ?? ''), 'orderNo' => (string) ($payload['orderNo'] ?? ''), 'orderStatus' => $payload['orderStatus'] ?? null, 'amount' => $payload['amount'] ?? null, 'realAmount' => $payload['realAmount'] ?? null,]);
    
        $stargoHelper = new StargoHelper();

        if (!$stargoHelper->verify($payload)) {
            Log::warning('starpago.notify.invalid_sign', ['trace_id' => $traceId,'merOrderNo' => (string) ($payload['merOrderNo'] ?? ''),'orderNo' => (string) ($payload['orderNo'] ?? ''),'payload' => $payload,]);
            return response()->json(['code' => 1, 'msg' => 'invalid sign'], 400);
        }
    
        $merOrderNo = (string) ($payload['merOrderNo'] ?? '');
        $orderNo = (string) ($payload['orderNo'] ?? '');
        $orderStatus = (int) ($payload['orderStatus'] ?? 0);
    
        $paid = $payload['realAmount'] ?? $payload['amount'] ?? null;
        $paid = is_numeric($paid) ? (float) $paid : 0.0;
    
        try {
            $this->finalizeIfSuccess( $merOrderNo, $orderNo, $orderStatus, $paid, ['notify_data' => $payload, 'trace_id' => $traceId]);
            // Log::info('starpago.notify.done', ['trace_id' => $traceId,'merOrderNo' => $merOrderNo,'orderNo' => $orderNo,'orderStatus' => $orderStatus,'paid' => $paid,]);
            return response()->json(['code' => 0, 'msg' => 'success']);
        } catch (\Throwable $e) {
            Log::error('starpago.notify.exception', ['trace_id' => $traceId,'merOrderNo' => $merOrderNo,'orderNo' => $orderNo,'orderStatus' => $orderStatus,'paid' => $paid,'error' => $e->getMessage(),'file' => $e->getFile(),'line' => $e->getLine(),]);
            return response()->json(['code' => 1, 'msg' => 'server error'], 500);
        }
    }
    
    private function finalizeIfSuccess(string $merOrderNo, string $orderNo, int $orderStatus, float $paid, array $infoPatch): void
    {
        // Log::info('starpago.finalize.enter', [
        //     'trace_id' => (string) ($infoPatch['trace_id'] ?? ''),
        //     'merOrderNo' => $merOrderNo,
        //     'orderNo' => $orderNo,
        //     'orderStatus' => $orderStatus,
        //     'paid' => $paid,
        // ]);
    
        if ($merOrderNo === '' || $paid <= 0) {
            Log::warning('starpago.finalize.skip_invalid_input', [
                'trace_id' => (string) ($infoPatch['trace_id'] ?? ''),
                'merOrderNo' => $merOrderNo,
                'paid' => $paid,
            ]);
            return;
        }
    
        DB::transaction(function () use ($merOrderNo, $orderNo, $orderStatus, $paid, $infoPatch) {
            $traceId = (string) ($infoPatch['trace_id'] ?? '');
    
            $deposit = Deposit::where('trx_id', $merOrderNo)->lockForUpdate()->first();
            if (!$deposit) {
                Log::warning('starpago.finalize.deposit_not_found', [
                    'trace_id' => $traceId,
                    'merOrderNo' => $merOrderNo,
                ]);
                return;
            }
            $info = $deposit->information ?? [];
    
            foreach ($infoPatch as $k => $v) {
                $info[$k] = $v;
            }
    
            $deposit->update([
                'information' => $info,
                'payment_id' => $orderNo ?: $deposit->payment_id,
            ]);
    
            if ((int) $deposit->status === Deposit::STATUS_SUCCESS) {
                Log::info('starpago.finalize.already_processed', [
                    'trace_id' => $traceId,
                    'deposit_id' => $deposit->id,
                    'merOrderNo' => $merOrderNo,
                    'trx_id' => $deposit->trx_id,
                ]);
                return;
            }
    
            if (!in_array($orderStatus, [2, 3], true)) {
                Log::info('starpago.finalize.not_success_status', [
                    'trace_id' => $traceId,
                    'deposit_id' => $deposit->id,
                    'merOrderNo' => $merOrderNo,
                    'orderStatus' => $orderStatus,
                ]);
                return;
            }
    
            $user = User::where('id', $deposit->user_id)->lockForUpdate()->first();
            if (!$user) {
                Log::error('starpago.finalize.user_not_found', [
                    'trace_id' => $traceId,
                    'deposit_id' => $deposit->id,
                    'user_id' => $deposit->user_id,
                ]);
                return;
            }
    
            $before = (float) $user->balance;
            $user->increment('balance', $paid);
            $user->increment('turnover_required', $paid);
            $after = $before + $paid;
    
            $deposit->update([
                'status' => Deposit::STATUS_SUCCESS,
                'payable_amount' => $paid,
                'note' => 'Deposit approved',
            ]);
    
            $trx = Transaction::create([
                'user_id' => $user->id,
                'amount' => $paid,
                'balance' => $before,
                'charge' => 0,
                'trx_type' => '+',
                'details' => 'Starpago Deposit',
                'remarks' => 'deposit',
                'trx' => $deposit->trx_id ?: $deposit->payment_id,
            ]);
    
            // levelCommission($user, $paid, 'deposit_commission', $trx->trx);
            // selfDepositCommission($user, $paid, $trx->trx);
        });
    }

}
