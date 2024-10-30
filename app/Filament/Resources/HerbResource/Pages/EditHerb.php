<?php

namespace App\Filament\Resources\HerbResource\Pages;

use App\Filament\Resources\HerbResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHerb extends EditRecord
{
    protected static string $resource = HerbResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
