<?php

namespace App\Filament\Resources\BioInspectorResource\Pages;

use App\Filament\Resources\BioInspectorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBioInspectors extends ManageRecords
{
    protected static string $resource = BioInspectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
