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
use Spatie\Backup\Commands\CleanupCommand;
use Spatie\Backup\Commands\MonitorCommand;

Schedule::timezone('Europe/Berlin')->group(function () {
    Schedule::command(FetchBillbeeProducts::class)->dailyAt('2:00');
    Schedule::command(GenerateStats::class)->dailyAt('2:30');
    // Local keeps the last 7 days for a quick restore; the off-server copy on
    // object storage keeps the full multi-year curve. Each disk needs its own
    // run because the destination path and retention strategy are per-config
    // rather than per-disk — see config/backup-s3.php. Staggered so the two
    // dumps don't contend for disk and memory.
    Schedule::command(BackupCommand::class)->dailyAt('3:00');
    Schedule::command(BackupCommand::class, [
        '--config' => 'backup-s3',
    ])->dailyAt('3:20');

    // Applies the retention tiers. Without this the backups accumulate forever
    // — they did, for 13 months, until the disk filled and backup:run started
    // failing silently.
    Schedule::command(CleanupCommand::class)->dailyAt('4:00');
    Schedule::command(CleanupCommand::class, [
        '--config' => 'backup-s3',
    ])->dailyAt('4:20');

    // Fails loudly when the newest backup is too old or storage is too big,
    // which is the only thing that catches a backup that stopped running.
    Schedule::command(MonitorCommand::class)->dailyAt('8:00');

    Schedule::command(FetchBillbeeOrders::class)->dailyAt('2:15');
    Schedule::command(FetchBillbeeOrders::class, [
        '--since-minutes' => 15,
    ])->everyFifteenMinutes();
});
