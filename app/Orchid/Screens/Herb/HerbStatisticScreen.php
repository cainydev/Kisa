<?php

namespace App\Orchid\Screens\Herb;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

use App\Models\Herb;

class HerbStatisticScreen extends Screen
{
    public Herb $herb;

    /**
     * Query data.
     *
     * @return array
     */
    public function query(Herb $herb): iterable
    {
        return [
            'herb' => $herb,
        ];
    }

    /**
     * Display header name.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return 'Auswertung ' . $this->herb->name;
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
            Layout::livewire('herb-statistics')
        ];
    }
}
