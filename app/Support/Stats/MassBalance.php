<?php

namespace App\Support\Stats;

use App\Models\Bag;
use App\Models\Herb;
use App\Models\Ingredient;
use Illuminate\Support\Collection;

/**
 * Warenstrombilanz / Mengenflussrechnung.
 *
 * Reconciles, per raw material (Herb) over an optional date window, the organic
 * quantity that flowed in against what flowed out — the mass balance a
 * Bio-Kontrolleur checks. The hard rule an inspection probes: output must never
 * exceed the justified input (you cannot have processed more organic material
 * than you legitimately received).
 *
 *   Eingang (deliveries)  →  Verbrauch (fillings) + Ausschuss (verworfen) + Bestand
 *
 * Inflow is dated by the delivery date; consumption by the bottling date. Used
 * grams come from ingredients.amount, snapshotted at bottling. The balance is
 * computed with a handful of grouped aggregate queries, never per-model loops.
 */
class MassBalance
{
    public function __construct(
        protected ?string $from = null,
        protected ?string $to = null,
    ) {}

    public static function between(?string $from, ?string $to): self
    {
        return new self($from, $to);
    }

    /**
     * One balanced row per herb that had any inflow or consumption in the window.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(): Collection
    {
        $delivered = $this->deliveredPerHerb($this->from, $this->to);
        $used = $this->usedPerHerb($this->from, $this->to);

        // Ausschuss and current stock are lifetime figures (not dateable from
        // the schema), so they are always computed unfiltered.
        $trashed = $this->trashedPerHerb();
        $lifetimeIn = $this->deliveredPerHerb(null, null);
        $lifetimeUsed = $this->usedPerHerb(null, null);

        $herbIds = $delivered->keys()
            ->merge($used->keys())
            ->merge($trashed->keys())
            ->unique()
            ->values();

        $herbs = Herb::whereIn('id', $herbIds)->pluck('name', 'id');

        return $herbIds
            ->map(function (int $herbId) use ($herbs, $delivered, $used, $trashed, $lifetimeIn, $lifetimeUsed) {
                $in = (float) $delivered->get($herbId, 0);
                $out = (float) $used->get($herbId, 0);
                $waste = (float) $trashed->get($herbId, 0);

                // Live current stock: everything ever received minus everything
                // ever used and discarded, through the canonical definition.
                $stock = HerbStock::fromAggregates(
                    (float) $lifetimeIn->get($herbId, 0),
                    (float) $lifetimeUsed->get($herbId, 0),
                    $waste,
                );

                // Signed balance over the window: Eingang − (Verbrauch + Ausschuss).
                // Positive = surplus (more came in than left, plausible);
                // negative = shortfall (more left than came in — the flag).
                $balance = round($in - ($out + $waste), 1);

                return [
                    'herb_id' => $herbId,
                    'herb' => $herbs->get($herbId) ?? "#{$herbId}",
                    'delivered' => round($in, 1),
                    'used' => round($out, 1),
                    'trashed' => round($waste, 1),
                    'stock' => round($stock, 1),
                    'balance' => $balance,
                    'shortfall' => max(-$balance, 0),
                    'plausible' => $balance >= -0.5, // allow rounding noise
                ];
            })
            ->sortByDesc('delivered')
            ->values();
    }

    /**
     * Totals across all herbs in the window, for the summary tiles.
     *
     * @return array<string, float|int>
     */
    public function totals(): array
    {
        return self::totalsForRows($this->rows());
    }

    /**
     * Aggregate summary totals from already-computed balance rows, so callers
     * that have cached the rows don't trigger a second row pass.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float|int>
     */
    public static function totalsForRows(Collection $rows): array
    {
        return [
            'herbs' => $rows->count(),
            'delivered' => round($rows->sum('delivered'), 1),
            'used' => round($rows->sum('used'), 1),
            'trashed' => round($rows->sum('trashed'), 1),
            'stock' => round($rows->sum('stock'), 1),
            'implausible' => $rows->where('plausible', false)->count(),
        ];
    }

    /**
     * Delivered grams per herb: SUM(bag.size) for deliveries in the window.
     * One grouped query. Soft-deleted (emptied/discarded) bags are included —
     * they were still received, so their size is part of the Eingang; otherwise
     * every discarded bag would look like an unexplained shortfall.
     *
     * @return Collection<int, float>
     */
    protected function deliveredPerHerb(?string $from, ?string $to): Collection
    {
        return Bag::withTrashed()
            ->when($from || $to, fn ($q) => $q->whereHas(
                'delivery',
                fn ($d) => $d
                    ->when($from, fn ($x) => $x->whereDate('delivered_date', '>=', $from))
                    ->when($to, fn ($x) => $x->whereDate('delivered_date', '<=', $to))
            ))
            ->selectRaw('herb_id, SUM(size) as total')
            ->groupBy('herb_id')
            ->pluck('total', 'herb_id')
            ->map(fn ($v) => (float) $v);
    }

    /**
     * Discarded grams per herb: SUM(bag.trashed). Lifetime (not dateable).
     * One grouped query.
     *
     * @return Collection<int, float>
     */
    protected function trashedPerHerb(): Collection
    {
        return Bag::withTrashed()
            ->selectRaw('herb_id, SUM(trashed) as total')
            ->groupBy('herb_id')
            ->having('total', '>', 0)
            ->pluck('total', 'herb_id')
            ->map(fn ($v) => (float) $v);
    }

    /**
     * Consumed grams per herb: SUM(ingredients.amount), the grams frozen at
     * bottling time. Dated by bottling date. One grouped query.
     *
     * @return Collection<int, float>
     */
    protected function usedPerHerb(?string $from, ?string $to): Collection
    {
        return Ingredient::query()
            ->when($from || $to, function ($q) use ($from, $to) {
                $q->join('bottle_positions', 'bottle_positions.id', '=', 'ingredients.bottle_position_id')
                    ->join('bottles', 'bottles.id', '=', 'bottle_positions.bottle_id')
                    ->when($from, fn ($x) => $x->whereDate('bottles.date', '>=', $from))
                    ->when($to, fn ($x) => $x->whereDate('bottles.date', '<=', $to));
            })
            ->groupBy('ingredients.herb_id')
            ->selectRaw('ingredients.herb_id as herb_id, SUM(ingredients.amount) as total')
            ->pluck('total', 'herb_id')
            ->map(fn ($v) => (float) $v);
    }
}
