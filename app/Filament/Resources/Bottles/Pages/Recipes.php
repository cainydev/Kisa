<?php

namespace App\Filament\Resources\Bottles\Pages;

use App\Filament\Resources\Bottles\BottleResource;
use App\Models\Bottle;
use App\Models\BottlePosition;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use function route;

class Recipes extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected static string $resource = BottleResource::class;

    /** @var Bottle */
    public Bottle $bottle;

    public array $bags = [];

    #[Url(as: 'grouped', keep: true)]
    public bool $grouped = true;

    #[Url(as: 'tab', keep: true)]
    public string|null $activeTab = null;

    #[Url(as: 'group', keep: true)]
    public string|null $activeGroupedTab = null;

    protected string $view = 'filament.resources.bottle-resource.pages.recipes';

    public function mount(int $record): void
    {
        $this->bottle = Bottle::find($record);
        $this->recalculateTabs();
    }

    public function recalculateTabs(): void
    {
        if ($this->bottle->positions->isEmpty()) {
            $this->activeTab = null;
            $this->activeGroupedTab = null;
            return;
        }

        if (!$this->activeTab || !$this->bottle->positions->pluck('id')->contains($this->activeTab)) {
            $this->activeTab = $this->bottle->positions->first()->id;
        }

        if (!$this->activeGroupedTab || !$this->groups->keys()->contains($this->activeGroupedTab)) {
            if ($this->activeGroupedTab) {
                $groupCount = $this->groups->count();
                $ids = str($this->activeGroupedTab)->after('g-')->explode('-');

                $this->activeGroupedTab = $this->groups->filter(function (Collection $positions) use ($ids) {
                    return $positions->pluck('id')->contains($ids->first());
                })->keys()->first();

                if ($this->grouped)
                    Notification::make()
                        ->title('Gruppen zusammengeführt.')
                        ->body('Zwei Gruppen wurden automatisch zusammengeführt weil die Zutaten identisch sind.')
                        ->success()
                        ->send();
            } else {
                $this->activeGroupedTab = $this->groups->keys()->first();
            }
        }
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => BottlePosition::whereIn('id', $this->positions()->pluck('id'))->with(['variant.product']))
            ->columnManager(false)
            ->paginated(false)
            ->columns([
                TextColumn::make('count')
                    ->alignEnd()
                    ->label(''),
                TextColumn::make('times')->state('×')->label(''),
                TextColumn::make('variant.product.name')
                    ->label('Produkt'),
                TextColumn::make('variant.size')
                    ->label('')
                    ->formatStateUsing(fn($state) => "{$state}g")
                    ->grow()
                    ->badge(),
                TextColumn::make('variant.stock')
                    ->label('Bestand'),
                TextColumn::make('charge')
                    ->grow(),
            ])
            ->recordActions([
                Action::make('refresh')
                    ->label('Aktualisieren')
                    ->iconButton()
                    ->iconSize(IconSize::Large)
                    ->icon('heroicon-s-arrow-path')
                    ->action(function (BottlePosition $record) {
                        $record->refresh();
                        $record->charge = $record->getCharge();
                        $record->save();
                    }),
                Action::make('upload')
                    ->disabled(fn(BottlePosition $position) => $position->uploaded)
                    ->label(function (BottlePosition $position) {
                        return $position->uploaded ? 'Eingelagert' : 'Einlagern';
                    })
                    ->action(function (BottlePosition $record) {
                        $record->upload();
                    })
                    ->button()
                    ->color('gray')
                    ->icon('icon-billbee')
                    ->iconSize(IconSize::Large)
            ]);
    }

    #[Computed]
    public function positions()
    {
        if ($this->bottle->positions->isEmpty()) {
            return collect();
        }

        if ($this->grouped) {
            return $this->groups->get($this->activeGroupedTab);
        } else {
            return $this->bottle->positions->where('id', $this->activeTab);
        }
    }

    #[On('positions.updated')]
    public function refreshPositions(): void
    {
        unset($this->positions, $this->groups);
        $this->recalculateTabs();
    }

    #[Computed]
    public function groups()
    {
        if ($this->bottle->positions->isEmpty()) {
            return collect();
        }

        return $this->bottle->positions->groupBy(function ($item) {
            $attachedBags = $item->ingredients->pluck('bag_id')->sort();
            if ($attachedBags->isEmpty()) return "{$item->variant->product_id}";
            return "{$item->variant->product_id}-{$attachedBags->implode('-')}";
        })->mapWithKeys(fn(Collection $positions) => [
            "g-{$positions->pluck('id')->implode('-')}" => $positions
        ]);
    }

    /**
     * Change the page title.
     *
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return "Rezepte {$this->bottle->date->format('d.m.Y')}";
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Zurück')
                ->color('gray')
                ->url(route('filament.admin.resources.bottles.edit', ['record' => $this->bottle->id])),
            Action::make('group')
                ->color(fn() => $this->grouped ? 'info' : 'gray')
                ->label(fn() => $this->grouped ? 'Gruppiert' : 'Nicht gruppiert')
                ->icon(fn() => $this->grouped ? 'heroicon-s-link' : 'heroicon-s-link-slash')
                ->action(fn() => $this->grouped = !$this->grouped),
        ];
    }
}
