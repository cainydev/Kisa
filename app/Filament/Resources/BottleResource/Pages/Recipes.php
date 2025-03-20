<?php

namespace App\Filament\Resources\BottleResource\Pages;

use App\Filament\Resources\BottleResource;
use App\Models\BottlePosition;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use function in_array;
use function url;

class Recipes extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BottleResource::class;

    protected static string $view = 'filament.resources.bottle-resource.pages.recipes';

    public bool $grouped;

    #[Url(as: 'tab', keep: true)]
    public int $activeTab;

    #[Url(as: 'group', keep: true)]
    public int $activeGroupedTab;

    public Collection $positions;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->grouped = true;

        $groups = $this->record->positions->groupBy('variant.product_id');

        $this->activeTab = $this->activeTab ?? $this->record->positions->first()->id;
        $this->activeGroupedTab = $this->activeGroupedTab ?? $groups->keys()->first();

        $this->refreshPositions();
    }

    public function refreshPositions(): void
    {
        $this->positions = $this->grouped
            ? $this->record->positions->where('variant.product_id', $this->activeGroupedTab)
            : collect(BottlePosition::find($this->activeTab));
    }

    public function updated($property): void
    {
        if (in_array($property, ['activeTab', 'activeGroupedTab', 'grouped'])) {
            $this->refreshPositions();
        }
    }

    /**
     * Change the page title.
     *
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return "Rezepte {$this->record->getAttribute('date')->format('d.m.Y')}";
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Zurück')
                ->color('gray')
                ->url(url()->previous()),
            Action::make('group')
                ->color(fn() => $this->grouped ? 'info' : 'gray')
                ->label(fn() => $this->grouped ? 'Gruppiert' : 'Nicht gruppiert')
                ->icon(fn() => $this->grouped ? 'heroicon-s-link' : 'heroicon-s-link-slash')
                ->action(fn() => $this->grouped = !$this->grouped),
        ];
    }
}
