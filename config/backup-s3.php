<?php

use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;

/*
 * The off-server backup destination.
 *
 * This needs its own config file rather than a second entry in
 * config/backup.php's `disks`, because both the destination path
 * (`backup.name`) and the retention strategy are per-config, not per-disk:
 * BackupDestinationFactory applies one name to every disk, and CleanupJob
 * applies one strategy to every destination.
 *
 * So each disk gets its own run:
 *
 *   backup:run                          → config/backup.php  → local, 7 days
 *   backup:run   --config=backup-s3     → this file          → S3, full curve
 *   backup:clean                        → config/backup.php
 *   backup:clean --config=backup-s3     → this file
 *
 * The cost is that the database is dumped and the files zipped twice per night.
 * Only keys that differ from config/backup.php are listed; Spatie merges this
 * over the package defaults, so notifications, source paths and encryption are
 * inherited.
 */

return [
    'backup' => [
        'name' => '',

        // Repeated from config/backup.php: Config::fromArray merges only at the
        // top level of `backup`, so anything not restated here falls back to the
        // package default (false).
        'verify_backup' => true,

        /*
         * `destination` must be spelled out in full. Config::fromArray merges
         * only at the top level of `backup`, so a partial array here replaces
         * the package's whole destination block — silently dropping
         * `filename_prefix`, which cleanup and monitoring use to recognise a
         * backup.
         */
        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,

            // Empty for the same reason as config/backup.php — a prefix breaks
            // the filename date parse, and on object storage the fallback dates
            // every archive by its upload time.
            'filename_prefix' => '',

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
             * 1 TB across all buckets; the full curve is ~7 GB.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => null,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
