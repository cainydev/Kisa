<?php

namespace App\Orchid\Layouts\Restock;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Columns;

class RestockSettingsPanel extends Columns
{
    protected $title = 'Einstellungen';

    protected function fields(): iterable
    {
        return [
            Input::make('trashGate')
                ->title('Valider Ausschuss max %')
                ->type('range')
                ->min(0)
                ->max(100),
            DateRange::make('dateRange')
                ->title('Datum Von/Bis')
        ];
    }
}
