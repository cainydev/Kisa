<?php

namespace App\Orchid\Layouts\Settings;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\{Input, CheckBox, DropDown};

class BillbeeLayout extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title;

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            CheckBox::make('billbee.get-stock')
                ->sendTrueOrFalse()
                ->value(config('kis.billbee.get-stock'))
                ->title('Bestände importieren')
                ->help('Wenn aktiviert, werden die Bestände aus Billbee automatisch importiert.'),
            CheckBox::make('billbee.set-stock')
                ->sendTrueOrFalse()
                ->value(config('kis.billbee.set-stock'))
                ->title('Abfüllungen exportieren')
                ->help('Wenn aktiviert, werden die Abfüllungen automatisch in Billbee eingelagert.'),
            Input::make('billbee.everyXMinutes')
                ->type('number')
                ->value(config('kis.billbee.everyXMinutes'))
                ->title('Bestände alle X Minuten importieren')
                ->required()
                ->help('Zeit in Minuten.'),
            Input::make('billbee.from')
                ->title('Uhrzeit Beginn des importierens morgens')
                ->value(config('kis.billbee.from'))
                ->required()
                ->help('Uhrzeit im Format hh:mm')
                ->mask([
                    'mask' => '99:99'
                ]),
            Input::make('billbee.to')
                ->title('Uhrzeit Ende des importierens abends')
                ->value(config('kis.billbee.to'))
                ->required()
                ->help('Uhrzeit im Format hh:mm')
                ->mask([
                    'mask' => '99:99'
                ]),

        ];
    }
}
