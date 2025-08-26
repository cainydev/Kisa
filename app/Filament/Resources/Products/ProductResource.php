<?php

namespace App\Filament\Resources\Products;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Tables\Columns\VariantColumn;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'Endprodukt';
    protected static ?string $pluralModelLabel = 'Endprodukte';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | \UnitEnum | null $navigationGroup = 'Produkte';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make("Allgemein")->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->default('Unbenanntes Produkt'),
                        Select::make('product_type_id')
                            ->relationship('type', 'name')
                            ->label("Produktgruppe")
                            ->required(),
                        Toggle::make('exclude_from_statistics')
                            ->label("Von Statistiken ausschließen")
                            ->disabled(fn(Product $product) => $product->type->exclude_from_statistics)
                            ->formatStateUsing(fn(Product $product, mixed $state) => $product->type->exclude_from_statistics ?: $state)
                            ->beforeStateDehydrated(fn(Product $product, mixed $state) => $product->type->exclude_from_statistics ? $product->exclude_from_statistics : $state)
                            ->hint(fn(Product $product) => $product->type->exclude_from_statistics ? "Die ganze Produktgruppe '{$product->type->name}' ist von den Statistiken ausgeschlossen." : false)
                            ->hintIcon('heroicon-o-exclamation-triangle')
                            ->required(),
                    ]),
                    Tab::make("Varianten")->schema([
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
                                Flex::make([
                                    TextInput::make('sku')
                                        ->label("SKU")
                                        ->dehydrateStateUsing(fn($state) => str($state)->toString())
                                        ->required(),
                                    TextInput::make('size')
                                        ->numeric()
                                        ->live()
                                        ->suffix("g")
                                        ->required(),
                                ])
                            ])
                    ]),
                    Tab::make("Rezept")->schema([
                        TextInput::make('sum')
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
                                Flex::make([
                                    Select::make('herb_id')
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
                                    TextInput::make('percentage')
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
                TextColumn::make('name')
                    ->searchable(),
                VariantColumn::make('variants')
                    ->label("Varianten"),
                TextColumn::make('type.name')
                    ->label("Produktgruppe")
                    ->sortable(),
                IconColumn::make('exclude_from_statistics')
                    ->label("Von Statistiken ausschließen")
                    ->getStateUsing(fn(Product $record) => $record->exclude_from_statistics || $record->type->exclude_from_statistics)
                    ->boolean(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
