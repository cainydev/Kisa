<?php

namespace App\Livewire;

use App\Filament\Tables\Columns\BagAmountColumn;
use App\Models\Herb;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithoutUrlPagination;

class BagSelectTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;
    use WithoutUrlPagination;

    #[Reactive, Locked]
    public ?int $herb = null;

    #[Modelable]
    public ?string $selected = null;

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        if (!$this->herb) return $table;

        return $table
            ->relationship(fn() => Herb::find($this->herb)->bags())
            ->inverseRelationship('herb')
            ->selectable()
            ->maxSelectableRecords(1)
            ->trackDeselectedRecords(false)
            ->deselectAllRecordsWhenFiltered(false)
            ->currentSelectionLivewireProperty('selected')
            ->disabledSelection(false)
            ->paginated(false)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('herb.name')
                            ->weight(FontWeight::SemiBold),
                        TextColumn::make('specification'),
                    ])->space(true),
                    TextColumn::make('bestbefore')
                        ->icon(Heroicon::Calendar)
                        ->badge()
                        ->tooltip(fn(Carbon $state) => $state->format('d.m.Y'))
                        ->formatStateUsing(fn() => '')
                        ->extraAttributes(['class' => '*:py-1 *:px-2'])
                        ->color(fn(Carbon $state) => $state->isNowOrPast() ? 'danger' : 'gray')
                        ->grow(false),
                    TextColumn::make('charge')
                        ->badge()
                        ->color('primary')
                        ->extraAttributes(['class' => '*:py-1 *:px-2'])
                        ->icon(Heroicon::Hashtag)
                        ->grow(false),
                    BagAmountColumn::make('size')
                        ->grow(),
                ])
            ]);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div wire:key="bag-select-table-wrapper-{{ $this->getId() }}">
                {{ $this->table }}
            </div>
        BLADE;
    }
}
