<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Orchid\Layouts\Product\ProductListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;

class ProductScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'products' => Product::paginate(
                config('kis.paginate')
            )
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.products.edit')
        ];
    }

    public function name(): ?string
    {
        return 'Produkte';
    }

    public function deleteProduct(Product $product)
    {
        $canDelete = true;
        $message = "Produkt konnte nicht gelöscht werden: ";
        foreach ($product->variants as $variant) {
            foreach ($variant->positions as $position) {
                $canDelete = false;
                $message .= "Die " . $variant->size . "g Variante dieses Produkts wird aktuell noch in der Abfüllung ID: " . $position->bottle->id . " verwendet. ";
            }
        }

        if ($canDelete) {
            $product->delete();
            Alert::success('Produkt wurde gelöscht.');
        } else {
            Alert::error($message);
        }
    }



    public function layout(): iterable
    {
        return [
            ProductListLayout::class
        ];
    }
}
