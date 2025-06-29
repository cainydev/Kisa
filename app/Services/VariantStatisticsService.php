<?php

namespace App\Services;

use App\Models\Variant;
use App\Settings\StatsSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class VariantStatisticsService extends AbstractStatistics
{
    /**
     * Generate statistics for all variants.
     *
     * @return void
     */
    public static function generateAll(): void
    {
        self::generate(Variant::all());
    }

    /**
     * Generate statistics for all the given variants.
     *
     * @param Collection<Variant> $models The collection of variants to generate statistics for
     * @return void
     */
    public static function generate(Collection $models): void
    {
        $startDate = app(StatsSettings::class)->startDate;

        foreach ($models as $variant) {
            $dailyRaw = $variant->orderPositions()
                ->selectRaw('DATE(created_at) as time, SUM(quantity) as sales')
                ->where('created_at', '>=', $startDate->startOfDay())
                ->groupBy('time')
                ->orderBy('time')
                ->pluck('sales', 'time');

            $daily = self::generateDataset(
                fn($start) => $dailyRaw[$start->toDateString()] ?? 0,
                self::days(since: $startDate)
            );
            $dailyAvg = $daily->avg();
            $variant->daily_sales = $daily;
            $variant->average_daily_sales = $dailyAvg;

            $weekly = self::aggregateData($daily, self::weeks(since: $startDate));
            $weeklyAvg = $weekly->avg();
            $variant->weekly_sales = $weekly;
            $variant->average_weekly_sales = $weeklyAvg;

            $monthly = self::aggregateData($daily, self::months(since: $startDate));
            $monthlyAvg = $monthly->avg();
            $variant->monthly_sales = $monthly;
            $variant->average_monthly_sales = $monthlyAvg;

            $yearly = self::aggregateData($daily, self::years(since: $startDate));
            $yearlyAvg = $yearly->avg();
            $variant->yearly_sales = $yearly;
            $variant->average_yearly_sales = $yearlyAvg;

            $recentCap = now()->subMonths(6);
            $recentSalesPerDay = $daily->filter(fn($_, $key) => Carbon::parse($key) >= $recentCap)->avg();

            $depletedDate = self::extrapolateDate($variant->stock, $recentSalesPerDay);
            $variant->depleted_date = $depletedDate->toDateString();

            $lastSaleDate = null;
            if ($dailyRaw->isNotEmpty()) {
                $lastSaleDate = Carbon::parse($dailyRaw->keys()->last())->endOfDay();
            }

            if ($lastSaleDate && $recentSalesPerDay > 0) {
                $intervalDays = 1 / $recentSalesPerDay;
                $nextSaleDateTime = $lastSaleDate->copy()->addDays($intervalDays);
                $variant->next_sale = $nextSaleDateTime->toDateTimeString();
            }
        }
    }
}
