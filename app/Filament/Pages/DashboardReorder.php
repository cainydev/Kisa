<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Filament\Widgets\Reorder;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DashboardReorder extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'dashboard-reorder';
    protected static ?string $title = 'Nachbestellen';
    protected static string|null|UnitEnum $navigationGroup = NavigationGroup::Overview;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public function getWidgets(): array
    {
        return [
            Reorder::make()
        ];
    }

}
