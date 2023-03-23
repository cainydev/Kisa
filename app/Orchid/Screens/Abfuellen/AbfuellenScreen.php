<?php

namespace App\Orchid\Screens\Abfuellen;

use App\Models\Bottle;
use App\Orchid\Layouts\Abfuellen\AbfuellenListLayout;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Alert;

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
            'bottles' => Bottle::with(['user', 'positions'])
            ->orderByDesc('date')
            ->get(),
        ];
    }

    /**
     * Display header name.
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
                ->route('platform.bottle.edit'),
        ];
    }

    public function deleteBottle(Bottle $bottle)
    {
        foreach ($bottle->positions as $pos) {
            foreach ($pos->ingredients as $i) {
                $i->delete();
            }
            $pos->delete();
        }

        $bottle->delete();
        Alert::success('Abfüllung wurde gelöscht.');
    }

    public function layout(): iterable
    {
        return [
            AbfuellenListLayout::class,
        ];
    }
}
