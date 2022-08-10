<?php

namespace App\Orchid\Screens\Product;

use App\Models\Product;
use App\Orchid\Layouts\Product\ProductEditLayout;
use App\Orchid\Layouts\Product\ProductVariantsLayout;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Facades\Layout;

class ProductEditScreen extends Screen
{
    public Product $product;

    public function query(Product $product): iterable
    {
        return [
            'product' => $product,
        ];
    }

    public function name(): ?string
    {
        return 'Endprodukt ' . ($this->product->exists ? 'bearbeiten' : 'erstellen');
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Zurück')
                ->icon('action-undo')
                ->class('btn btn-link')
                ->route('platform.products'),
            Button::make('Speichern')
            ->icon('save')
                ->class('btn btn-success')
                ->method('createOrUpdate'),
        ];
    }

    public function createOrUpdate(Product $product, Request $request)
    {
        $product->fill($request->get('product'))->save();

        Alert::success('Produkt wurde gespeichert.');

        return redirect()->route('platform.products.edit', $product);
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Allgemein' => ProductEditLayout::class,
                'Varianten' => Layout::livewire('variant-maker'),
                'Rezept' => Layout::livewire('recipe-maker')
            ])


        ];
    }
}
