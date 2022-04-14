<?php

namespace App\Orchid\Layouts\Herb;

use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;

class HerbListLayout extends Table
{
    protected $target = 'herbs';

    public static function perPage(): int
    {
        return 30;
    }

    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID')
            ->width('100px'),
            TD::make('name', 'Bezeichnung'),
            TD::make('supplier_id', 'Standardlieferant')
            ->render(function ($herb) {
                return $herb->standardSupplier->shortname;
            }),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($herb) {
                    return Group::make([
                        Button::make()
                            ->class('btn btn-danger p-2')
                            ->method('deleteHerb', ['id' => $herb->id])
                            ->confirm('Willst du den Rohstoff »' . $herb->name . '« wirklich löschen?')
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.herbs.edit', $herb),
                    ]);
                }),
        ];
    }
}
