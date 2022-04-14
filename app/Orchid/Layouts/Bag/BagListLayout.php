<?php

namespace App\Orchid\Layouts\Bag;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;

class BagListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'bags';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
            ->width('50px'),
            TD::make('Inhalt')
            ->render(function ($bag) {
                return $bag->herb->name . ' ' . $bag->specification;
            }),
            TD::make('charge', 'Charge'),
            TD::make('bio', 'Bio')
            ->render(function ($bag) {
                return view('partials/boolean', ['value' => $bag->bio]);
            }),
            TD::make('size', 'Gebinde')
            ->render(function ($bag) {
                return $bag->getSizeInKilo();
            }),
            TD::make('Aktuelles Gewicht')
            ->render(function ($bag) {
                return sprintf('%ug', $bag->getCurrent());
            }),
            TD::make('Lieferung')
            ->render(function ($bag) {
                if($bag->delivery != null){
                    return $bag->delivery->supplier->shortname . ', ' . $bag->delivery->delivered_date->format('d.m.y');
                }
                return '<span style="color:red;">gelöscht</span>';
            }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($bag) {
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
