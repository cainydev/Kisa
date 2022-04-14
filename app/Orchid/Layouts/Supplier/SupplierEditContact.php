<?php

namespace App\Orchid\Layouts\Supplier;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\Input;

class SupplierEditContact extends Rows
{

    protected function fields(): iterable
    {
        return [
            Input::make('supplier.contact')
                ->title('Kontaktperson')
                ->help('z.B. Firmeninhaber')
                ->required(),

            Input::make('supplier.email')
                ->type('email')
                ->title('Email')
                ->help('Email-Kontakt des Lieferanten')
                ->required(),

            Input::make('supplier.phone')
                ->title('Telefonnummer')
                ->help('Telefon-Kontakt des Lieferanten')
                ->required(),

            Input::make('supplier.website')
                ->title('Webseite (mit www.)')
                ->help('Internetpräsenz des Lieferanten')
                ->required()
        ];
    }
}
