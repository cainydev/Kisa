<?php

namespace App\Orchid\Layouts\Delivery;

use Orchid\Screen\Layouts\Rows;
use Orchid\Screen\Fields\{DateTimer, Relation};

use App\Models\{User, Supplier};

class DeliveryEditLayout extends Rows
{
    protected function fields(): iterable
    {
        return [
            DateTimer::make('delivery.delivered_date')
            ->title('Lieferdatum')
            ->value(now())
                ->required()
                ->format('Y-m-d'),

            Relation::make('delivery.user_id')
            ->title('Empfänger')
                ->help('Der Mitarbeiter, der die Lieferungen angenommen hat.')
                ->required()
                ->fromModel(User::class, 'name'),
            Relation::make('delivery.supplier_id')
            ->title('Lieferant')
                ->required()
                ->fromModel(Supplier::class, 'shortname'),
        ];
    }
}
