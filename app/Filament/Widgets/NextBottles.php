<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Bottles\Pages\EditBottle;
use App\Filament\Resources\Bottles\BottleResource;
use App\Models\Bottle;
use App\Models\Variant;
use Filament\Actions\Action;
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

    protected string $view = 'filament.widgets.next-bottles';

    protected static ?int $sort = 1;
    public int $maxSize = 200;
    public bool $groupSimilar = true;
    public int $coverMonths = 3;
    public int $maxPositions = 5;
    public int $minItems = 5;
    public Collection $groups;
    protected int|string|array $columnSpan = 'full';

    public function createAction(): Action
    {
        return Action::make('create')
            ->action(fn(array $arguments) => $this->createGroup($arguments['index']));
    }

    public function createGroup(int $index): void
    {
        /** @var Bottle $bottle */
        $bottle = Bottle::create([
            'user_id' => auth()->id(),
            'date' => now(),
        ]);

        foreach ($this->groups[$index] as $pos) {
            $bottle->positions()->create([
                'bottle_id' => $bottle->id,
                'variant_id' => $pos['variant_id'],
                'count' => $pos['count'],
            ]);
        }

        $this->redirect(EditBottle::getUrl(['record' => $bottle->id]), true);
    }

    protected function getViewData(): array
    {
        /** @var Collection<Variant> $variants */
        $variants = Variant::where('size', '<=', $this->maxSize)
            ->whereRelation('product', 'exclude_from_statistics', false)
            ->whereRelation('product.type', 'exclude_from_statistics', false)
            ->get();

        [$noStock, $hasStock] = $variants->partition('stock', '<=', 0);

        $noStockSorted = $noStock->sortBy->next_sale->take($this->maxPositions * 3);

        $variantGroups = $this->groupVariants($noStockSorted, $this->maxPositions, $this->groupSimilar);

        $this->groups = $variantGroups->map->map(fn(Variant $v) => [
            'variant_id' => $v->id,
            'count' => max($this->minItems, intval($this->coverMonths * $v->average_monthly_sales - $v->stock))
        ]);

        return [
            'sizes' => Variant::pluck('size')->unique()->sort(),
        ];
    }

    /**
     * Group variants by product id while trying to preserve initial ordering
     *
     * @param Collection<Variant> $variants
     * @param int $maxPositions
     * @param bool $groupSimilar
     * @return Collection<Collection<Variant>>
     */
    function groupVariants(Collection $variants, int $maxPositions, bool $groupSimilar): Collection
    {
        $groups = collect();

        if (!$groupSimilar) {
            // Just chunk in order
            return $variants->chunk($maxPositions);
        }

        $buckets = $variants->groupBy('product_id')->map->keyBy('id');
        $variants = $variants->keyBy('id');

        $group = 0;
        $elems = 0;
        while ($variants->isNotEmpty()) {
            $groups[$group] = collect();
            while ($elems < $maxPositions && $variants->isNotEmpty()) {
                $model = $variants->first();

                if ($elems > 0) {
                    $lastBucket = $buckets->get($groups[$group][$elems - 1]->product_id);
                    if ($lastBucket->isNotEmpty()) $model = $lastBucket->first();
                }

                $variants->forget($model->id);
                $buckets[$model->product_id]->forget($model->id);

                $groups[$group][$elems++] = $model;
            }

            $group++;
            $elems = 0;
        }

        return $groups;
    }

}
