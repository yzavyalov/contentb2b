<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Merchant Billing Scheduler
|--------------------------------------------------------------------------
|
| Process active subscription and hybrid billing plans.
|
| The billing services are idempotent, therefore running this command
| every hour is safe:
|
| - existing billing periods are reused;
| - monthly fees cannot be charged twice;
| - failed charges can be retried on the next scheduler run.
|
*/

Schedule::command('billing:process')
    ->hourly()
    ->withoutOverlapping();


/*
|--------------------------------------------------------------------------
| Finished Markets Scheduler
|--------------------------------------------------------------------------
|
| Every 15 minutes find published markets whose finish time has passed.
|
| The command atomically moves them from Published to Resolving,
| starts AI resolution analysis and notifies merchants that received
| the market.
|
| AI only prepares a recommendation. Final resolution is performed
| manually by Content Supervisor or Super Admin.
|
*/

Schedule::command('bets:process-finished')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
