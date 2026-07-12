<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronización con Sistema Lumen cada 6 horas
Schedule::command('lumen:sync')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Backup de cotizaciones diarias
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('quotes')
        ->where('created_at', '<', now()->subDays(90))
        ->where('status', 'closed')
        ->update(['archived_at' => now()]);
})->daily();