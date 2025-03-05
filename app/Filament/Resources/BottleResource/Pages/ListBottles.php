<?php

namespace App\Filament\Resources\BottleResource\Pages;

use App\Filament\Resources\BottleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBottles extends ListRecords
{
    protected static string $resource = BottleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
