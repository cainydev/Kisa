<?php

namespace App\Services\Stats;

use App\Models\Variant;
use App\Support\Stats\HerbReservation;
use Illuminate\Support\Collection;

/**
 * Herbs already promised to open orders that haven't been bottled yet.
 *
 * Billbee lowers a variant's stock as soon as an order comes in, and the
 * 15-minute order sync copies that stock over, so a negative variant stock is
 * exactly the number of units sold but not yet bottled. Those units still need
 * their herbs — grams the usage-based forecast can't see until the bottling
 * happens. Orders served from bottled stock are deliberately not counted:
 * their herbs were already used up when that stock was bottled.
 *
 * Calculated live, not in the nightly stats run, so a big order shows up in
 * the reorder table within minutes.
 */
class OpenOrderReservations
{
    /**
     * Reservations keyed by herb id. Herbs with nothing reserved are absent.
     *
     * @return Collection<int, HerbReservation>
     */
    public function perHerb(): Collection
    {
        /** @var array<int, array{grams: float, lines: list<array{variant: string, missing: int, grams: float}>}> $perHerb */
        $perHerb = [];

        $shortVariants = Variant::query()
            ->where('stock', '<', 0)
            ->with('product.recipeIngredients')
            ->get();

        foreach ($shortVariants as $variant) {
            $missing = (int) -$variant->stock;

            foreach ($variant->herbsNeededFor($missing) as $herbId => $grams) {
                if ($grams <= 0) {
                    continue;
                }

                $perHerb[$herbId]['grams'] = ($perHerb[$herbId]['grams'] ?? 0) + $grams;
                $perHerb[$herbId]['lines'][] = [
                    'variant' => $variant->name,
                    'missing' => $missing,
                    'grams' => round($grams, 1),
                ];
            }
        }

        return collect($perHerb)->map(fn (array $reservation) => new HerbReservation(
            grams: round($reservation['grams'], 1),
            lines: collect($reservation['lines'])->sortByDesc('grams')->values()->all(),
        ));
    }
}
