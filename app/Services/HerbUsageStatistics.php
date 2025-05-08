<?php

namespace App\Services;

use App\Models\Herb;
use App\Models\Ingredient;
use App\Settings\StatsSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HerbUsageStatistics extends AbstractStatistics
{
    public static function generateAll(): void
    {
        self::generate(Herb::all());
    }

    // stats:herb:123:depleted => depleted date based on last semester usage per day
    // stats:herb:123:daily => array of daily date => usage
    // stats:herb:123:weekly => array of weekly date => usage
    // stats:herb:123:monthly => array of monthly date => usage
    // stats:herb:123:yearly => array of yearly date => usage

    // stats:herb:123:averageLastDay => average usage per day in the last day
    // stats:herb:123:averageLastWeek => average usage per day in the last week
    // stats:herb:123:averageLastMonth => average usage per day in the last month
    // stats:herb:123:averageLastSemester => average usage per day in the last semester
    // stats:herb:123:averageLastYear => average usage per day in the last year

    public static function generate(Collection|Model|array|null $herbs = null): void
    {
        $herbs = collect($herbs);

        $startDate = app(StatsSettings::class)->startDate;

        foreach ($herbs as $herb) {
            $usageByDay = self::generateDataset(function ($start, $end) use ($herb) {
                return Ingredient::query()
                    ->join('bags', 'ingredients.bag_id', '=', 'bags.id')
                    ->join('bottle_positions', 'ingredients.bottle_position_id', '=', 'bottle_positions.id')
                    ->join('variants', 'bottle_positions.variant_id', '=', 'variants.id')
                    ->join('products', 'variants.product_id', '=', 'products.id')
                    ->join('herb_product', 'products.id', '=', 'herb_product.product_id')
                    ->join('herbs', 'herb_product.herb_id', '=', 'herbs.id')
                    ->whereBetween('bags.created_at', [$start, $end])
                    ->where('herbs.id', $herb)
                    ->sum(DB::raw(
                        '`variants`.`size` * (`herb_product`.`percentage` / 100.0) * `bottle_positions`.`count`'
                    )) ?? 0;
            }, self::getPeriod('1 day', $startDate, now()->endOfDay()));

            Cache::put("stats:herb:$herb->id:daily", $usageByDay, self::CACHE_LONG);

            dd($usageByDay);
        }
    }
}
