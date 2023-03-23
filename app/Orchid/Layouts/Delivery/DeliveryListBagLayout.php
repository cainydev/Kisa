<?php

namespace App\Orchid\Layouts\Delivery;

use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class DeliveryListBagLayout extends Table
{
    protected $target = 'delivery.bags';

    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID'),
            TD::make('herb.name', 'Kraut'),
            TD::make('size', 'Gebinde')
                ->render(function ($bag) {
                    return sprintf('%.1fkg ', ($bag->size / 1000.0));
                }),
            TD::make('charge', 'Charge'),
            TD::make('bio', 'Bio?')
                ->render(function ($bag) {
                    return view('partials/boolean', ['value' => $bag->bio]);
                }),
            TD::make('delete', 'Aktionen')
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($bag) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteBag', ['bag' => $bag->id])
                            ->icon('trash'),
                        Link::make()
                            ->class('btn btn-primary')
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.bags.edit', $bag),
                    ]);
                }),
        ];
    }
}
