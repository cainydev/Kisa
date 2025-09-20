<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Bag;
use App\Models\Delivery;
use App\Models\Herb;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $modelLabel = 'Lieferung';
    protected static ?string $pluralModelLabel = 'Lieferungen';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 30;
    protected static ?string $navigationGroup = 'Bestand';
    protected static ?string $navigationIcon = 'carbon-delivery';

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
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

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([

                Tables\Columns\TextColumn::make('supplier.shortname')
                    ->label("Lieferant")
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_date')
                    ->date('d.m.Y')
                    ->label("Lieferdatum")
                    ->sortable(),
                Tables\Columns\TextColumn::make('Chargen')
                    ->getStateUsing(function (Model $record) {
                        return $record->bags->implode('charge', ', ');
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $query->whereHas('bags', function (Builder $query) use ($search) {
                            $query->where('charge', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label("Empfänger")
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
                SelectFilter::make('bag')
                    ->label('Enthält Gebinde')
                    ->options(Bag::all()->mapWithKeys(function (Bag $bag) {
                        return [$bag->id => "{$bag->herb->name} ({$bag->charge})"];
                    }))
                    ->searchable()
                    ->query(function ($query, $state) {
                        if ($state === null || $state['value'] == null) return;
                        $query->whereHas('bags', function ($q) use ($state) {
                            $q->where('id', $state);
                        });
                    }),
                SelectFilter::make('herb')
                    ->label('Enthält Rohstoff')
                    ->options(Herb::pluck('name', 'id'))
                    ->searchable()
                    ->multiple()
                    ->query(function ($query, $state) {
                        if ($state === null || empty($state['values'])) return;
                        $query->whereHas('bags', function ($q) use ($state) {
                            $q->whereIn('herb_id', $state['values']);
                        });
                    }),
                Filter::make('created_at')
                    ->form([
                        Forms\Components\Split::make([
                            DatePicker::make('created_from')->label('Erstellt nach'),
                            DatePicker::make('created_until')->label('Erstellt vor'),
                        ])
                    ])
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(['sm' => 2, 'xl' => 4])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
                            ->default(fn() => User::where('name', 'Marcus Wagner')->first()->id)
                            ->required(),
                        Forms\Components\Select::make('supplier_id')
                            ->label("Lieferant")
                            ->relationship('supplier', 'shortname')
                            ->default(fn() => Supplier::where('shortname', 'Galke')->first()->id)
                            ->required(),
                    ]),
                    Forms\Components\Tabs\Tab::make('Dokumente')->schema([
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
                    Forms\Components\Tabs\Tab::make('Eingangskontrolle')->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'sm' => 2,
                            'lg' => 3,
                        ])->schema([
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
                            ])
                                ->extraAttributes(['class' => 'h-full'])
                                ->columnSpan([
                                    'sm' => 2,
                                    'lg' => 1,
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
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
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
                            ])->extraAttributes(['class' => 'h-full'])->columnSpan(1),
                        ])
                    ]),
                ])->columnSpan('full')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BagsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
