<?php

namespace App\Orchid\Layouts\Delivery;

use Orchid\Screen\Actions\Button;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DeliveryListBagLayout extends Table
{
    protected $target = 'delivery.bags';


    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID'),
            TD::make('herb.name', 'Kraut'),
            TD::make('size', 'Gebinde')
            ->render(function($bag) {
                return sprintf('%.1fkg ', ($bag->size / 1000.0));
            }),
            TD::make('charge', 'Charge'),
            TD::make('bio', 'Bio?')
            ->render(function ($bag) {
                return view('partials/boolean', ['value' => $bag->bio]);
            }),
            TD::make('delete', 'Löschen')
            ->render(function ($bag) {
                return Button::make()
                    ->class('btn btn-danger p-2')
                    ->method('deleteBag', ['bag' => $bag->id])
                    ->icon('trash');
            })
        ];
    }
}
