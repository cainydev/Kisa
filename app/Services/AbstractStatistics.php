<?php

namespace App\Services;

use App\Models\Bottle;
use App\Models\Delivery;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

abstract class AbstractStatistics
{
    /**
     * First day of the stats window: the earliest recorded event across
     * orders, bottlings and deliveries.
     */
    public static function windowStart(): Carbon
    {
        $earliest = collect([
            Order::query()->min('date'),
            Bottle::query()->min('date'),
            Delivery::query()->min('delivered_date'),
        ])->filter()->min();

        return $earliest ? Carbon::parse($earliest)->startOfDay() : now()->startOfDay();
    }

    /**
     * Generate and persist statistics for all entities.
     */
    abstract public static function generateAll(): void;

    /**
     * Generate and persist statistics for the given models.
     */
    abstract public static function generate(Collection $models): void;

    /**
     * Extrapolate when a stock will be depleted based on current amount and
     * daily usage rate. Returns end-of-time when the rate is zero or negative.
     */
    public static function extrapolateDate(float $currentAmount, float $dailyRate): Carbon
    {
        if ($dailyRate <= 0) {
            return Carbon::endOfTime();
        }

        if ($currentAmount <= 0) {
            return Carbon::now();
        }

        return Carbon::now()->addDays($currentAmount / $dailyRate);
    }

    /**
     * Snapshot a running level (like stock) backwards through time, keeping
     * only the days on which it changed plus anchors at both window ends.
     * The level is clamped at zero: usage recorded without a matching restock
     * (e.g. bags delivered before the stats window) would otherwise push the
     * reconstructed history below zero.
     *
     * @param  Collection<string, float>  $dailyChanges  Y-m-d => signed change (restock - usage)
     * @param  float  $currentValue  The level at the end of the window (today)
     * @return Collection<string, float> Y-m-d => level at end of that day
     */
    protected static function sparseStockLevels(Collection $dailyChanges, float $currentValue, Carbon $start, Carbon $end): Collection
    {
        $levels = collect([$end->toDateString() => $currentValue]);
        $value = $currentValue;

        foreach ($dailyChanges->sortKeysDesc() as $date => $change) {
            $levels[$date] = $value;
            $value = max($value - $change, 0);
        }

        $startKey = $start->toDateString();

        if (! $levels->has($startKey)) {
            $levels[$startKey] = $value;
        }

        return $levels->sortKeys();
    }
}
