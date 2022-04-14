<?php

namespace App\Orchid\Layouts\Product;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

use App\Models\ProductType;

class ProductEditLayout extends Rows
{
    protected function fields(): iterable
    {
        return [
            Input::make('product.name')
            ->title('Bezeichnung')
            ->required(),
            Input::make('product.mainnumber')
            ->title('SKU (Shopware)')
            ->required(),
            Relation::make('product.product_type_id')
            ->fromModel(ProductType::class, 'name')
            ->title('Produkttyp')
            ->required(),
        ];
    }
}
