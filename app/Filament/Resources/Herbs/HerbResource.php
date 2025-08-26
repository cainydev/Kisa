<?php

namespace App\Filament\Resources\Herbs;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Herbs\Pages\ListHerbs;
use App\Filament\Resources\Herbs\Pages\CreateHerb;
use App\Filament\Resources\Herbs\Pages\EditHerb;
use App\Filament\Resources\HerbResource\Pages;
use App\Filament\Resources\HerbResource\RelationManagers;
use App\Models\Herb;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HerbResource extends Resource
{
    protected static ?string $model = Herb::class;

    protected static ?string $modelLabel = 'Rohstoff';

    protected static ?string $pluralModelLabel = 'Rohstoffe';

    protected static ?string $recordTitleAttribute = 'fullname';

    protected static string | \UnitEnum | null $navigationGroup = 'Produkte';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube-transparent';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Bezeichnung')
                    ->required()
                    ->maxLength(255),
                TextInput::make('fullname')
                    ->label('Vollständige Bezeichnung')
                    ->required()
                    ->maxLength(255),
                Select::make('supplier_id')
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
                TextColumn::make('fullname')
                    ->label('Bezeichnung')
                    ->searchable()->sortable(),
                TextColumn::make('supplier.shortname')
                    ->label('Standardlieferant')
                    ->searchable()->sortable()
            ])
            ->recordActions([
                DeleteAction::make(),
                EditAction::make(),
                Action::make('Statistik'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
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
            'index' => ListHerbs::route('/'),
            'create' => CreateHerb::route('/create'),
            'edit' => EditHerb::route('/{record}/edit'),
        ];
    }
}
