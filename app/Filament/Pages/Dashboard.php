<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\NecessaryBottle;
use App\Filament\Widgets\NextBottles;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            NecessaryBottle::make(),
            NextBottles::make(),
        ];
    }
}
