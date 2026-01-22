<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Filament\Resources\Suppliers\Pages\ManageSuppliers;
use App\Models\BioInspector;
use App\Models\Supplier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $modelLabel = 'Lieferant';
    protected static ?string $pluralModelLabel = 'Liefertanten';

    protected static ?string $recordTitleAttribute = 'company';

    protected static string|\UnitEnum|null $navigationGroup = 'Metadaten';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('company')
                    ->label("Firma")
                    ->required()
                    ->maxLength(255),
                TextInput::make('shortname')
                    ->label("Kurzname")
                    ->required()
                    ->maxLength(255),
                TextInput::make('contact')
                    ->label("Kontaktperson")
                    ->maxLength(255),
                TextInput::make('email')
                    ->label("Email")
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label("Telefon")
                    ->tel()
                    ->maxLength(255),
                TextInput::make('website')
                    ->label("Webseite")
                    ->maxLength(255),
                Select::make('bio_inspector_id')
                    ->label("Kontrollstelle")
                    ->relationship('bioInspector')
                    ->getOptionLabelFromRecordUsing(fn(BioInspector $record): string => "{$record->company} ({$record->label})")
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company')
                    ->label("Firma")
                    ->searchable(),
                TextColumn::make('contact')
                    ->label("Kontaktperson")
                    ->searchable(),
                TextColumn::make('email')
                    ->label("Email")
                    ->searchable(),
                TextColumn::make('phone')
                    ->label("Telefon")
                    ->searchable(),
                TextColumn::make('website')
                    ->label("Webseite")
                    ->url(fn(Supplier $record): string => str($record->website)->start("https://"))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn(Supplier $record): string => str($record->website)->replaceFirst('www.', ''))
                    ->searchable(),
                TextColumn::make('bioInspector.company')
                    ->label("Kontrollstelle")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageSuppliers::route('/'),
        ];
    }
}
