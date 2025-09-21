<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Filament\Widgets\DailyQuote;
use App\Filament\Widgets\OrderChart;
use UnitEnum;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'dashboard';
    protected static ?string $title = 'Übersicht';
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Overview;

    public function getHeading(): string
    {
        // Oberfränkische Begrüßung je nach Tageszeit
        $time = now()->format('H:i');
        if ($time < '10:00') {
            return 'Guddn Moarn, ' . auth()->user()->name . '!';
        } elseif ($time < '14:00') {
            return 'Guddn Dog, ' . auth()->user()->name . '!';
        } elseif ($time < '18:00') {
            return 'Guddn Namiddach, ' . auth()->user()->name . '!';
        } else {
            return 'Guddn Omd, ' . auth()->user()->name . '!';
        }
    }

    public function getWidgets(): array
    {
        return [
            DailyQuote::make(),
            OrderChart::make(),
        ];
    }
}
