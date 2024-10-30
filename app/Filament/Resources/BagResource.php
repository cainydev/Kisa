<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BagResource\Pages;
use App\Livewire\BagAmountBar;
use App\Models\Bag;
use Filament\Forms;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BagResource extends Resource
{
    protected static ?string $model = Bag::class;

    protected static ?string $modelLabel = 'Sack';
    protected static ?string $pluralModelLabel = 'Säcke';

    protected static ?string $recordTitleAttribute = 'herb.fullname';

    protected static ?string $navigationGroup = 'Bestand';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(BagAmountBar::class)
                    ->columnSpan(2)
                    ->hiddenOn(['create'])
                    ->hidden(fn (?Bag $record) => $record === null),
                Forms\Components\Select::make('herb_id')
                    ->label('Rohstoff')
                    ->required()
                    ->relationship('herb', 'fullname')
                    ->searchable(),
                Forms\Components\Toggle::make('bio')
                    ->inline(false),
                Forms\Components\TextInput::make('charge')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('size')
                    ->required()
                    ->label('Gebindegröße')
                    ->suffix('g')
                    ->numeric(),
                Forms\Components\TextInput::make('specification')
                    ->required()
                    ->label('Spezifikation')
                    ->maxLength(255),
                Forms\Components\TextInput::make('trashed')
                    ->required()
                    ->label('Ausschuss')
                    ->numeric()
                    ->suffix('g')
                    ->default(0),
                Forms\Components\DatePicker::make('bestbefore')
                    ->label('MHD')
                    ->required(),
                Forms\Components\DatePicker::make('steamed')
                    ->label('Letzte Dampfbehandlung'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('charge')
                    ->searchable(),
                Tables\Columns\TextColumn::make('herb')
                    ->label("Inhalt")
                    ->formatStateUsing(function (Bag $record) {
                        $herb = $record->herb;
                        return "$herb->name $record->specification";
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('bio')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Gebinde')
                    ->numeric()
                    ->formatStateUsing(function (Bag $record) {
                        return $record->getSizeInKilo();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('redisCurrent')
                    ->label("Gewicht aktuell")
                    ->formatStateUsing(function ($state) {
                        return $state . 'g';
                    }),
                Tables\Columns\TextColumn::make('delivery.supplier.shortname')
                    ->placeholder("Nicht zugeordnet")
                    ->label("Lieferant"),
                Tables\Columns\TextColumn::make('bestbefore')
                    ->label("MHD")
                    ->date("d.m.Y")
                    ->sortable(),
                Tables\Columns\TextColumn::make('steamed')
                    ->label("Letzte Dampfbehandlung")
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label("Erstellt am")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label("Geändert am")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBags::route('/'),
            'view' => Pages\ViewBag::route('/{record}'),
            'edit' => Pages\EditBag::route('/{record}/edit'),
        ];
    }
}
