<?php

namespace App\Orchid\Screens\Information;

use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

use App\Http\Controllers\ChartsController;

class DashboardScreen extends Screen
{
    public function query(): iterable
    {
        return [];
    }

    public function name(): ?string
    {
        return 'Dashboard';
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('charts.bags', [
                'bestbefore' => ChartsController::bagBestBefore(),
                'bio' => ChartsController::bagIsBio(),
                'soonSpoil' => ChartsController::bagIsSoonSpoiled(),
            ]),
            Layout::livewire('bag-usage')
        ];
    }
}
