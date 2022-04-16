<?php

namespace App\Orchid\Screens\Abfuellen;

use Orchid\Screen\Screen;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Color;

use App\Orchid\Layouts\Abfuellen\AbfuellenListLayout;
use App\Models\Bottle;

class AbfuellenScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'bottles' => Bottle::paginate(20),
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Abfüllungen';
    }

    public function description(): ?string
    {
        return 'Einer Abfüllung ist immer ein Benutzer (der Abfüller) sowie das Datum der Abfüllung zugeordnet.';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Hinzufügen')
                ->icon('plus')
                ->class('btn btn-success')
                ->route('platform.bottle.edit')
        ];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            AbfuellenListLayout::class
        ];
    }
}
