<?php

namespace App\Filament\Resources\BioInspectors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\BioInspectors\BioInspectorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBioInspectors extends ManageRecords
{
    protected static string $resource = BioInspectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
