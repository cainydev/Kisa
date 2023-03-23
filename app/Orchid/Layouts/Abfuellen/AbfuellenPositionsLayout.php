<?php

namespace App\Orchid\Layouts\Abfuellen;

use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class AbfuellenPositionsLayout extends Table
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title = 'Alle Produkte dieser Abfüllung';

    protected $target = 'bottle.positions';

    /**
     * Get the fields elements to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('variant.product.name', 'Produkt'),
            TD::make('size', 'Gebinde')->render(function ($pos) {
                return view('partials.variants', [
                    'variants' => [$pos->variant],
                ]);
            }),
            TD::make('Anzahl')->render(function ($pos) {
                return $pos->count;
            }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(
                    function ($pos) {
                        return Button::make()
                            ->icon('trash')
                            ->method('deleteVariant', ['id' => $pos->id])
                            ->class('btn btn-danger');
                    }
                ),
        ];
    }
}
