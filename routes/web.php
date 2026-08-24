<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\HomeController;

// VIP level rewards processing route perday cron job 
// Route::get('/process-vip-level-rewards', [HomeController::class, 'processVipLevelRewards']);

Route::get('paw-download', function () {
    return redirect()->away(gs('paw_download'));
})->name('user.pwa.download');

Route::view('/', 'user-spa')->name('user.home')->withoutMiddleware('maintenance');
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Cron\ReferralCommissionSettlementController;

Route::get('/cron/referral-settlement', ReferralCommissionSettlementController::class)->name('admin.cron.referral-settlement');


Route::get('/wingo-runner', function () {
    // dd('sorry');
    Log::info('Cron triggered at ' . now());
    \App\Jobs\EnsureWingoRunning::dispatch();
    return response()->json(['ok' => true]);
});

// Route::get('/wingo-runner', function () {
//     Log::info('Cron triggered'. now());
    
//     ignore_user_abort(true);
//     set_time_limit(0);
//     while (true) {
//         \App\Jobs\EnsureWingoRunning::dispatch();
//         sleep(30);
//     }
// });


use App\Jobs\EnsureWingoRunning;


Route::get('/cron/wingo', function () {
    EnsureWingoRunning::dispatchSync();
    return 'Wingo cron executed';
});
Route::get('maintenance', function () {
    return 'maintenance runneing';
});

Route::get('/cron/queue', function () {
    \Artisan::call('queue:work', [
        '--stop-when-empty' => true
    ]);
    return 'Queue processed';
});



use App\Events\TestBroadcast;

Route::get('/test-broadcast', function () {
    broadcast(new \App\Events\TestBroadcast());
    return 'sent';
});
Route::get('/fire', function () {
    TestBroadcast::dispatch();
    return "Event Fired";
});
Route::get('/wingo/start', function () {
    // \App\Jobs\SettleWingoRound::dispatch();
    \App\Jobs\EnsureWingoRunning::dispatch();
    return 'Started';
});
