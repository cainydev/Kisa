<?php

namespace App\Services;

use App\Models\Variant;
use App\Settings\StatsSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class VariantStatisticsService extends AbstractStatistics
{
    public const string CACHE_PREFIX = 'variant_stats';

    public static function generateAll(): void
    {
        Variant::chunk(100, fn($variants) => (new self)->generate($variants));
    }

    public static function generate(Collection $models): void
    {
        $startDate = app(StatsSettings::class)->startDate;
        $period = self::days($startDate);

        foreach ($models as $variant) {
            // --- 1. SALES (Usage/Outgoing) ---
            $dailySales = static::calculateDailySales($variant, $startDate);

            // --- 2. PRODUCTION (Restock/Incoming) ---
            $dailyRestock = static::calculateDailyRestocks($variant, $startDate);

            // --- 3. STOCK HISTORY (Reconstruction) ---
            $currentStock = $variant->stock ?? 0;

            $netChanges = collect();
            foreach ($period as $date) {
                $d = $date->toDateString();
                $sales = $dailySales[$d] ?? 0;
                $production = $dailyRestock[$d] ?? 0;

                // If we sold 5 and produced 20, net change is +15
                if ($sales > 0 || $production > 0) {
                    $netChanges[$d] = $production - $sales;
                }
            }

            $dailyStock = static::reconstructHistory($netChanges, $currentStock, $period);

            // --- 4. CALCULATE METRICS ---
            // Metric: Recent Sales Rate (Last 6 Months)
            $recentCapDate = now()->subMonths(6);
            $recentSales = $dailySales->filter(fn($val, $date) => $date >= $recentCapDate->toDateString());
            $recentRate = $recentSales->avg() ?: 0;

            // Metric: Estimated Depletion
            $depletionDate = static::extrapolateDate($currentStock, $recentRate);

            // Metric: Next Sale Prediction
            $nextSaleDate = null;
            $lastSaleDate = $dailySales->filter(fn($v) => $v > 0)->keys()->last();

            if ($lastSaleDate && $recentRate > 0) {
                $daysUntilNext = 1 / $recentRate;
                $nextSaleDate = \Carbon\Carbon::parse($lastSaleDate)->addDays($daysUntilNext);
            }

            // --- 5. STORE IN REDIS ---
            $baseKey = static::CACHE_PREFIX . ":{$variant->id}";

            Cache::putMany([
                "{$baseKey}:sales:daily" => $dailySales,
                "{$baseKey}:restock:daily" => $dailyRestock, // Optional: Store if you want to graph production too
                "{$baseKey}:stock:daily" => $dailyStock,
                "{$baseKey}:stock:current" => $currentStock,
                "{$baseKey}:sales:total" => $dailySales->sum(),
                "{$baseKey}:sales:avg_recent" => $recentRate,
                "{$baseKey}:depletion_date" => $depletionDate->toIso8601String(),
                "{$baseKey}:next_sale_date" => $nextSaleDate?->toIso8601String(),
                "{$baseKey}:generated_at" => now()->toIso8601String(),
            ], self::CACHE_LONG);
        }
    }

    protected static function calculateDailySales(Variant $variant, $startDate): Collection
    {
        $dailyRaw = $variant->orderPositions()
            ->selectRaw('DATE(created_at) as time, SUM(quantity) as sales')
            ->where('created_at', '>=', $startDate->startOfDay())
            ->groupBy('time')
            ->pluck('sales', 'time');

        return self::generateDataset(
            fn($s) => $dailyRaw[$s->toDateString()] ?? 0,
            self::days($startDate)
        );
    }

    protected static function calculateDailyRestocks(Variant $variant, $startDate): Collection
    {
        $dailyRaw = $variant->positions()
            ->join('bottles', 'bottle_positions.bottle_id', '=', 'bottles.id')
            ->where('bottle_positions.uploaded', true)
            ->where('bottles.date', '>=', $startDate->startOfDay())
            ->selectRaw('DATE(bottles.date) as time, SUM(bottle_positions.count) as produced')
            ->groupBy('time')
            ->pluck('produced', 'time');

        return self::generateDataset(
            fn($s) => $dailyRaw[$s->toDateString()] ?? 0,
            self::days($startDate)
        );
    }
}
