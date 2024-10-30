<?php

namespace App\Filament\Resources\BagResource\Pages;

use App\Filament\Resources\BagResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBag extends ViewRecord
{
    protected static string $resource = BagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
