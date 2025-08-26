<?php

namespace App\Livewire;

use App\Filament\Forms\Components\TableSelect;
use App\Filament\Tables\BagTable;
use App\Models\BottlePosition;
use App\Models\Herb;
use App\Models\RecipeIngredient;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
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

        $this->form->fill([
            'bags' => $this->bags,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $startStep = $this->ingredients->takeWhile(function (RecipeIngredient $i) {
                return $this->positions->every(function (BottlePosition $p) use ($i) {
                    return $p->hasBagFor($i->herb);
                });
            })->count() + 1;

        if ($startStep > $this->ingredients->count()) {
            $startStep = 1;
        }

        return $schema
            ->components([
                Tabs::make()
                    ->components(
                        $this->ingredients->map(fn(RecipeIngredient $i) => Tabs\Tab::make($i->herb->name)
                            ->id("tab-{$i->herb_id}")
                            ->model(Herb::class)
                            ->lazy()
                            ->badge($this->totalGramms * $i->percentage / 100.0 . 'g')
                            ->badgeIcon(fn($state) => $state !== null ? Heroicon::Check : Heroicon::XMark)
                            ->badgeColor(fn($state) => $state !== null ? 'success' : 'gray')
                            ->statePath("bags.{$i->herb->id}")
                            ->schema([
                                TableSelect::make('bag_id')
                                    ->statePath(null)
                                    ->live()
                                    ->hiddenLabel()
                                    ->belowContent(Schema::center([view('components.bag-amount-bar-legend')]))
                                    ->tableConfiguration(BagTable::class)
                                    ->tableArguments(['herb_id' => $i->herb->id]),
                            ])
                        )->all())
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

    public function render(): View
    {
        return view('livewire.recipe');
    }
}
