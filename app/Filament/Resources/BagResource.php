<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BagResource\Pages;
use App\Livewire\BagAmountBar;
use App\Models\Bag;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
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
    protected static ?string $navigationGroup = 'Bestand';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Livewire::make(BagAmountBar::class, fn($record) => ['bag' => $record])
                    ->columnSpan(2)
                    ->hiddenOn(['create'])
                    ->hidden(fn(?Bag $record) => $record === null),
                Forms\Components\Select::make('herb_id')
                    ->label('Rohstoff')
                    ->required()
                    ->relationship('herb', 'fullname')
                    ->searchable(),
                Forms\Components\Toggle::make('bio')
                    ->default(true)
                    ->inline(false),
                Forms\Components\TextInput::make('specification')
                    ->required()
                    ->label('Spezifikation')
                    ->maxLength(255),
                Forms\Components\TextInput::make('charge')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('size')
                    ->required()
                    ->label('Gebindegröße')
                    ->suffix('g')
                    ->numeric(),
                Forms\Components\Split::make([
                    Forms\Components\TextInput::make('trashed')
                        ->label('Ausschuss')
                        ->required()
                        ->live()
                        ->numeric()
                        ->grow()
                        ->maxWidth(null)
                        ->suffix('g')
                        ->default(0),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('all')
                            ->label('Alles')
                            ->color('gray')
                            ->action(fn(Set $set, Bag $record) => $set('trashed', $record->getCurrent())),
                        Forms\Components\Actions\Action::make('nothing')
                            ->label('Nichts')
                            ->color('gray')
                            ->action(fn(Set $set, Bag $record) => $set('trashed', 0))
                    ])->grow(false)
                ])->verticallyAlignEnd(),
                Forms\Components\DatePicker::make('bestbefore')
                    ->label('MHD')
                    ->required(),
                Forms\Components\DatePicker::make('steamed')
                    ->label('Letzte Dampfbehandlung'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('charge')
                    ->searchable(),
                Tables\Columns\TextColumn::make('herb.name')
                    ->label("Inhalt")
                    ->formatStateUsing(function (Bag $record) {
                        $herb = $record->herb;
                        return "$herb->name $record->specification";
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('bio')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('size')
                    ->label('Gebinde')
                    ->numeric()
                    ->formatStateUsing(function (Bag $record) {
                        return $record->getSizeInKilo();
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('redisCurrent')
                    ->label("Gewicht aktuell")
                    ->formatStateUsing(function ($state) {
                        return $state . 'g';
                    }),
                Tables\Columns\TextColumn::make('delivery.supplier.shortname')
                    ->placeholder("Nicht zugeordnet")
                    ->label("Lieferant"),
                Tables\Columns\TextColumn::make('bestbefore')
                    ->label("MHD")
                    ->date("d.m.Y")
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('herb')
                    ->relationship('herb', 'name')
                    ->label('Enthält Rohstoff')
                    ->searchable(),
                Tables\Filters\TrashedFilter::make()
                    ->label('Entsorgte Säcke')
                    ->placeholder('Ohne entsorgte Säcke')
                    ->trueLabel('Mit entsorgten Säcken')
                    ->falseLabel('Nur entsorgte Säcke'),
                Tables\Filters\TernaryFilter::make('bio')
                    ->label('Bio spezifiziert')
                    ->placeholder('Bio/Nicht-Bio')
                    ->trueLabel('Bio')
                    ->falseLabel('Nicht-Bio'),
                Tables\Filters\TernaryFilter::make('bestbefore')
                    ->label('Haltbarkeit')
                    ->placeholder('Egal')
                    ->trueLabel('Abgelaufen')
                    ->falseLabel('Nicht abgelaufen')
                    ->query(function ($query, $state) {
                        if ($state === null || $state['value'] === null) return $query;
                        return $query->whereDate('bestbefore', $state['value'] ? '<' : '>=', now());
                    }),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBags::route('/'),
            'view' => Pages\ViewBag::route('/{record}'),
            'edit' => Pages\EditBag::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
