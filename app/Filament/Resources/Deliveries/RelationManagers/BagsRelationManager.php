<?php

namespace App\Filament\Resources\Deliveries\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BagsRelationManager extends RelationManager
{
    protected static string $relationship = 'bags';
    protected static ?string $title = 'Positionen';

    protected static ?string $modelLabel = 'Position';
    protected static ?string $pluralModelLabel = 'Positionen';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('herb_id')
                    ->relationship('herb', 'fullname')
                    ->searchable()
                    ->label("Rohstoff")
                    ->required(),
                TextInput::make('specification')
                    ->label("Spezifikation")
                    ->hint("z.B. äqypt. BIO geschnitten")
                    ->required()
                    ->maxLength(255),
                TextInput::make('charge')
                    ->label('Chargennummer des Herstellers')
                    ->required()
                    ->maxLength(255),
                TextInput::make('size')
                    ->label('Gebindegröße')
                    ->numeric()
                    ->suffix('g')
                    ->required(),
                DatePicker::make('bestbefore')
                    ->required()
                    ->default(now()->addYears(2)),
                DatePicker::make('steamed'),
                Toggle::make('bio')
                    ->label('Bio-Zertifiziert')
                    ->inline(false)
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('herb.fullname')
                    ->label("Rohstoff"),
                TextColumn::make('size')
                    ->label("Gebinde")
                    ->formatStateUsing(fn ($state) => "{$state}g"),
                TextColumn::make('charge')
                    ->label("Charge"),
                IconColumn::make('bio')
                    ->boolean()
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
