<?php

namespace App\Orchid\Layouts\Supplier;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;

use Orchid\Screen\Fields\Input;

class SupplierEditGeneral extends Rows
{
    protected function fields(): iterable
    {
        return [
            Input::make('supplier.company')
                ->title('Firma')
                ->help('Vollständiger Firmenname')
                ->required(),

            Input::make('supplier.shortname')
                ->title('Kurzname')
                ->help('Kurzer Name zur Identifikation im System')
                ->required()
        ];
    }
}
