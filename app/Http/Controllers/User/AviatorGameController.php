<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Responses\UserSpaPage;
use App\Services\AviatorRoundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use App\Models\Transaction;


use App\Models\AviatorBet;





class AviatorGameController extends Controller
{
    public function __construct(
        protected AviatorRoundService $aviatorRoundService
    ) {}

    public function index(Request $request): JsonResponse|Response
    {
        $this->aviatorRoundService->kickEngine();

        return UserSpaPage::render('user/Aviator/Index', [
            'initialState' => $this->aviatorRoundService->buildState($request->user()),
            'userId' => (int) $request->user()->id,
        ]);
    }

    public function history(Request $request)
    {
        $perPage = 20;

        $bets = AviatorBet::query()
            ->with([
                'round:id,round_no,crash_multiplier,betting_starts_at,flying_starts_at,crashed_at,finished_at,settled_at'
            ])
            ->where('user_id', auth()->id())
            ->where('is_demo', 0)
            ->latest('placed_at')
            ->paginate($perPage)
            ->through(function ($bet) {
                $multiplier = $bet->cashout_multiplier ?? $bet->round?->crash_multiplier ?? 0;
                $cashoutAmount = $bet->cashout_amount ?? 0;

                return [
                    'id' => $bet->id,
                    'slot' => $bet->slot,
                    'amount' => number_format((float) $bet->amount, 2, '.', ''),
                    'status' => $bet->status,
                    'cashout_multiplier' => $multiplier ? number_format((float) $multiplier, 2, '.', '') : null,
                    'cashout_amount' => number_format((float) $cashoutAmount, 2, '.', ''),
                    'placed_at' => optional($bet->placed_at)->format('Y-m-d H:i:s'),
                    'cashed_out_at' => optional($bet->cashed_out_at)->format('Y-m-d H:i:s'),
                ];
            });

        if ($request->expectsJson() && $request->header('X-User-Spa') !== '1') {
            return response()->json($bets);
        }

        return UserSpaPage::render('user/Aviator/History', [
            'initialBets' => $bets->items(),
            'pagination' => [
                'current_page' => $bets->currentPage(),
                'last_page' => $bets->lastPage(),
                'next_page_url' => $bets->nextPageUrl(),
            ],
        ]);
    }


    public function state(Request $request)
    {
        $this->aviatorRoundService->kickEngine();

        return response()->json(
            $this->aviatorRoundService->buildState($request->user())
        );
    }

    public function placeBet(Request $request)
    {
        $data = $request->validate([
            'slot' => ['required', 'integer', 'in:1,2'],
            'amount' => ['required', 'numeric', 'min:1'],
            'auto_cashout_enabled' => ['nullable', 'boolean'],
            'auto_cashout_multiplier' => ['nullable', 'numeric', 'min:2.00'],
        ]);

        $result = $this->aviatorRoundService->placeOrQueueBet(
            user: $request->user(),
            slot: (int) $data['slot'],
            amount: (float) $data['amount'],
            autoCashoutEnabled: (bool) ($data['auto_cashout_enabled'] ?? false),
            autoCashoutMultiplier: isset($data['auto_cashout_multiplier'])
                ? (float) $data['auto_cashout_multiplier']
                : null,
        );
        

        
        
        return response()->json([
            ...$this->aviatorRoundService->buildState($request->user()),
            'bet_action' => $result,
        ]);
    }

    public function cancelBet(Request $request)
    {
        $data = $request->validate([
            'slot' => ['required', 'integer', 'in:1,2'],
        ]);

        $this->aviatorRoundService->cancelBet(
            $request->user(),
            (int) $data['slot']
        );

        return response()->json(
            $this->aviatorRoundService->buildState($request->user())
        );
    }

    public function cashOut(Request $request)
    {
        $data = $request->validate([
            'bet_id' => ['required', 'integer'],
        ]);

        $this->aviatorRoundService->cashOut(
            $request->user(),
            (int) $data['bet_id']
        );

        return response()->json(
            $this->aviatorRoundService->buildState($request->user())
        );
    }
}
