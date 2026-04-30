<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command("notify:financial")->dailyAt("08:00");

Schedule::command('einvoice:consolidated')
    ->monthlyOn(1, '02:00')   // 1st of every month at 2am
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('exchange-rates:sync')
    ->dailyAt('08:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('exchange-rates:sync failed — manual rates may be required.');
    })
    ->appendOutputTo(storage_path('logs/exchange-rates.log'));
 
