<?php

namespace App\Livewire;

use App\Models\BottlePosition;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Actions\Action;
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

    public Collection $positions;

    public function table(Table $table): Table
    {
        $ids = $this->positions->pluck('id');

        return $table
            ->query(BottlePosition::with('variant')->whereIn('id', $ids))
            ->paginated(false)
            ->columns([
                TextColumn::make('count')
                    ->label('')
                    ->formatStateUsing(fn($state) => "{$state}x"),
                TextColumn::make('variant.product.name')
                    ->label('Produkt'),
                TextColumn::make('variant.size')
                    ->badge()
                    ->label('')
                    ->grow()
                    ->formatStateUsing(fn($state) => $state . 'g'),
                TextColumn::make('variant.stock')
                    ->label('Bestand'),
                TextColumn::make('charge')
                    ->label('Charge')
                    ->copyable(),
            ])
            ->actions([
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

    public function render(): View
    {
        return view('livewire.bottle-position-list');
    }
}
