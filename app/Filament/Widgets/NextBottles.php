<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class NextBottles extends Widget
{
    protected static string $view = 'filament.widgets.next-bottles';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        // TODO: Calculate good bottles to do next
        // maybe group them based on same recipes? some smart algo we need

        return [

        ];
    }
}
