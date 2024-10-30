<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BioInspectorResource\Pages;
use App\Filament\Resources\BioInspectorResource\RelationManagers;
use App\Models\BioInspector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BioInspectorResource extends Resource
{
    protected static ?string $model = BioInspector::class;

    protected static ?string $modelLabel = 'Bio-Kontrollstelle';
    protected static ?string $pluralModelLabel = 'Bio-Kontrollstellen';

    protected static ?string $recordTitleAttribute = 'company';

    protected static ?string $navigationGroup = 'Metadaten';
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company')
                    ->label("Firma")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->label("Kennzeichnung")
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company')
                    ->label("Firma")
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label("Kennzeichnung")
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBioInspectors::route('/'),
        ];
    }
}
