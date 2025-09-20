<?php

namespace App\Filament\Resources\Bottles\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Bottles\BottleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBottles extends ListRecords
{
    protected static string $resource = BottleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
