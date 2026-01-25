<?php

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

use App\Console\Commands\FetchBillbeeOrders;
use App\Console\Commands\FetchBillbeeProducts;
use App\Console\Commands\GenerateStats;
use Spatie\Backup\Commands\BackupCommand;

Schedule::timezone('Europe/Berlin')->group(function () {
    Schedule::command(FetchBillbeeProducts::class)->dailyAt('2:00');
    Schedule::command(GenerateStats::class)->dailyAt('2:30');
    Schedule::command(BackupCommand::class)->dailyAt('3:00');

    Schedule::command(FetchBillbeeOrders::class)->dailyAt('2:15');
    Schedule::command(FetchBillbeeOrders::class, [
        '--after' => now()->subMinutes(15)->toIso8601String(),
    ])->everyFifteenMinutes();
});
