<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerbResource\Pages;
use App\Filament\Resources\HerbResource\RelationManagers;
use App\Models\Herb;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HerbResource extends Resource
{
    protected static ?string $model = Herb::class;

    protected static ?string $modelLabel = 'Rohstoff';

    protected static ?string $pluralModelLabel = 'Rohstoffe';

    protected static ?string $recordTitleAttribute = 'fullname';

    protected static ?string $navigationGroup = 'Produkte';

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Bezeichnung')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('fullname')
                    ->label('Vollständige Bezeichnung')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('supplier_id')
                    ->label('Standardlieferant')
                    ->relationship('supplier', 'shortname')
                    ->selectablePlaceholder(false)
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Bezeichnung')
                    ->searchable()->sortable(),
                Tables\Columns\TextColumn::make('supplier.shortname')
                    ->label('Standardlieferant')
                    ->searchable()->sortable()
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('Statistik'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListHerbs::route('/'),
            'create' => Pages\CreateHerb::route('/create'),
            'edit' => Pages\EditHerb::route('/{record}/edit'),
        ];
    }
}
