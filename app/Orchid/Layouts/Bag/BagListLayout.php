<?php

namespace App\Orchid\Layouts\Bag;

use App\Models\Bag;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;

class BagListLayout extends Table
{
    protected $target = 'bags';

    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
                ->width('100px')
                ->sort(),
            TD::make('Inhalt')
                ->render(function (Bag $bag) {
                    if (500 > $bag->getRedisCurrent())
                        return '<span style="color:red">' . $bag->herb->name . ' ' . $bag->specification . '</span>';
                    return $bag->herb->name . ' ' . $bag->specification;
                }),
            TD::make('charge', 'Charge'),
            TD::make('bio', 'Bio')
                ->render(function (Bag $bag) {
                    return view('partials/boolean', ['value' => $bag->bio]);
                }),
            TD::make('size', 'Gebinde')
                ->render(function (Bag $bag) {
                    return $bag->getSizeInKilo();
                }),
            TD::make('Aktuelles Gewicht')
                ->render(function (Bag $bag) {
                    return $bag->getRedisCurrent();
                }),
            TD::make('bestbefore', 'Haltbar bis')
                ->sort(),
            TD::make('Lieferung')
                ->render(function (Bag $bag) {
                    if ($bag->delivery != null) {
                        return Link::make($bag->delivery->supplier->shortname . ', ' . $bag->delivery->delivered_date->format('d.m.y'))
                            ->route('platform.deliveries.edit', $bag->delivery);
                    }
                    return '<span style="color:red;">gelöscht</span>';
                }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function (Bag $bag) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteBag', ['id' => $bag->id])
                            ->confirm('Willst du den Sack mit der ID »' . $bag->id . '« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.bags.edit', $bag),
                    ]);
                }),
        ];
    }
}
