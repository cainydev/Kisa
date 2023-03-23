<?php

namespace App\Orchid\Screens\Meta;

use App\Models\ProductType;
use App\Orchid\Layouts\ProductType\ProductTypeListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Alert;

class ProductTypeScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'productTypes' => ProductType::all(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Produktgruppen';
    }

    public function description(): ?string
    {
        return 'Werden benutzt um die Produkte nach Typ zu sortieren.';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.meta.producttype.edit'),
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            ProductTypeListLayout::class,
        ];
    }

    public function deleteType($type)
    {
        $type = ProductType::with('products')->find($type);
        if ($type != null) {
            $countProducts = $type->products->count();

            if ($countProducts > 0) {
                Alert::view('toasts.deleteFailed', Color::DANGER(), [
                    'objectName' => 'Produktgruppe',
                    'errors' => [
                        'Produkte' => $countProducts,
                    ],
                ]);
            } else {
                $typename = $type->name;
                $type->delete();
                Alert::success('Produktgruppe '.$typename.' wurde erfolgreich gelöscht.');
            }
        } else {
            Alert::error('Produktgruppe konnte nicht entfernt werden.');
        }
    }
}
