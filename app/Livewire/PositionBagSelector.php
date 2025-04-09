<?php

namespace App\Livewire;

use App\Models\Bag;
use App\Models\BottlePosition;
use App\Models\Herb;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Illuminate\Support\Collection;

class PositionBagSelector extends Field
{
    /** @var Collection */
    protected $positions;

    /** @var Herb */
    protected $herb;

    protected string $view = 'filament.components.position-bag-selector';

    public function forHerb(Herb|int $herb): static
    {
        $this->herb = $herb instanceof Herb ? $herb : Herb::find($herb);

        $actions = $this->herb->bags()->withTrashed()->get()->map(function (Bag $bag) {
            return Action::make("select-bag-$bag->id")
                ->label('Auswählen')
                ->disabled(fn($state) => $state === $bag->id && $bag->trashed())
                ->icon(fn($state) => $state === $bag->id ? 'heroicon-s-check' : 'heroicon-s-arrows-right-left')
                ->color(fn($state) => $state === $bag->id ? 'success' : 'gray')
                ->action(function ($state) use ($bag) {
                    if ($state === $bag->id) {
                        $this->unselectBag();
                    } else {
                        $this->selectBag($bag->id);
                    }
                });
        })->all();

        $this->registerActions($actions);

        if (!empty($this->getPositions())) $this->state($this->getDefaultState());

        return $this;
    }

    public function unselectBag(): void
    {
        $bagBefore = $this->getState();

        $this->positions->each(function (BottlePosition $p) {
            $p->ingredients()
                ->where('herb_id', $this->herb->id)
                ->delete();
            $p->refresh();
        });

        $this->state(null);
        $this->getLivewire()->validate();

        $this->getLivewire()->dispatch('positions.updated');
        if ($bagBefore) $this->getLivewire()->dispatch('bag.' . $bagBefore . '.updated');
    }

    public function selectBag(int $bagId): void
    {
        $bagBefore = $this->getState();

        $this->positions->each(function (BottlePosition $p) use ($bagId) {
            $p->ingredients()->updateOrCreate(['herb_id' => $this->herb->id], [
                'bag_id' => $bagId,
            ]);
            $p->refresh();
        });

        $this->state($bagId);
        $this->getLivewire()->validate();

        $this->getLivewire()->dispatch('positions.updated');
        $this->getLivewire()->dispatch('bag.' . $bagId . '.updated');
        if ($bagBefore) $this->getLivewire()->dispatch('bag.' . $bagBefore . '.updated');
    }

    public function getPositions(): ?Collection
    {
        return $this->positions;
    }

    public function getDefaultState(): mixed
    {
        return $this->evaluate($this->defaultState, [
            'herb' => $this->herb,
            'positions' => $this->positions,
        ]);
    }

    public function getHerb(): ?Herb
    {
        return $this->herb;
    }

    public function applyToPositions(Collection|array $positions): static
    {
        $this->positions = collect($positions);

        return $this;
    }

    protected function setUp(): void
    {
        $this->hiddenLabel();
        $this->default(null);

        $this->registerListeners([
            'position-bag-selector::select' => [
                function (PositionBagSelector $component, string $statePath, int $bag): void {
                    $this->selectBag($bag);
                },
            ],
        ]);
    }
}
