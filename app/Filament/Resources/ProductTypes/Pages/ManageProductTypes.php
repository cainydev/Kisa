<?php

namespace App\Filament\Resources\ProductTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ProductTypes\ProductTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProductTypes extends ManageRecords
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
