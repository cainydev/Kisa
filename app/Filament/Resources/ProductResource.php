<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Tables\Columns\VariantColumn;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'Endprodukt';
    protected static ?string $pluralModelLabel = 'Endprodukte';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'Produkte';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()->tabs([
                    Tabs\Tab::make("Allgemein")->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->default('Unbenanntes Produkt'),
                        Forms\Components\Select::make('product_type_id')
                            ->relationship('type', 'name')
                            ->label("Produktgruppe")
                            ->required(),
                        Forms\Components\Toggle::make('exclude_from_statistics')
                            ->label("Von Statistiken ausschließen")
                            ->disabled(fn(Product $product) => $product->type->exclude_from_statistics)
                            ->formatStateUsing(fn(Product $product, mixed $state) => $product->type->exclude_from_statistics ?: $state)
                            ->beforeStateDehydrated(fn(Product $product, mixed $state) => $product->type->exclude_from_statistics ? $product->exclude_from_statistics : $state)
                            ->hint(fn(Product $product) => $product->type->exclude_from_statistics ? "Die ganze Produktgruppe '{$product->type->name}' ist von den Statistiken ausgeschlossen." : false)
                            ->hintIcon('heroicon-o-exclamation-triangle')
                            ->required(),
                    ]),
                    Tabs\Tab::make("Varianten")->schema([
                        Repeater::make('variants')
                            ->addActionLabel("Neue Variante")
                            ->hiddenLabel()
                            ->itemLabel(function (array $state): string {
                                if ($state['size'] !== null)
                                    return "{$state['size']}g Variante";
                                return "Neue Variante";
                            })
                            ->relationship()
                            ->schema([
                                Split::make([
                                    Forms\Components\TextInput::make('sku')
                                        ->label("SKU")
                                        ->dehydrateStateUsing(fn($state) => str($state)->toString())
                                        ->required(),
                                    Forms\Components\TextInput::make('size')
                                        ->numeric()
                                        ->live()
                                        ->suffix("g")
                                        ->required(),
                                ])
                            ])
                    ]),
                    Tabs\Tab::make("Rezept")->schema([
                        Forms\Components\TextInput::make('sum')
                            ->label("Gesamt %")
                            ->readOnly()
                            ->disabled()
                            ->maxWidth('xs')
                            ->prefixIcon(fn($state) => round($state, 1) === 100.0 ? 'heroicon-s-check' : 'heroicon-s-x-mark')
                            ->prefixIconColor(fn($state) => round($state, 1) === 100.0 ? 'success' : 'danger'),
                        Repeater::make('recipeIngredients')
                            ->addActionLabel("Neue Zutat")
                            ->relationship()
                            ->live()
                            ->hiddenLabel()
                            ->schema([
                                Split::make([
                                    Forms\Components\Select::make('herb_id')
                                        ->label("Rohstoff")
                                        ->searchable()
                                        ->required()
                                        ->relationship('herb', 'name')
                                        ->disableOptionWhen(function ($value, $state, Get $get) {
                                            return collect($get('../*.herb_id'))
                                                ->reject(fn($id) => $id == $state)
                                                ->filter()
                                                ->contains($value);
                                        }),
                                    Forms\Components\TextInput::make('percentage')
                                        ->label("Anteil in %")
                                        ->numeric()
                                        ->inputMode('decimal')
                                        ->step(0.1)
                                        ->disabled(fn(Get $get) => $get('locked'))
                                        ->suffix("%")
                                        ->required(),
                                ])
                            ])
                            ->afterStateHydrated(function (Get $get, Set $set) {
                                self::updateSum($get, $set);
                            })
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateSum($get, $set);
                            })
                            ->debounce()
                            ->deleteAction(
                                fn(Action $action) => $action->after(fn(Get $get, Set $set) => self::updateSum($get, $set)),
                            )
                    ])
                ])->columnSpan("full")
            ]);
    }

    public static function updateSum(Get $get, Set $set): void
    {
        $sum = collect($get('recipeIngredients'))
            ->filter(fn($item) => !empty($item['herb_id']) && !empty($item['percentage']))
            ->map(fn($item) => $item['percentage'])
            ->sum();

        $set('sum', round($sum, 1));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                VariantColumn::make('variants')
                    ->label("Varianten"),
                Tables\Columns\TextColumn::make('type.name')
                    ->label("Produktgruppe")
                    ->sortable(),
                Tables\Columns\IconColumn::make('exclude_from_statistics')
                    ->label("Von Statistiken ausschließen")
                    ->getStateUsing(fn(Product $record) => $record->exclude_from_statistics || $record->type->exclude_from_statistics)
                    ->boolean(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
