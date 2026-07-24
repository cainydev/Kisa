<?php

namespace App\Services;

use App\Models\Bag;
use App\Models\Herb;
use App\Settings\StatsSettings;
use App\Support\Stats\HerbStats;
use App\Support\Stats\TimeSeriesQuery;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HerbStatisticsService extends AbstractStatistics
{
    public static function generateAll(): void
    {
        Herb::chunk(50, fn ($herbs) => static::generate($herbs));
    }

    public static function generate(Collection $models): void
    {
        $start = app(StatsSettings::class)->startDate->copy()->startOfDay();
        $end = now()->startOfDay();

        $models->loadMissing('bags.ingredients');

        foreach ($models as $herb) {
            $usage = static::dailyUsage($herb, $start);
            $restocks = static::dailyRestocks($herb, $start);
            $currentStock = (float) $herb->bags->sum(fn (Bag $bag) => $bag->getCurrentWithTrashed());

            $netChanges = $restocks->keys()
                ->merge($usage->keys())
                ->unique()
                ->mapWithKeys(fn (string $date) => [$date => ($restocks[$date] ?? 0) - ($usage[$date] ?? 0)]);

            $stockLevels = static::sparseStockLevels($netChanges, $currentStock, $start, $end);

            $totalDays = max((int) $start->diffInDays($end) + 1, 1);
            $recentStart = $start->copy()->max(now()->subDays(89)->startOfDay());
            $recentDays = max((int) $recentStart->diffInDays($end) + 1, 1);
            $recentKey = $recentStart->toDateString();

            $recentAverage = $usage->filter(fn (float $total, string $date) => $date >= $recentKey)->sum() / $recentDays;
            $allTimeAverage = $usage->sum() / $totalDays;
            $rate = $recentAverage > 0 ? $recentAverage : $allTimeAverage;

            $usageSeries = fn (): TimeSeriesQuery => new TimeSeriesQuery($usage, 'sum', $start, $end);

            $herb->stats = new HerbStats(
                start: HerbStats::dayFrom($start),
                end: HerbStats::dayFrom($end),
                generatedAt: now()->toImmutable(),
                currentStock: $currentStock,
                usage: $usage->all(),
                stock: $stockLevels->all(),
                totalUsage: (float) $usage->sum(),
                averageDailyUsage: (float) $allTimeAverage,
                averageWeeklyUsage: (float) ($usageSeries()->lastWeeks(52)->get()->avg() ?? 0),
                averageMonthlyUsage: (float) ($usageSeries()->lastMonths(12)->get()->avg() ?? 0),
                depletionDate: static::extrapolateDate($currentStock, $rate)->toImmutable(),
            );

            $herb->timestamps = false;
            $herb->saveQuietly();
            $herb->timestamps = true;
        }
    }

    /**
     * True daily usage in grams (production ingredients + discarded bags)
     * since $start, sparse and keyed by Y-m-d.
     *
     * @return Collection<string, float>
     */
    protected static function dailyUsage(Herb $herb, Carbon $start): Collection
    {
        $production = DB::table('ingredients', 'i')
            ->join('bottle_positions as bp', 'i.bottle_position_id', '=', 'bp.id')
            ->join('bottles as bo', 'bp.bottle_id', '=', 'bo.id')
            ->where('i.herb_id', $herb->id)
            ->where('bo.date', '>=', $start)
            ->selectRaw('DATE(bo.date) as day, ROUND(SUM(i.amount)) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $trash = $herb->bags()
            ->onlyTrashed()
            ->where('deleted_at', '>=', $start)
            ->where('trashed', '>', 0)
            ->selectRaw('DATE(deleted_at) as day, SUM(trashed) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $production->keys()
            ->merge($trash->keys())
            ->unique()
            ->mapWithKeys(fn (string $date) => [$date => (float) ($production[$date] ?? 0) + (float) ($trash[$date] ?? 0)])
            ->sortKeys();
    }

    /**
     * Incoming grams (delivered bag sizes) per day since $start, sparse and
     * keyed by Y-m-d. Dated by the physical delivery date — bags are often
     * entered into the system long after the goods arrived, which would
     * otherwise place usage before its restock.
     *
     * @return Collection<string, float>
     */
    protected static function dailyRestocks(Herb $herb, Carbon $start): Collection
    {
        return DB::table('bags', 'b')
            ->leftJoin('deliveries as d', 'b.delivery_id', '=', 'd.id')
            ->where('b.herb_id', $herb->id)
            ->whereRaw('COALESCE(d.delivered_date, DATE(b.created_at)) >= ?', [$start->toDateString()])
            ->selectRaw('COALESCE(d.delivered_date, DATE(b.created_at)) as day, SUM(b.size) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($total) => (float) $total)
            ->sortKeys();
    }
}
