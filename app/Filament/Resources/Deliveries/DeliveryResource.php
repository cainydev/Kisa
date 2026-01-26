<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages\CreateDelivery;
use App\Filament\Resources\Deliveries\Pages\EditDelivery;
use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Deliveries\RelationManagers\BagsRelationManager;
use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use App\Models\Supplier;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $modelLabel = 'Lieferung';
    protected static ?string $pluralModelLabel = 'Lieferungen';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;
    protected static string|\UnitEnum|null $navigationGroup = 'Bestand';
    protected static string|\BackedEnum|null $navigationIcon = 'carbon-delivery';

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "{$record->supplier->shortname} ({$record->delivered_date->format('d.m.Y')})";
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['delivered_date', 'supplier.shortname', 'bags.charge'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Gebinde' => $record->bags->count(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make('Allgemein')->schema([
                        DatePicker::make('delivered_date')
                            ->label("Lieferdatum")
                            ->default(now())
                            ->required(),
                        Select::make('user_id')
                            ->label("Empfänger")
                            ->relationship('user', 'name')
                            ->default(fn() => User::where('name', 'Marcus Wagner')->first()->id)
                            ->required(),
                        Select::make('supplier_id')
                            ->label("Lieferant")
                            ->relationship('supplier', 'shortname')
                            ->default(fn() => Supplier::where('shortname', 'Galke')->first()->id)
                            ->required(),
                    ]),
                    Tab::make('Dokumente')->schema([
                        SpatieMediaLibraryFileUpload::make('invoice')
                            ->label("Rechnung")
                            ->hintIcon('heroicon-s-document-text')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('invoice')
                            ->downloadable()
                            ->previewable()
                            ->maxFiles(1),
                        SpatieMediaLibraryFileUpload::make('deliveryNote')
                            ->label("Lieferschwein")
                            ->hintIcon('heroicon-s-clipboard-document-check')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('deliveryNote')
                            ->downloadable()
                            ->previewable()
                            ->maxFiles(1),
                        SpatieMediaLibraryFileUpload::make('certificate')
                            ->label("Zertifikat")
                            ->hintIcon('heroicon-s-shield-check')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('certificate')
                            ->downloadable()
                            ->previewable()
                            ->maxFiles(1)
                    ])->columns(['md' => 3]),
                    Tab::make('Eingangskontrolle')->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'lg' => 3,
                        ])->schema([
                            Section::make("Allgemein")->schema([
                                DatePicker::make('bio_inspection.date')
                                    ->label("Kontrolldatum")
                                    ->default(now())
                                    ->required(),
                                Toggle::make('bio_inspection.approved')
                                    ->label("Ware freigegeben?")
                                    ->default(true),
                                Toggle::make('bio_inspection.certificateValid')
                                    ->label("Codenummer der Kontrollstelle gültig?")
                                    ->default(true),
                                Toggle::make('bio_inspection.goodsMatchValidity')
                                    ->label("Entspricht die Ware dem Zertizierungsbereich?")
                                    ->default(true),
                            ])
                                ->extraAttributes(['class' => 'h-full'])
                                ->columnSpan([
                                    'sm' => 2,
                                    'lg' => 1,
                                ]),
                            Section::make("Dokumentenkontrolle")->schema([
                                Toggle::make('bio_inspection.hasInvoice')
                                    ->label("Rechnung vorhanden?")
                                    ->default(true),
                                Toggle::make('bio_inspection.codeOnInvoice')
                                    ->label("Codenummer der Kontrollstelle auf Rechnung? ")
                                    ->default(true),
                                Toggle::make('bio_inspection.hasDeliveryNote')
                                    ->label("Lieferschein vorhanden? ")
                                    ->default(true),
                                Toggle::make('bio_inspection.codeOnDeliveryNote')
                                    ->label("Codenummer der Kontrollstelle auf Lieferschein? ")
                                    ->default(false),
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
                            Section::make("Gebindekontrolle")->schema([
                                Toggle::make('bio_inspection.codeOnBag')
                                    ->label("Codenummer der Kontrollstelle auf Gebinde? ")
                                    ->default(true),
                                Toggle::make('bio_inspection.damaged')
                                    ->label("Beschädigung?")
                                    ->default(false),
                                Toggle::make('bio_inspection.pestInfection')
                                    ->label("Schädlingsbefall?")
                                    ->default(false),
                                TextInput::make('bio_inspection.notes')
                                    ->label("Bei Befund (Bemerkungen): ")
                                    ->default(""),
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
                        ])
                    ]),
                ])->columnSpan('full')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('delivered_date', 'desc')
            ->columns([
                TextColumn::make('supplier.shortname')
                    ->label("Lieferant")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('delivered_date')
                    ->date('d.m.Y')
                    ->label("Lieferdatum")
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label("Empfänger")
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('charges')
                    ->getStateUsing(fn(Delivery $record) => $record->bags->pluck('charge')->unique()->join(', '))
                    ->hidden()
                    ->searchable()
            ])
            ->filters([
                SelectFilter::make('supplier')
                    ->label("Lieferant")
                    ->relationship('supplier', 'shortname'),
                TernaryFilter::make('has_files')
                    ->label('Hat Dokumente')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein')
                    ->queries(
                        true: fn($query) => $query->whereHas('media'),
                        false: fn($query) => $query->whereDoesntHave('media'),
                    ),
                SelectFilter::make('herbs')
                    ->label("Enthält Rohstoff")
                    ->multiple()
                    ->searchable()
                    ->relationship('bags.herb', 'name'),
                SelectFilter::make('charges')
                    ->label('Enthält Charge')
                    ->multiple()
                    ->searchable()
                    ->relationship('bags', 'charge'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BagsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeliveries::route('/'),
            'create' => CreateDelivery::route('/create'),
            'edit' => EditDelivery::route('/{record}/edit'),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
