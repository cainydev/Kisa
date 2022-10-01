<?php

namespace App\Orchid\Screens\Abfuellen;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

use App\Models\Bottle;
use Orchid\Screen\Actions\Link;

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
        return 'Rezepte vom ' . $this->bottle->date->format('d.m.Y');
    }

    /**
     * Button commands.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
            Link::make('Zurück')
                ->icon('action-undo')
                ->class('btn btn-link')
                ->route('platform.bottle.edit', $this->bottle),
            Link::make('Neue Abfüllung')
                ->class('btn btn-success hover:text-white')
                ->route('platform.bottle.edit', null)
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
            Layout::livewire('recipes')
        ];
    }
}
