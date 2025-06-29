<?php

namespace App\Filament\Widgets;

use App\Models\BottlePosition;
use App\Models\Variant;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class NextBottles extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.widgets.next-bottles';

    protected static ?int $sort = 1;
    public int $maxSize = 200;
    public bool $groupSimilar = true;
    public int $coverMonths = 3;
    public int $maxPositions = 5;
    public int $minItems = 5;
    public array $groups = [];
    protected int|string|array $columnSpan = 'full';

    public function createGroup(int $index): void
    {

    }

    protected function getViewData(): array
    {
        /** @var Collection<Variant> $variants */
        $variants = Variant::where('size', '<=', $this->maxSize)->get();

        [$noStock, $hasStock] = $variants->partition('stock', '<=', 0);

        $noStockSorted = $noStock->sortBy->next_sale->take($this->maxPositions * $this->minItems);

        $positions = $noStockSorted->map(fn(Variant $v) => new BottlePosition([
            'variant_id' => $v->id,
            'count' => max($this->minItems, intval($this->coverMonths * $v->average_monthly_sales - $v->stock))
        ]));


        $this->groups = $this->groupModels($positions, $this->maxPositions, $this->groupSimilar);

        return [
            'sizes' => Variant::pluck('size')->unique()->sort(),
        ];
    }

    function groupModels(Collection $models, int $maxPositions, bool $groupSimilar): array
    {
        $groups = [];

        if (!$groupSimilar) {
            // Just chunk in order
            return $models->chunk($maxPositions)->values()->all();
        }

        // 1. Group by product_id, preserve order within each group
        $buckets = $models->groupBy('variant.product_id')->map(function ($bucket) {
            return $bucket->values();
        });

        // 2. Prepare a working copy of all items, preserving order
        $remaining = $models->values();

        while ($remaining->isNotEmpty()) {
            // Try to find the product_id with the most remaining items at the start of the list
            $firstProductId = $remaining->first()->product_id;
            $bucket = $buckets->get($firstProductId, collect());

            // Pull as many as possible from the same product_id at the head
            $group = $bucket->splice(0, $maxPositions);

            // Remove these from remaining
            $idsToRemove = $group->pluck('id')->all();
            $remaining = $remaining->reject(function ($item) use ($idsToRemove) {
                return in_array($item->id, $idsToRemove);
            })->values();

            // If not enough, fill with the next-in-line items (regardless of product_id)
            if ($group->count() < $maxPositions && $remaining->isNotEmpty()) {
                $toFill = $maxPositions - $group->count();
                $fillers = $remaining->splice(0, $toFill);
                $group = $group->concat($fillers);

                // Remove fillers from buckets too
                foreach ($fillers as $filler) {
                    $buckets[$filler->variant->product_id] = $buckets[$filler->variant->product_id]->reject(fn($item) => $item->id === $filler->id)->values();
                }
            }

            $groups[] = $group->all();
        }

        return $groups;
    }

}
