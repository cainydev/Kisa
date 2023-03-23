<?php

namespace App\Orchid\Layouts\Product;

use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ProductListLayout extends Table
{
    protected $target = 'products';

    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
                ->width('100px'),
            TD::make('name', 'Name'),
            TD::make('mainnumber', 'SKU'),
            TD::make('Produkttyp')
                ->render(function ($product) {
                    return $product->type->name;
                }),
            TD::make('Varianten')
                ->render(function ($product) {
                    return view('partials/variants', ['variants' => $product->variants]);
                }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($product) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteProduct', ['id' => $product->id])
                            ->confirm('Willst du das Produkt »'.$product->name.'« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.products.edit', $product),
                        Link::make()
                            ->icon('bar-chart')
                            ->class('btn p-2')
                            ->route('platform.products.statistics', $product),
                    ]);
                })->width('100px'),
        ];
    }
}
