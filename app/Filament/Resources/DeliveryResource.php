<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $modelLabel = 'Lieferung';
    protected static ?string $pluralModelLabel = 'Lieferungen';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Bestand';
    protected static ?string $navigationIcon = 'carbon-delivery';

    public static function getGlobalSearchResultTitle(Model $record): string|\Illuminate\Contracts\Support\Htmlable
    {
        return "{$record->supplier->shortname} ({$record->delivered_date->format('d.m.Y')})";
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['delivered_date', 'supplier.shortname'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Gebinde' => $record->bags->count(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make()->tabs([
                    Forms\Components\Tabs\Tab::make('Allgemein')->schema([
                        Forms\Components\DatePicker::make('delivered_date')
                            ->label("Lieferdatum")
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label("Empfänger")
                            ->relationship('user', 'name')
                            ->default(fn () => User::where('name', 'Marcus Wagner')->first()->id)
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->label("Lieferant")
                            ->relationship('supplier', 'shortname')
                            ->default(fn () => Supplier::where('shortname', 'Galke')->first()->id)
                            ->required(),
                    ]),
                    Forms\Components\Tabs\Tab::make('Dokumente')->schema([
                        SpatieMediaLibraryFileUpload::make('invoice')
                            ->label("Rechnung")
                            ->hintIcon('heroicon-s-document-text')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('invoice')
                            ->maxFiles(1),
                        SpatieMediaLibraryFileUpload::make('deliveryNote')
                            ->label("Lieferschwein")
                            ->hintIcon('heroicon-s-clipboard-document-check')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('deliveryNote')
                            ->maxFiles(1),
                        SpatieMediaLibraryFileUpload::make('certificate')
                            ->label("Zertifikat")
                            ->hintIcon('heroicon-s-shield-check')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('certificate')
                            ->maxFiles(1)
                    ])->columns(['md' => 3]),
                    Forms\Components\Tabs\Tab::make('Eingangskontrolle')->schema([
                        Forms\Components\Split::make([
                            Forms\Components\Section::make("Allgemein")->schema([
                                Forms\Components\DatePicker::make('bio_inspection.date')
                                    ->label("Kontrolldatum")
                                    ->default(now())
                                    ->required(),
                                Forms\Components\Toggle::make('bio_inspection.approved')
                                    ->label("Ware freigegeben?")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.certificateValid')
                                    ->label("Codenummer der Kontrollstelle gültig?")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.goodsMatchValidity')
                                    ->label("Entspricht die Ware dem Zertizierungsbereich?")
                                    ->default(true),
                            ]),
                            Forms\Components\Section::make("Dokumentenkontrolle")->schema([
                                Forms\Components\Toggle::make('bio_inspection.hasInvoice')
                                    ->label("Rechnung vorhanden?")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.codeOnInvoice')
                                    ->label("Codenummer der Kontrollstelle auf Rechnung? ")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.hasDeliveryNote')
                                    ->label("Lieferschein vorhanden? ")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.codeOnDeliveryNote')
                                    ->label("Codenummer der Kontrollstelle auf Lieferschein? ")
                                    ->default(false),
                            ]),
                            Forms\Components\Section::make("Gebindekontrolle")->schema([
                                Forms\Components\Toggle::make('bio_inspection.codeOnBag')
                                    ->label("Codenummer der Kontrollstelle auf Gebinde? ")
                                    ->default(true),
                                Forms\Components\Toggle::make('bio_inspection.damaged')
                                    ->label("Beschädigung?")
                                    ->default(false),
                                Forms\Components\Toggle::make('bio_inspection.pestInfection')
                                    ->label("Schädlingsbefall?")
                                    ->default(false),
                                Forms\Components\TextInput::make('bio_inspection.notes')
                                    ->label("Bei Befund (Bemerkungen): ")
                                    ->default(""),
                            ]),
                        ])
                    ]),
                ])->columnSpan('full')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.shortname')
                    ->label("Lieferant")
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_date')
                    ->date('d.m.Y')
                    ->label("Lieferdatum")
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label("Empfänger")
                    ->sortable(),
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
            RelationManagers\BagsRelationManager::class
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
        ];
    }
}
