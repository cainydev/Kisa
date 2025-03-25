<?php

namespace App\Livewire;

use App\Livewire\CustomWizard\Step;
use App\Models\BottlePosition;
use App\Models\RecipeIngredient;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use function dd;

class Recipe extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $selectedBag = [];

    public Collection $positions;

    public int $activeTab;

    public function mount(Collection $positions): void
    {
        $this->positions = $positions;
        $this->activeTab = $this->ingredients->first()->herb_id;

        $this->selectedBag = [];
        foreach ($this->ingredients as $ingredient) {
            $this->selectedBag[$ingredient->herb_id] = $this->positions->first()?->getBagFor($ingredient->herb)?->id;
        }
    }

    public function form(Form $form): Form
    {
        $startStep = $this->ingredients->takeWhile(function (RecipeIngredient $i) {
                return $this->positions->every(function (BottlePosition $p) use ($i) {
                    return $p->hasBagFor($i->herb);
                });
            })->count() + 1;

        if ($startStep > $this->ingredients->count()) {
            $startStep = 1;
        }

        $steps = $this->ingredients->map(function (RecipeIngredient $i) {
            return Step::make($i->herb->name)
                ->description($this->totalGramms * $i->percentage / 100.0 . 'g')
                ->completedWhen(fn($state) => $state !== null)
                ->live(true)
                ->completedIcon('heroicon-s-check')
                ->schema([
                    PositionBagSelector::make('')
                        ->statePath(null)
                        ->live()
                        ->afterStateUpdated(fn($state) => dd("afterStateUpdated", $state))
                        ->afterStateHydrated(fn($state) => dd("afterStateHydrated", $state))
                        ->forHerb($i->herb)
                        ->applyToPositions($this->positions)
                        ->default(fn($herb, $positions) => $positions->first()->getBagFor($herb)?->id)
                ])->statePath($i->herb_id);
        })->all();

        return $form
            ->schema([
                CustomWizard::make($steps)
                    ->footerContent(ViewField::make('footer')->view('components.bag-amount-bar-legend'))
                    ->startOnStep($startStep)
                    ->previousAction(function (Action $action) {
                        $action->color('gray')
                            ->icon('heroicon-s-arrow-left')
                            ->iconPosition(IconPosition::Before);
                    })
                    ->nextAction(function (Action $action) {
                        $action->color('gray')
                            ->icon('heroicon-s-arrow-right')
                            ->iconPosition(IconPosition::After);
                    })
                    ->live(true)
                    ->skippable()
            ])->statePath('selectedBag');
    }

    #[Computed]
    public function totalGramms(): int
    {
        return $this->positions
            ->map(fn(BottlePosition $p) => $p->variant->size * $p->count)
            ->sum();
    }

    #[Computed]
    public function ingredients(): Collection
    {
        if ($this->positions->isEmpty()) {
            return collect();
        } else {
            return $this->positions->first()->variant->product->recipeIngredients->sortByDesc('percentage');
        }
    }

    public function render(): View
    {
        return view('livewire.recipe');
    }
}
