<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\BioInspector;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $modelLabel = 'Lieferant';
    protected static ?string $pluralModelLabel = 'Liefertanten';

    protected static ?string $recordTitleAttribute = 'company';

    protected static ?string $navigationGroup = 'Metadaten';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('company')
                    ->label("Firma")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('shortname')
                    ->label("Kurzname")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact')
                    ->label("Kontaktperson")
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label("Email")
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label("Telefon")
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('website')
                    ->label("Webseite")
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('bio_inspector_id')
                    ->label("Kontrollstelle")
                    ->relationship('inspector')
                    ->getOptionLabelFromRecordUsing(fn (BioInspector $record): string
                        => "{$record->company} ({$record->label})")
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company')
                    ->label("Firma")
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact')
                    ->label("Kontaktperson")
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label("Email")
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label("Telefon")
                    ->searchable(),
                Tables\Columns\TextColumn::make('website')
                    ->label("Webseite")
                    ->url(fn (Supplier $record): string => str($record->website)->start("https://"))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn (Supplier $record): string => str($record->website)->replaceFirst('www.', ''))
                    ->searchable(),
                Tables\Columns\TextColumn::make('inspector.company')
                    ->label("Kontrollstelle")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ManageSuppliers::route('/'),
        ];
    }
}
