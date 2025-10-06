<?php

namespace App\Filament\Resources\Bags;

use App\Filament\Resources\BagResource\Pages;
use App\Filament\Resources\Bags\Pages\EditBag;
use App\Filament\Resources\Bags\Pages\ListBags;
use App\Filament\Resources\Bags\Pages\ViewBag;
use App\Livewire\BagAmountBar;
use App\Models\Bag;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;
use function now;

class BagResource extends Resource
{
    protected static ?string $model = Bag::class;

    protected static ?string $modelLabel = 'Sack';
    protected static ?string $pluralModelLabel = 'Säcke';

    protected static ?string $recordTitleAttribute = 'herb.name';

    protected static ?int $navigationSort = 20;
    protected static string|\UnitEnum|null $navigationGroup = 'Bestand';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $free = Number::abbreviate($record->getCurrentWithTrashed());

        return [
            'Charge' => $record->charge,
            'Verbleibend' => "{$free}g",
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "{$record->herb->name} {$record->specification}";
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery()->with(['herb']);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('charge')
                    ->copyable()
                    ->badge()
                    ->size(TextSize::Large)
                    ->searchable(),
                TextColumn::make('herb.name')
                    ->label("Inhalt")
                    ->html()
                    ->formatStateUsing(function (Bag $record) {
                        $herb = str($record->herb->name)->limit(20);
                        return "<p class='font-semibold'>$herb</p><p class='text-gray-700 dark:text-gray-300'>$record->specification</p>";
                    })
                    ->sortable()
                    ->searchable(),
                IconColumn::make('bio')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('size')
                    ->label('Gebinde')
                    ->numeric()
                    ->formatStateUsing(function (Bag $record) {
                        return $record->getSizeInKilo();
                    })
                    ->sortable(),
                TextColumn::make('redisCurrent')
                    ->label("Gewicht aktuell")
                    ->formatStateUsing(function ($state) {
                        return $state . 'g';
                    }),
                TextColumn::make('delivery.supplier.shortname')
                    ->placeholder("Nicht zugeordnet")
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label("Lieferant"),
                TextColumn::make('bestbefore')
                    ->toggleable()
                    ->label("MHD")
                    ->date("d.m.Y")
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('herb')
                    ->relationship('herb', 'name')
                    ->label('Enthält Rohstoff')
                    ->searchable(),
                TrashedFilter::make()
                    ->label('Entsorgte Säcke')
                    ->placeholder('Ohne entsorgte Säcke')
                    ->trueLabel('Mit entsorgten Säcken')
                    ->falseLabel('Nur entsorgte Säcke'),
                TernaryFilter::make('bio')
                    ->label('Bio spezifiziert')
                    ->placeholder('Bio/Nicht-Bio')
                    ->trueLabel('Bio')
                    ->falseLabel('Nicht-Bio'),
                TernaryFilter::make('bestbefore')
                    ->label('Haltbarkeit')
                    ->placeholder('Egal')
                    ->trueLabel('Abgelaufen')
                    ->falseLabel('Nicht abgelaufen')
                    ->query(function ($query, $state) {
                        if ($state === null || $state['value'] === null) return $query;
                        return $query->whereDate('bestbefore', $state['value'] ? '<' : '>=', now());
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(4)
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->label('Bearbeiten'),
                DeleteAction::make()
                    ->button()
                    ->label('Entsorgen')
                    ->color('danger')
                    ->modalHeading('Sack entsorgen?')
                    ->modalDescription('Der Sack kann danach nicht mehr in der Abfüllung verwenden werden.')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Sack entsorgt'),
                RestoreAction::make()
                    ->button()
                    ->label('Wiederherstellen')
                    ->successNotificationTitle('Sack wiederhergestellt')
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Alle Entsorgen')
                    ->color('danger')
                    ->modalHeading('Säcke entsorgen?')
                    ->modalDescription('Die Säcke können danach nicht mehr in der Abfüllung verwenden werden.')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Säcke entsorgt'),
                RestoreBulkAction::make()
                    ->label('Alle wiederherstellen')
                    ->successNotificationTitle('Säcke wiederhergestellt'),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Livewire::make(BagAmountBar::class, fn($record) => ['bag' => $record])
                    ->columnSpan(2)
                    ->hiddenOn(['create'])
                    ->hidden(fn(?Bag $record) => $record === null),
                Select::make('herb_id')
                    ->label('Rohstoff')
                    ->required()
                    ->relationship('herb', 'fullname')
                    ->searchable(),
                Toggle::make('bio')
                    ->default(true)
                    ->inline(false),
                TextInput::make('specification')
                    ->required()
                    ->label('Spezifikation')
                    ->maxLength(255),
                TextInput::make('charge')
                    ->required()
                    ->maxLength(255),
                TextInput::make('size')
                    ->required()
                    ->label('Gebindegröße')
                    ->suffix('g')
                    ->numeric(),
                Flex::make([
                    TextInput::make('trashed')
                        ->label('Ausschuss')
                        ->required()
                        ->live()
                        ->numeric()
                        ->grow()
                        ->maxWidth(null)
                        ->suffix('g')
                        ->default(0),
                    Actions::make([
                        Action::make('all')
                            ->label('Alles')
                            ->color('gray')
                            ->action(fn(Set $set, Bag $record) => $set('trashed', $record->getCurrent())),
                        Action::make('nothing')
                            ->label('Nichts')
                            ->color('gray')
                            ->action(fn(Set $set, Bag $record) => $set('trashed', 0))
                    ])->grow(false)
                ])->verticallyAlignEnd(),
                DatePicker::make('bestbefore')
                    ->label('MHD')
                    ->required(),
                DatePicker::make('steamed')
                    ->label('Letzte Dampfbehandlung'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBags::route('/'),
            'edit' => EditBag::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
