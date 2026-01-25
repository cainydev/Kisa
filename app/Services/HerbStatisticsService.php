<?php

namespace App\Services;

use App\Models\Herb;
use App\Settings\StatsSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HerbStatisticsService extends AbstractStatistics
{
    public const string CACHE_PREFIX = 'herb_stats';

    public static function generateAll(): void
    {
        Herb::chunk(50, fn($herbs) => static::generate($herbs));
    }

    public static function generate(Collection $models): void
    {
        $startDate = app(StatsSettings::class)->startDate;
        $period = self::days($startDate);

        foreach ($models as $herb) {
            // 1. Calculate TRUE Daily Usage (Production + Trash)
            $dailyUsage = static::calculateTotalDailyUsage($herb, $startDate);

            // 2. Calculate Daily Restock (Incoming Bags)
            // Uses 'created_at' and 'size' (initial amount)
            $dailyRestock = $herb->bags()
                ->withTrashed()
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, SUM(size) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            // 3. Current Stock Anchor
            $currentStock = $herb->bags->sum(fn($b) => $b->getCurrentWithTrashed());

            // 4. Calculate Net Changes
            $netChanges = collect();
            foreach ($period as $date) {
                $d = $date->toDateString();
                $usage = $dailyUsage[$d] ?? 0;
                $restock = $dailyRestock[$d] ?? 0;

                if ($usage > 0 || $restock > 0) {
                    $netChanges[$d] = $restock - $usage;
                }
            }

            // 5. Reconstruct History
            // Remember: Ensure your AbstractStatistics::reconstructHistory does NOT clamp to 0
            // so the graph floats correctly even if data is missing.
            $dailyStock = static::reconstructHistory(
                $netChanges,
                $currentStock,
                $period
            );

            // 6. Metrics
            $recentAvg = $dailyUsage->reverse()->take(90)->avg();
            $allTimeAvg = $dailyUsage->avg() ?: 0;
            $rate = ($recentAvg > 0) ? $recentAvg : $allTimeAvg;

            $depletionDate = self::extrapolateDate($currentStock, $rate);

            // 7. Store to Redis
            $baseKey = self::CACHE_PREFIX . ":{$herb->id}";

            Cache::putMany([
                "{$baseKey}:usage:daily" => $dailyUsage,
                "{$baseKey}:stock:daily" => $dailyStock,
                "{$baseKey}:stock:current" => $currentStock,
                "{$baseKey}:usage:total" => $dailyUsage->sum(),
                "{$baseKey}:depletion_date" => $depletionDate->toIso8601String(),
                "{$baseKey}:generated_at" => now()->toIso8601String(),
            ], self::CACHE_LONG);
        }
    }

    /**
     * Combines Production Usage (Ingredients) + Trash Usage (Deleted Bags)
     */
    protected static function calculateTotalDailyUsage(Herb $herb, $startDate): Collection
    {
        // A. Production Usage (Ingredients)
        $productionUsage = DB::table('ingredients', 'i')
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
            ->pluck('total', 'time');

        // B. Trash Usage (Deleted Bags)
        // We look for bags that were deleted (discarded) and have a trash value
        $trashUsage = $herb->bags()
            ->onlyTrashed() // Crucial: Only look at discarded bags
            ->where('deleted_at', '>=', $startDate->startOfDay())
            ->where('trashed', '>', 0)
            ->selectRaw('DATE(deleted_at) as date, SUM(trashed) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        // C. Merge timelines
        $combined = collect();
        // Use the master period to ensure we cover all days properly
        $period = self::days($startDate);

        foreach ($period as $date) {
            $d = $date->toDateString();
            $prod = $productionUsage[$d] ?? 0;
            $waste = $trashUsage[$d] ?? 0;

            $combined[$d] = $prod + $waste;
        }

        return $combined;
    }
}
