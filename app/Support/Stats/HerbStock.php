<?php

namespace App\Support\Stats;

use App\Models\Bag;
use Illuminate\Support\Collection;

/**
 * The single definition of a herb's current physical stock in grams:
 *
 *     stock = max(delivered − used − trashed, 0)
 *
 * where `delivered` is the sum of received bag sizes, `used` the grams drawn
 * into bottlings, and `trashed` the grams discarded. Clamped at zero (negative
 * stock is physically impossible; it only ever means an over-draw) and rounded
 * to one decimal.
 *
 * Both the per-bag stats generation (which has the bags in memory) and the
 * set-based MassBalance query (which has grouped aggregates) resolve stock
 * through here so the two can never drift apart.
 */
class HerbStock
{
    public const PRECISION = 1;

    /**
     * Current stock from a herb's bags. Uses each bag's remaining-with-trashed
     * amount, which already encodes size − used − trashed per bag.
     *
     * @param  Collection<int, Bag>  $bags
     */
    public static function forBags(Collection $bags): float
    {
        return self::clamp((float) $bags->sum(fn (Bag $bag) => $bag->getCurrentWithTrashed()));
    }

    /**
     * Current stock from pre-aggregated grams (e.g. grouped SQL sums).
     */
    public static function fromAggregates(float $delivered, float $used, float $trashed): float
    {
        return self::clamp($delivered - $used - $trashed);
    }

    private static function clamp(float $stock): float
    {
        return round(max($stock, 0.0), self::PRECISION);
    }
}
