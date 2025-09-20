<?php

namespace App\Filament\Resources\Bags\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\Bags\BagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBag extends ViewRecord
{
    protected static string $resource = BagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
