<?php

namespace App\Orchid\Layouts\Abfuellen;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\{Input, Relation, DateTimer};

use App\Models\User;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;

class AbfuellenGeneralLayout extends Rows
{
    protected $title = 'Allgemein';

    protected function fields(): iterable
    {
        return [
            Relation::make('bottle.user_id')
                ->title('Abfüller')
                ->help('Derjenige, der abgefüllt hat. Das musst nicht zwingend du sein.')
                ->required()
                ->value($this->bottle->user_id ?? 2)
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
