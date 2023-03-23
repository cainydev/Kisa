<?php

namespace App\Orchid\Screens\Abfuellen;

use App\Models\Bottle;
use Orchid\Screen\Screen;

class AbfuellenViewScreen extends Screen
{
    /**
     * Query data.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'bottle' => Bottle::all(),
        ];
    }

    /**
     * Display header name.
     */
    public function name(): ?string
    {
        return 'Abfüllung ansehen';
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * Views.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [];
    }
}
