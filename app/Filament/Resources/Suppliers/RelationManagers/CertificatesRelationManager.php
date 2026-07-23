<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use App\Enums\CertificateActivity;
use App\Enums\ProductCategory;
use App\Filament\Resources\BioInspectors\BioInspectorResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CertificatesRelationManager extends RelationManager
{
    protected static string $relationship = 'certificates';

    protected static ?string $title = 'Zertifikate';

    protected static ?string $modelLabel = 'Zertifikat';

    protected static ?string $pluralModelLabel = 'Zertifikate';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SpatieMediaLibraryFileUpload::make('document')
                    ->label('Dokument (PDF)')
                    ->collection('document')
                    ->acceptedFileTypes(['application/pdf'])
                    ->downloadable()
                    ->openable()
                    ->columnSpanFull(),
                TextInput::make('certificate_number')
                    ->label('Zertifikatsnummer')
                    ->maxLength(255),
                BioInspectorResource::select(),
                Select::make('activities')
                    ->label('Tätigkeiten')
                    ->options(CertificateActivity::class)
                    ->multiple(),
                Select::make('product_categories')
                    ->label('Erzeugniskategorien')
                    ->options(ProductCategory::class)
                    ->multiple(),
                DatePicker::make('issued_at')
                    ->label('Ausgestellt am'),
                DatePicker::make('valid_from')
                    ->label('Gültig ab'),
                DatePicker::make('valid_until')
                    ->label('Gültig bis'),
                TextInput::make('issued_place')
                    ->label('Ausstellungsort')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->label('Nummer')
                    ->searchable(),
                TextColumn::make('bioInspector.label')
                    ->label('Kontrollstelle'),
                TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->label('Gültig bis')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('valid_from', 'desc')
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
