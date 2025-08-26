<?php

namespace App\Filament\Resources\Herbs\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Herbs\HerbResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHerb extends EditRecord
{
    protected static string $resource = HerbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
