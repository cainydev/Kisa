<?php

namespace App\Filament\Resources\BioInspectors;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BioInspectors\Pages\ManageBioInspectors;
use App\Filament\Resources\BioInspectorResource\Pages;
use App\Filament\Resources\BioInspectorResource\RelationManagers;
use App\Models\BioInspector;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BioInspectorResource extends Resource
{
    protected static ?string $model = BioInspector::class;

    protected static ?string $modelLabel = 'Bio-Kontrollstelle';
    protected static ?string $pluralModelLabel = 'Bio-Kontrollstellen';

    protected static ?string $recordTitleAttribute = 'company';

    protected static string | \UnitEnum | null $navigationGroup = 'Metadaten';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company')
                    ->label("Firma")
                    ->required()
                    ->maxLength(255),
                TextInput::make('label')
                    ->label("Kennzeichnung")
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company')
                    ->label("Firma")
                    ->searchable(),
                TextColumn::make('label')
                    ->label("Kennzeichnung")
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getPages(): array
    {
        return [
            'index' => ManageBioInspectors::route('/'),
        ];
    }
}
