<?php

namespace App\Orchid\Layouts\Abfuellen;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;
use Orchid\Screen\Actions\{Link, Button};
use App\Orchid\Fields\Group;

class AbfuellenListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'bottles';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')->width('100px'),
            TD::make('user_id', 'Abfüller')
                ->render(function ($bottle) {
                    return $bottle->user->name;
                })->width('80px'),
            TD::make('date', 'Datum')->width('100px'),
            TD::make('Fertig abgefüllt')
                ->render(function ($bottle) {
                    return view('partials/boolean', ['value' => $bottle->finished()]);
                })->width('50px'),
            TD::make('Abgefüllt')
                ->render(function ($bottle) {
                    $s = "";
                    foreach ($bottle->positions as $pos) {
                        $s .= $pos->variant->product->name . ', ';
                    }
                    if (strlen($s) > 120) {
                        return substr(rtrim(trim($s), ','), 0, 120) . '...';
                    }
                    return rtrim(trim($s), ',');
                }),
            TD::make()
                ->width('50px')
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($bottle) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteBottle', ['id' => $bottle->id])
                            ->confirm('Willst du die Abfüllung mit der ID »' . $bottle->id . '« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.bottle.edit', $bottle),
                    ]);
                }),
        ];
    }
}
