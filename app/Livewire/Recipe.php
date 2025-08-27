<?php

namespace App\Livewire;

use App\Filament\Forms\Components\TableSelect;
use App\Filament\Tables\BagTable;
use App\Models\BottlePosition;
use App\Models\Ingredient;
use App\Models\RecipeIngredient;
use Exception;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Recipe extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var Collection<BottlePosition> */
    public Collection $positions;

    /** @var array<int, int> */
    public array $bags = [];

    public ?int $herb = null;

    public function mount(Collection $positions): void
    {
        $this->positions = $positions;

        foreach ($this->ingredients as $ingredient) {
            $herbId = (string)$ingredient->herb_id;
            $pre = $this->positions->first()?->getBagFor($ingredient->herb)?->id;
            if ($pre) {
                $this->bags[$herbId] = $pre;
            }
        }

        $this->herb = $this->ingredients->first()?->herb->id;

        $this->form->fill([
            'bags' => $this->bags,
        ]);
    }

    public function updatedBags($value): void
    {
        $old = Ingredient::whereIn('bottle_position_id', $this->positions->pluck('id'))
            ->where('herb_id', $this->herb)
            ->pluck('bag_id', 'herb_id')
            ->toArray();

        if (empty(array_diff_assoc($this->bags, $old))) return;

        Ingredient::whereIn('bottle_position_id', $this->positions->pluck('id'))
            ->where('herb_id', $this->herb)
            ->delete();

        if ($value !== null) {
            Ingredient::insert($this->positions->map(fn(BottlePosition $position) => [
                'bottle_position_id' => $position->id,
                'herb_id' => $this->herb,
                'bag_id' => $value
            ])->all());
        }
    }

    /**
     * @throws Exception
     */
    public function form(Schema $schema): Schema
    {
        if (!$this->herb) return $schema;

        return $schema
            ->components([
                TableSelect::make("bags.{$this->herb}")
                    ->live()
                    ->hiddenLabel()
                    ->tableConfiguration(BagTable::class)
                    ->tableArguments(['herb_id' => $this->herb])
            ]);
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

    public function firstStep(): int
    {
        $startStep = $this->ingredients->takeWhile(function (RecipeIngredient $i) {
                return $this->positions->every(function (BottlePosition $p) use ($i) {
                    return $p->hasBagFor($i->herb);
                });
            })->count() + 1;

        return $startStep >= $this->ingredients->count() ? 1 : $startStep;
    }

    public function render(): View
    {
        return view('livewire.recipe');
    }
}
