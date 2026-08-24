<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\EnsureWingoRunning;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new EnsureWingoRunning)->everyMinute()->withoutOverlapping();





// use App\Jobs\EnsureWingoRunning;
// use Illuminate\Support\Facades\Schedule;
// Schedule::job(new EnsureWingoRunning)->everyMinute()->withoutOverlapping();


// use App\Jobs\Aviator\EnsureAviatorRunning;
// Schedule::job(new EnsureAviatorRunning)->everyMinute()->withoutOverlapping();

// Schedule::job(new EnsureAviatorRunning)->everyTenSeconds()->withoutOverlapping();

// Schedule::job(new EnsureWingoRunning)->everySecond()->withoutOverlapping();