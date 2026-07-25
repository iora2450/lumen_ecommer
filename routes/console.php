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

// Pull HTTP desde ERP (configurable por .env)
if ((bool) env('ERP_PULL_SCHEDULE_ENABLED', false)) {
    Schedule::command('erp:pull --type=full')
        ->dailyAt(env('ERP_PULL_FULL_AT', '03:00'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();

    Schedule::command('erp:pull --type=inventory')
        ->cron(env('ERP_PULL_INVENTORY_CRON', '*/15 * * * *'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();

    Schedule::command('erp:pull --type=prices')
        ->cron(env('ERP_PULL_PRICES_CRON', '0 * * * *'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground();
}

// Backup de cotizaciones diarias
Schedule::call(function () {
    \Illuminate\Support\Facades\DB::table('quotes')
        ->where('created_at', '<', now()->subDays(90))
        ->where('status', 'closed')
        ->update(['archived_at' => now()]);
})->daily();