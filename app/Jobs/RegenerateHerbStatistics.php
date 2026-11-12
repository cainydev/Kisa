<?php

namespace App\Jobs;

use App\Services\HerbStatisticsService;
use App\Support\Stats\HerbStatsRegeneration;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Manually triggered rebuild of the herb stats behind the reorder table — the
 * same work the nightly stats:generate run does for herbs.
 */
class RegenerateHerbStatistics implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $uniqueFor = 600;

    public int $tries = 1;

    public function handle(): void
    {
        HerbStatisticsService::generateAll();
    }

    /**
     * Clear a run that never started, e.g. when the job failed before handle().
     */
    public function failed(?Throwable $exception): void
    {
        HerbStatsRegeneration::finish();
    }
}
