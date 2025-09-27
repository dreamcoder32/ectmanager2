<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automated salary processing
// Run on the 1st of every month at 9:00 AM
Schedule::command('salary:process-monthly')
    ->monthlyOn(1, '09:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure('admin@delivery.com');

// Schedule automated commission processing
// Run on the 2nd of every month at 10:00 AM (after salary processing)
Schedule::command('commission:process-monthly')
    ->monthlyOn(2, '10:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->emailOutputOnFailure('admin@delivery.com');
