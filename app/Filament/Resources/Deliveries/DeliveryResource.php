<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages\CreateDelivery;
use App\Filament\Resources\Deliveries\Pages\EditDelivery;
use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Deliveries\RelationManagers\BagsRelationManager;
use App\Filament\Resources\Suppliers\SupplierResource;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
                            ->label('Lieferdatum')
                            ->default(now())
                            ->live(onBlur: true)
                            ->required(),
                        Select::make('user_id')
                            ->label('Empfänger')
                            ->relationship('user', 'name')
                            ->default(fn () => User::where('name', 'Marcus Wagner')->first()->id)
                            ->required(),
                        Select::make('supplier_id')
                            ->label('Lieferant')
                            ->relationship('supplier', 'shortname')
                            ->default(fn () => Supplier::where('shortname', 'Galke')->first()->id)
                            ->live()
                            ->required(),
                        View::make('filament.deliveries.certificate-panel')
                            ->viewData(fn (Get $get, ?Delivery $record): array => static::certificatePanelData($get, $record))
                            ->columnSpanFull(),
                    ]),
                    Tab::make('Dokumente')->schema([
                        SpatieMediaLibraryFileUpload::make('invoice')
                            ->label('Rechnung')
                            ->hintIcon('heroicon-s-document-text')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('invoice')
                            ->downloadable()
                            ->previewable()
                            ->maxFiles(1),
                        SpatieMediaLibraryFileUpload::make('deliveryNote')
                            ->label('Lieferschwein')
                            ->hintIcon('heroicon-s-clipboard-document-check')
                            ->acceptedFileTypes(['application/pdf'])
                            ->collection('deliveryNote')
                            ->downloadable()
                            ->previewable()
                            ->maxFiles(1),
                    ])->columns(['md' => 2]),
                    Tab::make('Eingangskontrolle')->schema([
                        Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'lg' => 3,
                        ])->schema([
                            Section::make('Allgemein')->schema([
                                DatePicker::make('bio_inspection.date')
                                    ->label('Kontrolldatum')
                                    ->default(now())
                                    ->required(),
                                Toggle::make('bio_inspection.approved')
                                    ->label('Ware freigegeben?')
                                    ->default(true),
                                Toggle::make('bio_inspection.certificateValid')
                                    ->label('Codenummer der Kontrollstelle gültig?')
                                    ->default(true),
                                Toggle::make('bio_inspection.goodsMatchValidity')
                                    ->label('Entspricht die Ware dem Zertizierungsbereich?')
                                    ->default(true),
                            ])
                                ->extraAttributes(['class' => 'h-full'])
                                ->columnSpan([
                                    'sm' => 2,
                                    'lg' => 1,
                                ]),
                            Section::make('Dokumentenkontrolle')->schema([
                                Toggle::make('bio_inspection.hasInvoice')
                                    ->label('Rechnung vorhanden?')
                                    ->default(true),
                                Toggle::make('bio_inspection.codeOnInvoice')
                                    ->label('Codenummer der Kontrollstelle auf Rechnung? ')
                                    ->default(true),
                                Toggle::make('bio_inspection.hasDeliveryNote')
                                    ->label('Lieferschein vorhanden? ')
                                    ->default(true),
                                Toggle::make('bio_inspection.codeOnDeliveryNote')
                                    ->label('Codenummer der Kontrollstelle auf Lieferschein? ')
                                    ->default(false),
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
                            Section::make('Gebindekontrolle')->schema([
                                Toggle::make('bio_inspection.codeOnBag')
                                    ->label('Codenummer der Kontrollstelle auf Gebinde? ')
                                    ->default(true),
                                Toggle::make('bio_inspection.damaged')
                                    ->label('Beschädigung?')
                                    ->default(false),
                                Toggle::make('bio_inspection.pestInfection')
                                    ->label('Schädlingsbefall?')
                                    ->default(false),
                                TextInput::make('bio_inspection.notes')
                                    ->label('Bei Befund (Bemerkungen): ')
                                    ->default(''),
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
                        ]),
                    ]),
                ])->columnSpan('full'),
            ]);
    }

    /**
     * Build the data for the certificate panel.
     *
     * On an existing delivery whose supplier + date still match what is saved,
     * show the certificate frozen onto it at intake (the real audit record). If
     * the operator has changed the supplier or date in the form, show a live
     * preview of the certificate the new selection will resolve to — this is
     * what will replace the snapshot on save — flagged as pending.
     *
     * On create, live-resolve the certificate covering the chosen supplier +
     * date so the operator sees what will be snapshotted (or a warning that
     * none covers the date).
     *
     * @return array{summary: array<string, mixed>|null, frozen: bool, pending: bool, replacesExisting: bool, supplierUrl: string|null}
     */
    protected static function certificatePanelData(Get $get, ?Delivery $record): array
    {
        $supplierId = $get('supplier_id');
        $date = $get('delivered_date');

        $supplierUrl = filled($supplierId)
            ? SupplierResource::getUrl('edit', ['record' => $supplierId])
            : null;

        $hasExistingSnapshot = $record?->certificateSummary() !== null;

        if ($record?->exists) {
            $unchanged = (int) $supplierId === (int) $record->supplier_id
                && ! blank($date)
                && Carbon::parse($date)->isSameDay($record->delivered_date);

            if ($unchanged) {
                return [
                    'summary' => $record->certificateSummary(),
                    'frozen' => true,
                    'pending' => false,
                    'replacesExisting' => false,
                    'supplierUrl' => $supplierUrl,
                ];
            }
        }

        if (blank($supplierId) || blank($date)) {
            return [
                'summary' => null,
                'frozen' => false,
                'pending' => (bool) $record?->exists,
                'replacesExisting' => $hasExistingSnapshot,
                'supplierUrl' => $supplierUrl,
            ];
        }

        $certificate = Supplier::with('certificates.bioInspector')
            ->find($supplierId)
            ?->certificateForDate(Carbon::parse($date));

        return [
            'summary' => $certificate?->toSummary(),
            'frozen' => false,
            'pending' => (bool) $record?->exists,
            'replacesExisting' => $hasExistingSnapshot,
            'supplierUrl' => $supplierUrl,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('delivered_date', 'desc')
            ->columns([
                TextColumn::make('supplier.shortname')
                    ->label('Lieferant')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('delivered_date')
                    ->date('d.m.Y')
                    ->label('Lieferdatum')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Empfänger')
                    ->sortable(),
                IconColumn::make('certificate_status')
                    ->label('Zertifikat')
                    ->alignCenter()
                    ->tooltip(fn (Delivery $record): string => $record->certificateSummary() === null
                        ? 'Kein Zertifikat eingefroren'
                        : 'Zertifikat '.$record->certificateSummary()['control_body_code'])
                    ->state(fn (Delivery $record): bool => $record->certificateSummary() !== null)
                    ->trueIcon('heroicon-s-shield-check')
                    ->falseIcon('heroicon-s-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('charges')
                    ->getStateUsing(fn (Delivery $record) => $record->bags->pluck('charge')->unique()->join(', '))
                    ->hidden()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('supplier')
                    ->label('Lieferant')
                    ->relationship('supplier', 'shortname'),
                TernaryFilter::make('has_files')
                    ->label('Hat Dokumente')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein')
                    ->queries(
                        true: fn ($query) => $query->whereHas('media'),
                        false: fn ($query) => $query->whereDoesntHave('media'),
                    ),
                SelectFilter::make('herbs')
                    ->label('Enthält Rohstoff')
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
            BagsRelationManager::class,
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
