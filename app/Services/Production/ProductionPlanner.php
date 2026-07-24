<?php

namespace App\Services\Production;

use App\Models\Bottle;
use App\Models\Order;
use App\Models\OrderPosition;
use App\Models\User;
use App\Models\Variant;
use App\Support\Stats\VariantStats;
use Illuminate\Support\Collection;

/**
 * Works out which variants must be bottled to fulfil the open (paid, unshipped)
 * orders, and how many of each. Billbee has already decremented stock when an
 * order arrived, so a negative variant stock is exactly the open shortfall to
 * produce; for common sizes the shortfall is topped up with a sales-based
 * projection so a batch also covers near-term demand.
 */
class ProductionPlanner
{
    private const MIN_BATCH_SIZE = 1;

    /**
     * @return Collection<int, array{
     *     variant_id: int,
     *     variant_label: string,
     *     stock: int,
     *     min_needed_quantity: int,
     *     order_references: list<mixed>,
     *     per_variant_herbs: iterable,
     *     original_positions: list<int>
     * }>
     */
    public function plan(ProductionPlan $plan): Collection
    {
        return $this->openOrderPositions($plan)
            ->groupBy(fn (OrderPosition $position) => $position->variant_id)
            ->mapWithKeys(fn (Collection $group) => $this->planForVariant($group, $plan))
            ->collect();
    }

    /**
     * Order positions for paid-but-unshipped orders created since the window start.
     *
     * @return Collection<int, OrderPosition>
     */
    private function openOrderPositions(ProductionPlan $plan): Collection
    {
        return Order::with(['positions.variant', 'positions.order'])
            ->whereNotNull('paid_at')
            ->whereNull('shipped_at')
            ->where('created_at', '>=', $plan->since)
            ->whereHas('positions')
            ->get()
            ->flatMap(fn (Order $order) => $order->positions
                ->map(fn (OrderPosition $position) => $position->setRelation('order', $order)));
    }

    /**
     * @param  Collection<int, OrderPosition>  $group
     * @return array<int, array<string, mixed>>
     */
    private function planForVariant(Collection $group, ProductionPlan $plan): array
    {
        $variant = $group->first()->variant ?? Variant::find($group->first()->variant_id);
        $stock = $variant?->stock ?? 0;

        // Only variants that are actually short (Billbee already decremented on
        // order) need production.
        if ($variant === null || $stock >= 0) {
            return [];
        }

        $shortfall = -$stock;
        $quantity = max(self::MIN_BATCH_SIZE, $shortfall);

        if ($variant->size <= $plan->extrapolateMaxSize) {
            $projected = (int) ceil($plan->extrapolateMonths * VariantStats::for($variant)->averageMonthlySales());
            $quantity = max($quantity, $projected);
        }

        $quantity = $plan->round($quantity);

        return [
            $variant->id => [
                'variant_id' => $variant->id,
                'variant_label' => $variant->title ?? $variant->name ?? '#'.$variant->id,
                'stock' => $stock,
                'min_needed_quantity' => $quantity,
                'order_references' => $group
                    ->map(fn (OrderPosition $p) => $p->order?->order_number ?? $p->order?->reference ?? $p->order_id)
                    ->unique()->values()->all(),
                'per_variant_herbs' => $variant->herbsNeededFor($quantity) ?: [],
                'original_positions' => $group->pluck('id')->all(),
            ],
        ];
    }

    /**
     * Create a bottling from planned rows: one position per row at its planned
     * (already rounded) quantity.
     *
     * @param  Collection<int, array{variant_id: int, min_needed_quantity: int}>  $rows
     */
    public function createBottle(Collection $rows, User $user): Bottle
    {
        $bottle = Bottle::create([
            'date' => now(),
            'user_id' => $user->id,
            'note' => 'Auto bottle '.now()->format('Y-m-d H:i'),
        ]);

        foreach ($rows as $row) {
            $bottle->positions()->create([
                'variant_id' => $row['variant_id'],
                'count' => $row['min_needed_quantity'],
            ]);
        }

        return $bottle;
    }
}
