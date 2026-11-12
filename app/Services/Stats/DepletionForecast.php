<?php

namespace App\Services\Stats;

use App\Models\Herb;
use App\Models\Variant;
use App\Support\Stats\HerbStats;
use App\Support\Stats\VariantStats;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Answers "what runs out within N days", the projection several tools and
 * widgets each used to re-derive from the persisted stats. Entities without a
 * projected depletion date (no usage/sales history) are excluded; the rest are
 * returned ordered by how soon they deplete.
 */
class DepletionForecast
{
    public function __construct(private OpenOrderReservations $reservations) {}

    /**
     * Herbs projected to run out on or before `$withinDays` from now. Grams
     * already promised to open, not yet bottled orders are taken out of the
     * stock first (see OpenOrderReservations).
     *
     * @return Collection<int, array{herb: Herb, stock: float, reserved: float, depletion: CarbonImmutable}>
     */
    public function herbs(int $withinDays = 30): Collection
    {
        $deadline = CarbonImmutable::now()->addDays($withinDays);
        $reservations = $this->reservations->perHerb();

        return Herb::query()->with('supplier')->get()
            ->map(function (Herb $herb) use ($reservations): array {
                $stats = HerbStats::for($herb);
                $reserved = $reservations->get($herb->id)?->grams ?? 0.0;

                return [
                    'herb' => $herb,
                    'stock' => $stats->currentStock(),
                    'reserved' => $reserved,
                    'depletion' => $stats->depletionDateWith($reserved),
                ];
            })
            ->filter(fn (array $row) => $row['depletion'] !== null && $row['depletion']->lessThanOrEqualTo($deadline))
            ->sortBy('depletion')
            ->values();
    }

    /**
     * Variants projected to run out on or before `$withinDays` from now.
     *
     * @return Collection<int, array{variant: Variant, avgDaily: float, depletion: CarbonImmutable}>
     */
    public function variants(int $withinDays = 30): Collection
    {
        $deadline = CarbonImmutable::now()->addDays($withinDays);

        return Variant::query()->with('product')->get()
            ->map(function (Variant $variant): array {
                $stats = VariantStats::for($variant);

                return [
                    'variant' => $variant,
                    'avgDaily' => $stats->averageDailySales(),
                    'depletion' => $stats->estimatedDepletionDate(),
                ];
            })
            ->filter(fn (array $row) => $row['depletion'] !== null && $row['depletion']->lessThanOrEqualTo($deadline))
            ->sortBy('depletion')
            ->values();
    }
}
