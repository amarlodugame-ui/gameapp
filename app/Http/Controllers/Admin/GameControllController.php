<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameControl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GameControllController extends Controller
{
    public function index()
    {
        $pageTitle = "Game Control";

        $types = ['30s', '1m', '3m', '5m'];

        $controls = GameControl::query()
            ->whereIn('game_type', $types)
            ->get()
            ->keyBy('game_type');

        $rows = collect($types)->map(function ($t) use ($controls) {
            $c = $controls->get($t);

            return [
                'game_type' => $t,
                'mode' => $c?->mode ?? 'random',
                'manual_number' => $c?->manual_number,
                'profit_margin' => (float)($c?->profit_margin ?? 0.00),
                'updated_at' => $c?->updated_at,
            ];
        });

        return view('admin.controll.index', compact('pageTitle', 'rows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'game_type' => ['required', Rule::in(['30s','1m','3m','5m'])],
            'mode' => ['required', Rule::in(['random','profit_control','manual'])],
            'manual_number' => ['nullable', 'integer', 'min:0', 'max:9'],
            'profit_margin' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if ($data['mode'] !== 'manual') {
            $data['manual_number'] = null;
        }

        GameControl::query()->updateOrCreate(
            ['game_type' => $data['game_type']],
            [
                'mode' => $data['mode'],
                'manual_number' => $data['manual_number'],
                'profit_margin' => (float)$data['profit_margin'],
            ]
        );
        $notify[] = ['success', 'Game control updated successfully'];
        return redirect()->route('admin.game_control.index')->withNotify($notify);
    }
}