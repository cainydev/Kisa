<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use function now;

abstract class AbstractStatistics
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
     * Generate all statistics for the given entity
     *
     * @return void
     */
    abstract public static function generateAll(): void;


    /**
     * Generate statistics for the given instances
     *
     * @param Collection $models
     * @return void
     */
    abstract public static function generate(Collection $models): void;


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
     * Get a daily CarbonPeriod since a given date
     *
     * @param Carbon $since Date to start from (start of day is used)
     * @return CarbonPeriod
     */
    public static function days(Carbon $since): CarbonPeriod
    {
        return self::getPeriod(self::PER_DAY, $since->startOfDay(), now()->endOfDay());
    }

    /**
     * Get a weekly CarbonPeriod since a given date
     *
     * @param Carbon $since Date to start from (start of week is used)
     * @return CarbonPeriod
     */
    public static function weeks(Carbon $since): CarbonPeriod
    {
        return self::getPeriod(self::PER_WEEK, $since->startOfWeek(), now()->endOfWeek());
    }

    /**
     * Get a monthly CarbonPeriod since a given date
     *
     * @param Carbon $since Date to start from (start of month is used)
     * @return CarbonPeriod
     */
    public static function months(Carbon $since): CarbonPeriod
    {
        return self::getPeriod(self::PER_MONTH, $since->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Get a yearly CarbonPeriod since a given date
     *
     * @param Carbon $since Date to start from (start of year is used)
     * @return CarbonPeriod
     */
    public static function years(Carbon $since): CarbonPeriod
    {
        return self::getPeriod(self::PER_YEAR, $since->startOfYear(), now()->endOfYear());
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
     * Get warning level based on days remaining
     *
     * @param int|null $daysRemaining Number of days remaining, or null if unknown.
     * @return 'critical'|'warning'|'normal'|'unknown'
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

    /**
     * Aggregate data from smaller periods to larger periods
     *
     * @param Collection<string, mixed> $data The data with ISO8601 date keys and values to aggregate.
     * @param CarbonPeriod $targetPeriod The target period to aggregate the data to.
     * @return Collection<string, mixed>
     * @throws InvalidArgumentException
     */
    public static function aggregateData(Collection $data, CarbonPeriod $targetPeriod): Collection
    {
        if ($data->isEmpty()) {
            return collect();
        }

        $sortedSourceData = $data->map(function ($value, $dateString) {
            return (object)[
                'date' => Carbon::parse($dateString), // Parse once
                'value' => $value,
            ];
        })->sortBy('date')->values(); // ->values() to re-index collection

        if ($sortedSourceData->isEmpty()) { // Should be covered by $data->isEmpty() but good for safety
            return collect();
        }

        $targetInterval = $targetPeriod->getDateInterval();

        if ($sortedSourceData->count() >= 2) {
            $sourceInterval = $sortedSourceData[1]->date->diff($sortedSourceData[0]->date);
            if ($targetInterval->spec() !== 'P0D' && $sourceInterval->spec() !== 'P0D') { // Avoid issues with zero intervals
                if (CarbonInterval::make($targetInterval)->totalSeconds < CarbonInterval::make($sourceInterval)->totalSeconds) {
                    throw new InvalidArgumentException('Target period interval must be larger than or equal to source period interval for meaningful aggregation.');
                }
            }
        }

        $aggregatedResults = collect();
        $sourceDataIterator = $sortedSourceData->getIterator();
        $sourceDataIterator->rewind(); // Ensure iterator is at the beginning

        $currentTargetDataPoint = $sourceDataIterator->valid() ? $sourceDataIterator->current() : null;

        foreach ($targetPeriod as $targetPeriodStartDate) {
            $targetPeriodEndDate = $targetPeriodStartDate->copy()->add($targetInterval);
            $sumForCurrentTargetPeriod = 0;
            $hasValuesInPeriod = false;

            while ($currentTargetDataPoint !== null && $currentTargetDataPoint->date->lt($targetPeriodEndDate)) {
                if ($currentTargetDataPoint->date->gte($targetPeriodStartDate)) {
                    $sumForCurrentTargetPeriod += $currentTargetDataPoint->value;
                    $hasValuesInPeriod = true;
                }

                $sourceDataIterator->next();
                $currentTargetDataPoint = $sourceDataIterator->valid() ? $sourceDataIterator->current() : null;
            }

            if ($hasValuesInPeriod) {
                $aggregatedResults[$targetPeriodStartDate->toIso8601String()] = $sumForCurrentTargetPeriod;
            }

            if ($currentTargetDataPoint === null) {
                break;
            }
        }

        return $aggregatedResults;
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
        $interval = $period->getDateInterval();

        foreach ($period as $key => $start) {
            $end = $start->copy()->add($interval);
            $value = $getData($start, $end, $key);

            $data[$start->toIso8601String()] = $value;
        }

        return $data;
    }

    /**
     * Extrapolate when a stock will be depleted based on current amount and daily usage rate
     *
     * @param float $currentAmount The current stock amount
     * @param float $dailyRate The average daily consumption/usage rate
     * @return Carbon The estimated depletion date, or null if rate is zero or negative
     */
    public static function extrapolateDate(float $currentAmount, float $dailyRate): Carbon
    {
        if ($dailyRate <= 0)
            return Carbon::endOfTime();

        if ($currentAmount <= 0)
            return Carbon::now();

        return Carbon::now()->addDays($currentAmount / $dailyRate);
    }
}
