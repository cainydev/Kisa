<?php

namespace App\Orchid\Layouts\Delivery;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;

class DeliveryListLayout extends Table
{

    protected $target = 'deliveries';

    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
                ->width('100px'),
            TD::make('delivered_date', 'Lieferdatum'),
            TD::make('bio_inspection', 'Eingangskontrolle')
                ->render(function ($delivery) {
                    return view('partials/inspection', ['inspection' => $delivery->bio_inspection]);
                }),
            TD::make('Säcke')
            ->render(function ($delivery) {
                return view('partials/bags', ['bags' => $delivery->bags]);
            }),
            TD::make('user_id', 'Empfänger')
                ->render(function ($delivery) {
                    return $delivery->user->name;
                }),
            TD::make('supplier_id', 'Lieferant')
                ->render(function ($delivery) {
                    return $delivery->supplier->shortname;
                }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($delivery) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteDelivery', ['id' => $delivery->id])
                            ->confirm('Willst du die Lieferung mit der ID »' . $delivery->id . '« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.deliveries.edit', $delivery),
                    ]);
                }),
        ];
    }
}
