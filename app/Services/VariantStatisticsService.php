<?php

namespace App\Services;

use App\Models\Variant;
use App\Support\Stats\VariantStats;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VariantStatisticsService extends AbstractStatistics
{
    public static function generateAll(): void
    {
        Variant::chunk(100, fn ($variants) => static::generate($variants));
    }

    public static function generate(Collection $models): void
    {
        $start = static::windowStart();
        $end = now()->startOfDay();

        foreach ($models as $variant) {
            $sales = static::dailySales($variant, $start);
            $restocks = static::dailyRestocks($variant, $start);
            $currentStock = (float) ($variant->stock ?? 0);

            $netChanges = $restocks->keys()
                ->merge($sales->keys())
                ->unique()
                ->mapWithKeys(fn (string $date) => [$date => ($restocks[$date] ?? 0) - ($sales[$date] ?? 0)]);

            $stockLevels = static::sparseStockLevels($netChanges, $currentStock, $start, $end);

            $recentStart = $start->copy()->max(now()->subMonths(6)->startOfDay());
            $recentDays = max((int) $recentStart->diffInDays($end) + 1, 1);
            $recentKey = $recentStart->toDateString();
            $recentRate = $sales->filter(fn (float $total, string $date) => $date >= $recentKey)->sum() / $recentDays;

            $lastSaleDate = $sales->filter(fn (float $total) => $total > 0)->keys()->last();

            $variant->stats = new VariantStats(
                start: VariantStats::dayFrom($start),
                end: VariantStats::dayFrom($end),
                generatedAt: now()->toImmutable(),
                currentStock: $currentStock,
                sales: $sales->all(),
                restocks: $restocks->all(),
                stock: $stockLevels->all(),
                totalSales: (float) $sales->sum(),
                averageDailySales: $recentRate,
                depletionDate: static::extrapolateDate($currentStock, $recentRate)->toImmutable(),
                nextSaleDate: ($lastSaleDate && $recentRate > 0)
                    ? Carbon::parse($lastSaleDate)->addDays(1 / $recentRate)->toImmutable()
                    : null,
            );

            $variant->timestamps = false;
            $variant->saveQuietly();
            $variant->timestamps = true;
        }
    }

    /**
     * Units sold per day since $start, sparse and keyed by Y-m-d. Dated by the
     * real order date, not by when the position was imported from Billbee —
     * most of the early history was bulk-imported long after the fact.
     *
     * @return Collection<string, float>
     */
    protected static function dailySales(Variant $variant, Carbon $start): Collection
    {
        return $variant->orderPositions()
            ->join('orders', 'order_positions.order_id', '=', 'orders.id')
            ->where('orders.date', '>=', $start)
            ->selectRaw('DATE(orders.date) as day, SUM(order_positions.quantity) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total) => (float) $total)
            ->sortKeys();
    }

    /**
     * Units produced (bottled and uploaded) per day since $start, sparse and
     * keyed by Y-m-d.
     *
     * @return Collection<string, float>
     */
    protected static function dailyRestocks(Variant $variant, Carbon $start): Collection
    {
        return $variant->positions()
            ->join('bottles', 'bottle_positions.bottle_id', '=', 'bottles.id')
            ->where('bottle_positions.uploaded', true)
            ->where('bottles.date', '>=', $start)
            ->selectRaw('DATE(bottles.date) as day, SUM(bottle_positions.count) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total) => (float) $total)
            ->sortKeys();
    }
}
