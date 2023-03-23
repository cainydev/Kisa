<?php

namespace App\Orchid\Layouts\Bag;

use App\Models\Herb;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

class BagEditLayout extends Rows
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
            Relation::make('bag.herb_id')
                ->fromModel(Herb::class, 'fullname')
                ->title('Rohstoff auswählen')
                ->required(),

            Input::make('bag.specification')
                ->title('Spezifikation')
                ->help('z.B. ägypt. BIO geschnitten')
                ->required(),

            Input::make('bag.charge')
                ->title('Chargennummer des Herstellers')
                ->required(),

            CheckBox::make('bag.bio')
                ->value(true)
                ->sendTrueOrFalse()
                ->title('Bio-Zertifiziert?'),

            Input::make('bag.size')
                ->type('number')
                ->title('Gebindegröße in g')
                ->required(),

            DateTimer::make('bag.bestbefore')
                ->title('Mindestens haltbar bis:')
                ->value(now()->addYears(3))
                ->format('Y-m-d')
                ->required(),

            DateTimer::make('bag.steamed')
                ->title('Letzte Dampfbehandlung:')
                ->format('Y-m-d'),
        ];
    }
}
