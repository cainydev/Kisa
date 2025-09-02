<?php

namespace App\Filament\Resources\BagResource\Pages;

use App\Filament\Resources\BagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBag extends EditRecord
{
    protected static string $resource = BagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make('discard')
                ->label('Entsorgen')
                ->color('danger')
                ->modalHeading('Sack entsorgen?')
                ->modalDescription('Der Sack kann danach nicht mehr in der Abfüllung verwenden werden.')
                ->requiresConfirmation(),
            Actions\RestoreAction::make()->label('Aus dem Müll holen')
        ];
    }
}
