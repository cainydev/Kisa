<?php

namespace App\Orchid\Layouts\Abfuellen;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\{Input, Relation, DateTimer};

use App\Models\User;

class AbfuellenGeneralLayout extends Rows
{
    /**
     * Used to create the title of a group of form elements.
     *
     * @var string|null
     */
    protected $title = 'Allgemein';

    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    protected function fields(): iterable
    {
        return [
            Relation::make('bottle.user_id')
                ->title('Abfüller')
                ->help('Derjenige, der abgefüllt hat. Das musst nicht zwingend du sein.')
                ->required()
                ->fromModel(User::class, 'name'),
            DateTimer::make('bottle.date')
                ->title('Datum der Abfüllung')
                ->value(now())
                ->required()
                ->format('Y-m-d'),
            Input::make('bottle.note')
                ->title('Notizen')
                ->help('Irgendwelche Notizen zu dieser Abfüllung. Kann leer gelassen werden.')
        ];
    }
}
