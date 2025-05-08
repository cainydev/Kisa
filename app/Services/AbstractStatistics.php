<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class AbstractStatisticsService
{
    /**
     * Cache durations in seconds
     */
    const int CACHE_SHORT = 3600;   // 1 hour
    const int CACHE_MEDIUM = 86400; // 1 day
    const int CACHE_LONG = 604800;  // 1 week

    const string PER_DAY = '1 day';
    const string PER_WEEK = '1 week';
    const string PER_MONTH = '1 month';
    const string PER_YEAR = '1 year';

    /**
     * Get a CarbonPeriod for the past x days
     *
     * @param int $days Number of days to go back
     * @return CarbonPeriod
     */
    public static function pastDays(int $days): CarbonPeriod
    {
        return self::getPeriod(self::PER_DAY, now()->subDays($days - 1)->startOfDay(), now()->endOfDay());
    }

    /**
     * Get a CarbonPeriod for the given time period (going backwards in time)
     *
     * @param CarbonInterval|string $interval When passed as a string, it will be parsed to a CarbonInterval
     * @param Carbon|string|float $start Start date (default: 'now')
     * @param Carbon|string|float $end End date (default: INF)
     * @return CarbonPeriod
     */
    public static function getPeriod(CarbonInterval|string $interval, Carbon|string|float $start = -INF, Carbon|string|float $end = 'now'): CarbonPeriod
    {
        $interval = ($interval instanceof CarbonInterval)
            ? $interval->optimize()
            : CarbonInterval::make($interval)->optimize();

        if ($interval->totalMicroseconds === 0.0) {
            throw new \InvalidArgumentException("Interval cannot be zero.");
        }

        $start = Carbon::create($start);
        $end = Carbon::create($end);

        if ($start->gt($end)) [$start, $end] = [$end, $start];

        return CarbonPeriod::create($start, CarbonInterval::make($interval), $end);
    }

    /**
     * Get a CarbonPeriod for the past x weeks
     *
     * @param int $weeks Number of weeks to go back
     * @return CarbonPeriod
     */
    public static function pastWeeks(int $weeks): CarbonPeriod
    {
        return self::getPeriod(self::PER_WEEK, now()->subWeeks($weeks - 1)->startOfWeek(), now()->endOfWeek());
    }

    /**
     * Get a CarbonPeriod for the past x months
     *
     * @param int $months Number of months to go back
     * @return CarbonPeriod
     */
    public static function pastMonths(int $months): CarbonPeriod
    {
        return self::getPeriod(self::PER_MONTH, now()->subMonths($months - 1)->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Get a CarbonPeriod for the past x years
     *
     * @param int $years Number of years to go back
     * @return CarbonPeriod
     */
    public static function pastYears(int $years): CarbonPeriod
    {
        return self::getPeriod(self::PER_YEAR, now()->subYears($years - 1)->startOfYear(), now()->endOfYear());
    }

    /**
     * Invalidate cache for a specific key
     *
     * @param string|array $key
     * @return bool
     */
    public static function invalidate(string|array $key): bool
    {
        if (is_array($key)) {
            return collect($key)
                ->map(fn($k) => Cache::forget($k))
                ->every(fn($el) => $el);
        }

        return Cache::forget($key);
    }

    /**
     * Generate dataset for the specified period with zero values for missing dates
     *
     * @param Closure(Carbon $start, Carbon $end): mixed $getData Function to get data. Should return null if no data is available.
     * @param CarbonPeriod $period Period to cover.
     */
    public static function generateDataset(Closure $getData, CarbonPeriod $period): Collection
    {
        $data = collect();

        foreach ($period as $key => $start) {
            $end = $start->copy()->add($period->getDateInterval());
            $value = $getData($start, $end, $key);

            if (empty($value)) break;

            $data[$start->toIso8601String()] = $value;
        }

        return $data;
    }

    /**
     * Get warning level based on days remaining
     *
     * @param int|null $daysRemaining
     * @return string 'critical', 'warning', 'normal', or 'unknown'
     */
    public static function getWarningLevel(?int $daysRemaining): string
    {
        if ($daysRemaining === null) {
            return 'unknown';
        }

        if ($daysRemaining <= 7) {
            return 'critical';
        }

        if ($daysRemaining <= 14) {
            return 'warning';
        }

        return 'normal';
    }
}
