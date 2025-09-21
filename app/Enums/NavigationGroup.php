<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup implements HasLabel
{
    case Overview;
    case System;
    case Stock;
    case Products;
    case Metadata;

    public function getLabel(): string
    {
        return match ($this) {
            self::Overview => __('Übersicht'),
            self::System => __('System'),
            self::Stock => __('Bestand'),
            self::Products => __('Produkte'),
            self::Metadata => __('Metadaten'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Overview => 'heroicon-o-home',
            self::System => 'heroicon-o-cog',
            self::Stock => 'heroicon-o-archive-box',
            self::Products => 'heroicon-o-cube',
            self::Metadata => 'heroicon-o-tag',
        };
    }
}
