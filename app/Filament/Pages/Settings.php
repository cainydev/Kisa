<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Settings extends Page
{
    protected static ?string $title = 'Einstellungen';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog';

    protected string $view = 'filament.pages.settings';
}
