<?php

namespace App\Support\Stats;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks a running herb stats regeneration in the cache so the reorder table
 * can show its progress. The entry expires on its own when a worker dies
 * mid-run, which brings the "Neu berechnen" button back instead of leaving a
 * progress bar stuck forever.
 */
class HerbStatsRegeneration
{
    public const string CACHE_KEY = 'stats:herbs:regeneration';

    /**
     * Seconds without a progress update before a run is considered dead.
     */
    public const int STALE_AFTER_SECONDS = 300;

    /**
     * Mark a run as requested but not yet picked up by a worker.
     */
    public static function queue(): void
    {
        static::put(processed: 0, total: 0);
    }

    public static function start(int $total): void
    {
        static::put(processed: 0, total: $total);
    }

    public static function advance(int $by = 1): void
    {
        $current = static::current();

        if ($current === null) {
            return;
        }

        static::put(processed: min($current['processed'] + $by, $current['total']), total: $current['total']);
    }

    public static function finish(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function isRunning(): bool
    {
        return static::current() !== null;
    }

    /**
     * @return array{processed: int, total: int}|null
     */
    public static function current(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * Completion in percent, or null while the run is still waiting for a worker.
     */
    public static function percentage(): ?int
    {
        $current = static::current();

        if ($current === null || $current['total'] === 0) {
            return null;
        }

        return (int) floor($current['processed'] / $current['total'] * 100);
    }

    protected static function put(int $processed, int $total): void
    {
        Cache::put(self::CACHE_KEY, ['processed' => $processed, 'total' => $total], self::STALE_AFTER_SECONDS);
    }
}
