<?php

namespace App\Filament\Resources\Bottles\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Flex;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Bottles\BottleResource;
use App\Models\BottlePosition;
use App\Models\Variant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Flex::make([
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
            ->paginated(false)
            ->columns([
                TextColumn::make('count')
                    ->alignEnd()
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
                Action::make('recipes')
                    ->label('Zu den Rezepten')
                    ->icon('heroicon-s-book-open')
                    ->url(BottleResource::getUrl('recipes', [
                        'record' => $this->getOwnerRecord()->getKey()
                    ]))
                    ->color('gray'),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription("Möchten Sie diese Position wirklich löschen? Falls bereits Rohstoffe zugewiesen wurden, werden diese vorher als nicht mehr zugewiesen markiert."),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
