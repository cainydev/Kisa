<?php

namespace App\Orchid\Layouts\Delivery;

use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Rows;

use Orchid\Screen\Fields\{Relation, Input, CheckBox, DateTimer};
use Orchid\Support\Facades\Layout;

use App\Models\Herb;
use Orchid\Screen\Actions\Button;

class DeliveryBagsLayout extends Rows
{

    protected function fields(): iterable
    {
        return [
            Relation::make('currentBag.herb_id')
                ->fromModel(Herb::class, 'name')
                ->title('Rohstoff auswählen'),

            Input::make('currentBag.specification')
            ->title('Spezifikation')
            ->help('z.B. ägypt. BIO geschnitten'),

            Input::make('currentBag.charge')
                ->title('Chargennummer des Herstellers'),

            CheckBox::make('currentBag.bio')
                ->value(true)
                ->sendTrueOrFalse()
                ->title('Bio-Zertifiziert?'),

            Input::make('currentBag.size')
                ->type('number')
                ->title('Gebindegröße in g'),

            DateTimer::make('currentBag.bestbefore')
                ->title('Mindestens haltbar bis:')
                ->value(now()->addYears(3))
                ->format('Y-m-d'),

            DateTimer::make('currentBag.steamed')
                ->title('Letzte Dampfbehandlung:')
                ->format('Y-m-d'),

            Button::make('Hinzufügen')
                ->class('btn btn-success')
                ->method('addBag')
        ];
    }
}
