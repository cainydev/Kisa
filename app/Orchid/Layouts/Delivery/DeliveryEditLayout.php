<?php

namespace App\Orchid\Layouts\Delivery;

use App\Models\Supplier;
use App\Models\User;
use Orchid\Screen\Fields\DateTimer;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Layouts\Rows;

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
