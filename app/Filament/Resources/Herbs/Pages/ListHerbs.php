<?php

namespace App\Filament\Resources\Herbs\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Herbs\HerbResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHerbs extends ListRecords
{
    protected static string $resource = HerbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
