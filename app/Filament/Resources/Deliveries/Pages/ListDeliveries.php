<?php

namespace App\Filament\Resources\Deliveries\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Deliveries\DeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveries extends ListRecords
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
