<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AviatorSetting;
use App\Services\AviatorRoundService;
use Illuminate\Http\Request;

class AviatorSettingsController extends Controller
{
    public function index()
    {
        $pageTitle = 'Aviator Settings';
        return view('admin.aviator_settings.index', compact('pageTitle'));
    }

    public function show()
    {
        return response()->json(
            AviatorSetting::query()->firstOrFail()
        );
    }

    public function update(Request $request, AviatorRoundService $service)
    {
        // $manualRule = app()->environment(['local', 'testing', 'staging'])
        //     ? []
        //     : ['prohibited'];

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'betting_duration_ms' => ['required', 'integer', 'min:1000', 'max:30000'],
            'result_duration_ms' => ['required', 'integer', 'min:1000', 'max:15000'],
            'slow_factor' => ['required', 'numeric', 'min:0.50', 'max:10.00'],
            'max_bet_amount' => ['required', 'integer', 'min:1'],
            'max_payout_amount' => ['required', 'integer', 'min:1'],
            'house_edge_bps' => ['required', 'integer', 'min:0', 'max:2000'],
            'max_crash_multiplier' => ['required', 'numeric', 'min:2.00', 'max:1000.00'],
            'sound_enabled_by_default' => ['required', 'boolean'],
            'public_heartbeat_ms' => ['required', 'integer', 'min:100', 'max:1000'],
            'engine_sleep_ms' => ['required', 'integer', 'min:50', 'max:500'],
            'manual_next_crash_multiplier' => 'nullable', 'numeric', 'min:1.01', 'max:1000',
        ]);

        $settings = AviatorSetting::query()->firstOrFail();
        $settings->fill($data);
        $settings->save();

        $service->refreshSettingsCache();

        return response()->json([
            'message' => 'Aviator settings updated successfully.',
            'settings' => $settings->fresh(),
        ]);
    }
}