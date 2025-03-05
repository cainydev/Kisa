<?php

namespace App\Livewire;

use App\Models\BottlePosition;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class BottlePositionList extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    /**
     * @var Collection<BottlePosition> $positions
     */
    public Collection $positions;

    public function mount(Collection|array|null $pos): void
    {
        $this->positions = collect($pos);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BottlePosition::query()->whereIn('id', $this->positions->pluck('id')))
            ->paginated(false)
            ->columns([
                TextColumn::make('count')
                    ->formatStateUsing(fn($state) => "{$state}x"),
                TextColumn::make('variant.product.name'),
                TextColumn::make('variant.size')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state . 'g'),

            ])
            ->actions([

            ]);
    }

    public function render(): View
    {
        return view('livewire.bottle-position-list');
    }
}
