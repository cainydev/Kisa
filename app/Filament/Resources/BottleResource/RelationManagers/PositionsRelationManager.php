<?php

namespace App\Filament\Resources\BottleResource\RelationManagers;

use App\Filament\Resources\BottleResource;
use App\Models\BottlePosition;
use App\Models\Variant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PositionsRelationManager extends RelationManager
{
    protected static string $relationship = 'positions';

    protected static string $model = BottlePosition::class;

    protected static ?string $modelLabel = 'Position';
    protected static ?string $pluralModelLabel = 'Positionen';

    protected static ?string $title = 'Positionen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Select::make('variant_id')
                        ->label('Produkt')
                        ->required()
                        ->live()
                        ->relationship('variant')
                        ->getOptionLabelFromRecordUsing(function (Variant $variant) {
                            return "{$variant->product->name} {$variant->size}g";
                        })
                        ->preload()
                        ->searchable()
                        ->grow(),
                    TextInput::make('count')
                        ->label('Anzahl')
                        ->required()
                        ->integer()
                        ->live()
                        ->default(1)
                        ->grow(false)
                        ->extraAttributes(['class' => 'w-24'])
                ])->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(function (BottlePosition $record) {
                return "{$record->variant->product->name} {$record->variant->size}g";
            })
            ->columns([
                TextColumn::make('count')
                    ->label(''),
                TextColumn::make('times')->state('×')->label(''),
                TextColumn::make('variant.product.name')
                    ->label('Produkt'),
                TextColumn::make('variant.size')
                    ->label('Variante')
                    ->alignCenter()
                    ->formatStateUsing(fn($state) => "{$state}g")
                    ->badge(),
                TextColumn::make('charge')
                    ->grow(),
                IconColumn::make('is_bottled')
                    ->label('Abgefüllt')
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(function (BottlePosition $record) {
                        return $record->hasAllBags();
                    }),
                IconColumn::make('uploaded')
                    ->label('Hochgeladen')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('recipes')
                    ->label('Zu den Rezepten')
                    ->icon('heroicon-s-book-open')
                    ->url(BottleResource::getUrl('recipes', [
                        'record' => $this->getOwnerRecord()->getKey()
                    ]))
                    ->color('gray'),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription("Möchten Sie diese Position wirklich löschen? Falls bereits Rohstoffe zugewiesen wurden, werden diese vorher als nicht mehr zugewiesen markiert."),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
