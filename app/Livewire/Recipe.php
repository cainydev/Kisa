<?php

namespace App\Livewire;

use App\Models\BottlePosition;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Recipe extends Component
{
    /** @var Collection<BottlePosition> */
    public Collection $positions;

    /** @var array<int, int> */
    public array $bags = [];

    public ?int $herb = null;

    public function mount(Collection $positions): void
    {
        $this->positions = $positions;

        $this->bags = Ingredient::whereIn('bottle_position_id', $this->positions->pluck('id'))
            ->pluck('bag_id', 'herb_id')
            ->toArray();

        foreach ($this->ingredients as $ingredient) {
            if (!array_key_exists($ingredient->herb_id, $this->bags))
                $this->bags[$ingredient->herb_id] = null;
        }

        $this->herb = $this->ingredients->first()?->herb_id ?? null;
    }

    public function select(int $herb, ?int $bag): void
    {
        $old = $this->bags[$herb];
        $this->bags[$herb] = $bag;

        if ($this->bags[$herb] !== $old) {
            Ingredient::whereIn('bottle_position_id', $this->positions->pluck('id'))
                ->where('herb_id', $this->herb)
                ->delete();

            if ($bag !== null) {
                Ingredient::insert($this->positions->map(fn(BottlePosition $position) => [
                    'bottle_position_id' => $position->id,
                    'herb_id' => $this->herb,
                    'bag_id' => $bag
                ])->all());
            }
        }
    }

    #[Computed]
    public function ingredients(): Collection
    {
        if ($this->positions->isEmpty()) {
            return collect();
        }
        return $this->positions->first()->variant->product->recipeIngredients->sortByDesc('percentage');
    }

    #[Computed]
    public function steps(): array
    {
        return $this->ingredients->map(fn(RecipeIngredient $i) => [
            'key' => $i->herb_id,
            'label' => $i->herb->name,
            'description' => "{$this->amounts[$i->herb_id]}g"
        ])->all();
    }

    #[Computed]
    public function completedSteps(): array
    {
        return array_keys(array_filter($this->bags));
    }

    #[Computed]
    public function totalGramms(): int
    {
        return $this->positions
            ->map(fn(BottlePosition $p) => $p->variant->size * $p->count)
            ->sum();
    }

    #[Computed]
    public function amounts(): array
    {
        return $this->positions->first()->variant->product->recipeIngredients->mapWithKeys(function (RecipeIngredient $i) {
            return [$i->herb_id => $this->positions->sum(fn(BottlePosition $p) => $p->count * $p->variant->size * ($i->percentage / 100.))];
        })->all();
    }

    public function render(): View
    {
        return view('livewire.recipe');
    }
}
