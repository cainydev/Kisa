<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Foundation\Inspiring;

class DailyQuote extends Widget
{
    protected string $view = 'filament.widgets.daily-quote';
    protected int|string|array $columnSpan = 'full';
    protected ?string $placeholderHeight = '30px';

    public function getQuote(): string
    {
        return Inspiring::quote();
    }
}
