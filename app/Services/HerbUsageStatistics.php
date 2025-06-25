<?php

namespace App\Services;

use App\Models\Bag;
use App\Models\Herb;
use App\Settings\StatsSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HerbUsageStatistics extends AbstractStatistics
{
    /**
     * Generate statistics for all herbs.
     *
     * @return void
     */
    public static function generateAll(): void
    {
        self::generate(Herb::all());
    }

    /**
     * Generate statistics for all the given herbs.
     *
     * @param Collection<Herb> $models The collection of herbs to generate statistics for
     * @return void
     */
    public static function generate(Collection $models): void
    {
        $startDate = app(StatsSettings::class)->startDate;

        foreach ($models as $herb) {
            $dailyRaw = DB::table('ingredients', 'i')
                ->join('bags as b', 'i.bag_id', '=', 'b.id')
                ->join('bottle_positions as bp', 'i.bottle_position_id', '=', 'bp.id')
                ->join('variants as v', 'bp.variant_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->join('herb_product as hp', function ($join) {
                    $join->on('hp.product_id', '=', 'p.id')
                        ->on('hp.herb_id', '=', 'b.herb_id');
                })
                ->where('b.herb_id', $herb->id)
                ->whereBetween('i.created_at', [$startDate->startOfDay(), now()->endOfDay()])
                ->select(
                    DB::raw('DATE(i.created_at) as time'),
                    DB::raw('ROUND(COALESCE(SUM(
                        v.`size` *
                        (hp.`percentage` / 100.0) *
                        bp.`count`
                    ), 0)) as total')
                )
                ->groupBy(DB::raw('time'))
                ->orderBy(DB::raw('time'))
                ->pluck('total', 'time');

            $daily = self::generateDataset(
                fn(Carbon $start) => $dailyRaw[$start->toDateString()] ?? 0,
                self::days(since: $startDate)
            );

            $herb->daily_usage_stats = $daily;

            $weekly = self::aggregateData($daily, self::weeks(since: $startDate));
            $herb->weekly_usage_stats = $weekly;

            $monthly = self::aggregateData($daily, self::months(since: $startDate));
            $herb->monthly_usage_stats = $monthly;

            $yearly = self::aggregateData($daily, self::years(since: $startDate));
            $herb->yearly_usage_stats = $yearly;

            $currentStock = $herb->bags->sum(function (Bag $bag) {
                return $bag->getCurrentWithTrashed();
            });

            $averagePerDay90 = $daily->reverse()->take(90)->avg() ?: 0;
            $depletedDate = self::extrapolateDate($currentStock, $averagePerDay90);

            $herb->average_daily_usage = $daily->avg() ?: 0;
            $herb->average_weekly_usage = $weekly->avg() ?: 0;
            $herb->average_monthly_usage = $monthly->avg() ?: 0;
            $herb->average_yearly_usage = $yearly->avg() ?: 0;

            $herb->estimated_depletion_date = $depletedDate;
            $herb->current_stock = $currentStock;
            $herb->total_usage = $daily->sum();
        }
    }
}
