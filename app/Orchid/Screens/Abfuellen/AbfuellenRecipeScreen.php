<?php

namespace App\Orchid\Screens\Abfuellen;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

use App\Models\Bottle;

class AbfuellenRecipeScreen extends Screen
{

    /**
     * @var Bottle
     */
    public $bottle;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(Bottle $bottle): iterable
    {
        return [
            'bottle' => $bottle
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Rezepte';
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
        return [
            Layout::livewire('recipes')
        ];
    }
}
