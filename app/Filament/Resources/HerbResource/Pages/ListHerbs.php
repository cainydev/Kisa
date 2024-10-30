<?php

namespace App\Filament\Resources\HerbResource\Pages;

use App\Filament\Resources\HerbResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHerbs extends ListRecords
{
    protected static string $resource = HerbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
