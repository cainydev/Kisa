<?php

use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

/*
 * Retention for the off-server backup disk.
 *
 * Spatie applies one cleanup strategy per run, so keeping "7 days locally, years
 * off-server" needs two configs and two scheduled cleanups:
 *
 *   backup:clean                        → config/backup.php    → local, 7 days
 *   backup:clean --config=backup-s3     → this file            → backups, full curve
 *
 * Only the keys that differ from config/backup.php are listed; Spatie merges
 * this over the package defaults, and everything unspecified (notifications,
 * source, encryption) is inherited from there.
 */

return [
    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),

        'destination' => [
            'disks' => [
                'backups',
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 30,
            'keep_weekly_backups_for_weeks' => 12,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 10,

            /*
             * Unlimited on purpose: this cap deletes oldest-first regardless of
             * which tier protected a backup, so any finite value silently eats
             * the yearly snapshots this disk exists to hold. Hetzner includes
             * 1 TB; the archive is ~18 GB.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
