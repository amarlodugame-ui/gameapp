<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use Illuminate\Http\Request;
use App\Models\Bet;
use App\Models\GameRound;
use App\Models\Deposit;
use App\Support\WingoRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Transaction;
use Illuminate\Support\Str;

class WingoGameController extends Controller
{
    public function index()
    {
        $periodLookupTitle = " HGZY / DK WIN HACK TELEGRAM CHANNEL(𝗕𝗥𝗔𝗡𝗗 🇧 🇭 🇦 🇮 👑 😎)";
        $userRefcode = auth()->user()->referral_code;
        return UserSpaPage::render('user/WinGo/Index', compact('periodLookupTitle','userRefcode'));
    }

    public function verifyLookupPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $expectedPassword = 'Hagnice@eX';
        if ($expectedPassword === '' || !hash_equals($expectedPassword, (string) $request->password)) {
            return response()->json([
                'message' => 'Wrong password',
            ], 403);
        }

        return response()->json([
            'message' => 'Password verified successfully',
        ]);
    }

    public function resultByPeriod(Request $request)
    {
        $request->validate([
            'period_id' => ['required', 'string'],
            'game_type' => ['nullable', 'in:30s,1m,3m,5m'],
            'lookup_password' => ['required', 'string'],
        ]);

        $expectedPassword = 'Hagnice@eX';

        if ($expectedPassword === '' || !hash_equals($expectedPassword, (string) $request->lookup_password)) {
            return response()->json([
                'message' => 'Please verify password first',
            ], 403);
        }

        $periodInput = trim((string) $request->period_id);

        $row = GameRound::query()
            ->when(
                $request->filled('game_type'),
                fn ($q) => $q->where('game_type', $request->game_type)
            )
            ->where(function ($q) use ($periodInput) {
                $q->where('period_id', $periodInput);

                if (strlen($periodInput) <= 3) {
                    $q->orWhere('period_id', 'like', '%' . $periodInput);
                }
            })
            ->whereNotNull('result_number')
            ->latest('id')
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Result not found',
            ], 404);
        }

        $number = (int) $row->result_number;

        $colors = match (true) {
            $number === 0 => ['red', 'violet'],
            $number === 5 => ['green', 'violet'],
            in_array($number, [1, 3, 7, 9], true) => ['green'],
            default => ['red'],
        };

        $bigsmall = $number >= 5 ? 'big' : 'small';

        return response()->json([
            'result' => [
                'period_id' => (string) $row->period_id,
                'game_type' => $row->game_type,
                'result_number' => $number,
                'bigsmall' => $bigsmall,
                'colors' => $colors,
                'status' => $row->status,
            ],
        ]);
    }

// public function resultByPeriod(Request $request)
// {
//     $request->validate([
//         'period_id' => ['required', 'string'],
//         'game_type' => ['nullable', 'in:30s,1m,3m,5m'],
//     ]);

//     $periodInput = trim((string) $request->period_id);

//     $row = GameRound::query()
//         ->when(
//             $request->filled('game_type'),
//             fn ($q) => $q->where('game_type', $request->game_type)
//         )
//         ->where(function ($q) use ($periodInput) {
//             $q->where('period_id', $periodInput);
//             if (strlen($periodInput) <= 3) {
//                 $q->orWhere('period_id', 'like', '%' . $periodInput);
//             }
//         })
//         // ->whereNotIn('status', ['running', 'queued'])
//         ->whereNotNull('result_number')
//         ->latest('id')
//         ->first();

//         if (!$row) {
//             $number = random_int(0, 9);

//             $colors = match (true) {
//                 $number === 0 => ['red', 'violet'],
//                 $number === 5 => ['green', 'violet'],
//                 in_array($number, [1, 3, 7, 9], true) => ['green'],
//                 default => ['red'],
//             };

//             return response()->json([
//                 'result' => [
//                     'period_id' => $periodInput,
//                     'game_type' => $request->game_type ?: '30s',
//                     'result_number' => $number,
//                     'bigsmall' => $number >= 5 ? 'big' : 'small',
//                     'colors' => $colors,
//                     'status' => 'random',
//                 ],
//             ]);
//         }
    

//     $number = (int) $row->result_number;

//     $colors = match (true) {
//         $number === 0 => ['red', 'violet'],
//         $number === 5 => ['green', 'violet'],
//         in_array($number, [1, 3, 7, 9], true) => ['green'],
//         default => ['red'],
//     };

//     $bigsmall = $number >= 5 ? 'big' : 'small';

//     return response()->json([
//         'result' => [
//             'period_id' => (string) $row->period_id,
//             'game_type' => $row->game_type,
//             'result_number' => $number,
//             'bigsmall' => $bigsmall,
//             'colors' => $colors,
//             'status' => $row->status,
//         ],
//     ]);
// }
    public function state(Request $request)
    {
        $gameType = $request->query('game_type', '30s');

        abort_unless(in_array($gameType, ['30s','1m','3m','5m'], true), 422);

        $round = GameRound::query()
            ->where('game_type', $gameType)
            ->where('status', 'running')
            ->orderByDesc('end_time')
            ->first();

        $history = GameRound::query()
            ->where('game_type', $gameType)
            ->where('status', 'ended')
            ->orderByDesc('end_time')
            ->limit(120)
            ->get()
            ->map(fn ($r) => [
                'period_id' => $r->period_id,
                'number' => $r->result_number,
                'colors' => $r->result_number !== null ? WingoRules::numberToColors($r->result_number) : [],
                'bigsmall' => $r->result_number !== null ? WingoRules::numberToBigSmall($r->result_number) : null,
            ]);
        $myBets = Bet::query()
            ->where('user_id', auth()->id())
            ->where('game_type', $gameType)
            ->latest('id')
            ->limit(120)
            ->with(['round:id,period_id,result_number'])
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'created_at' => $b->created_at?->toISOString(),
                'bet_type' => $b->bet_type,
                'bet_value' => $b->bet_value,
                'amount' =>  (float)$b->amount,
                'qty' => (int)$b->qty,
                'payout' => (float)$b->payout,
                'status' => $b->status,
                'game_type' => $b->game_type,
                'period_id' => $b->round?->period_id,         
                'result_number' => $b->round?->result_number, 
            ]);
        return response()->json([
            'server_time' => now()->toISOString(),
            'fee_rate' => WingoRules::FEE_RATE,
            'game_type' => $gameType,
            'current_round' => $round ? [
                'id' => $round->id,
                'period_id' => $round->period_id,
                'start_time' => $round->start_time?->toISOString(),
                'end_time' => $round->end_time?->toISOString(),
            ] : null,
            'history' => $history,
            'wallet' => [
                 'game_balance' => (float) (auth()->user()?->balance ?? 0),
            ],
            'my_bets' => $myBets,
        ]);
    }


public function placeBet(Request $request)
{
    $data = $request->validate([
        'game_type' => ['required', Rule::in(['30s','1m','3m','5m'])],
        'bet_type' => ['required', Rule::in(['color','number','bigsmall'])],
        'bet_value' => ['required','string','max:10'],
        'amount' => ['required','numeric','min:1'],
        'qty' => ['nullable','integer','min:1'],
    ]);

    $authId = auth()->id();
    // $hasApprovedDeposit = Deposit::where('user_id', $authId)->where('status', 1)->exists();
    // if (!$hasApprovedDeposit) {
    //       return response()->json(['message' => 'Deposit first.'], 409);
    // }
    
    
    return DB::transaction(function () use ($data, $authId) {
        $round = GameRound::query()
            ->where('game_type', $data['game_type'])
            ->where('status', 'running')
            ->orderByDesc('end_time')
            ->lockForUpdate()
            ->firstOrFail();

        $user = \App\Models\User::query()
            ->whereKey($authId)
            ->lockForUpdate()
            ->firstOrFail();

        $amount = (float) $data['amount'];

        $lockAt = $round->end_time->copy()->subSeconds(5);
        if (now()->greaterThanOrEqualTo($lockAt)) {
            return response()->json(['message' => 'Betting is locked for this round.'], 409);
        }

        if ((float)$user->balance < $amount) {
            return response()->json(['message' => 'Balance insufficient.'], 409);
        }

        $user->balance = (float)$user->balance - $amount;
        $user->save();

        $bet = Bet::create([
            'user_id' => $user->id,
            'round_id' => $round->id,
            'bet_type' => $data['bet_type'],
            'bet_value' => $data['bet_value'],
            'amount' => $amount,
            'multiplier' => 0,
            'qty' => (int)($data['qty'] ?? 1),
            'payout' => 0,
            'status' => 'pending',
            'game_type' => $data['game_type'],
        ]);
        
         levelCommission($user, $amount, 'deposit_commission', get_trx());
 requiredTurnoverAmount($user, $amount);
        return response()->json([
            'bet' => [
                'id' => $bet->id,
                'period_id' => $round->period_id,
                'round_id' => $round->id,
                'status' => $bet->status,
            ],
            'wallet' => [
                'game_balance' => (float) $user->fresh()->balance,
            ],
        ], 201);
    });
}
}
