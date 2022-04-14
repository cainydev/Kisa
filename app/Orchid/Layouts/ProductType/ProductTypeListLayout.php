<?php

namespace App\Orchid\Layouts\ProductType;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

use App\Orchid\Fields\Group;

use Orchid\Screen\Actions\{Link, Button};

class ProductTypeListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'productTypes';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID'),

            TD::make('name', 'Name'),

            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($type) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteType', ['id' => $type->id])
                            ->confirm('Willst du die Produktgruppe »' . $type->name . '« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.meta.producttype.edit', $type),
                    ]);
                }),
        ];
    }
}
