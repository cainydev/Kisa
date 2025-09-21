<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Filament\Widgets\NecessaryBottle;
use App\Filament\Widgets\NextBottles;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DashboardBottles extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'dashboard-bottles';
    protected static ?string $title = 'Nächste Abfüllungen';
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Overview;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;

    public function getWidgets(): array
    {
        return [
            NecessaryBottle::make(),
            NextBottles::make(),
        ];
    }
}
