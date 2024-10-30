<?php

namespace App\Filament\Resources\BagResource\Pages;

use App\Filament\Resources\BagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBag extends EditRecord
{
    protected static string $resource = BagResource::class;

    protected function afterSave(): void
    {
        $this->dispatch('bag-updated');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
