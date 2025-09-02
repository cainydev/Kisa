<?php

namespace App\Filament\Resources\DeliveryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BagsRelationManager extends RelationManager
{
    protected static string $relationship = 'bags';
    protected static ?string $title = 'Positionen';

    protected static ?string $modelLabel = 'Position';
    protected static ?string $pluralModelLabel = 'Positionen';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('herb_id')
                    ->relationship('herb', 'fullname')
                    ->searchable()
                    ->label("Rohstoff")
                    ->required(),
                Forms\Components\TextInput::make('specification')
                    ->label("Spezifikation")
                    ->hint("z.B. äqypt. BIO geschnitten")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('charge')
                    ->label('Chargennummer des Herstellers')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('size')
                    ->label('Gebindegröße')
                    ->numeric()
                    ->suffix('g')
                    ->required(),
                Forms\Components\DatePicker::make('bestbefore')
                    ->required()
                    ->default(now()->addYears(2)),
                Forms\Components\DatePicker::make('steamed'),
                Forms\Components\Toggle::make('bio')
                    ->label('Bio-Zertifiziert')
                    ->inline(false)
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $query->withTrashed();
            })
            ->columns([
                Tables\Columns\TextColumn::make('herb.fullname')
                    ->label("Rohstoff"),
                Tables\Columns\TextColumn::make('size')
                    ->label("Gebinde")
                    ->formatStateUsing(fn($state) => "{$state}g"),
                Tables\Columns\TextColumn::make('charge')
                    ->label("Charge"),
                Tables\Columns\IconColumn::make('bio')
                    ->boolean()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->label('Entsorgen'),
                Tables\Actions\RestoreAction::make()->label('Aus dem Müll holen'),
            ])
            ->bulkActions([]);
    }
}
