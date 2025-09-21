<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use Filament\Pages\Page;
use UnitEnum;

class Settings extends Page
{
    protected static ?string $title = 'Einstellungen';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected string $view = 'filament.pages.settings';
}
