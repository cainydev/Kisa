<?php

namespace App\Filament\Resources\Bags\Pages;

use App\Filament\Resources\Bags\BagResource;
use App\Traits\HasBackUrl;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBag extends EditRecord
{
    use HasBackUrl;

    protected static string $resource = BagResource::class;

    protected function afterSave(): void
    {
        $this->dispatch("bag.{$this->record->id}.updated");
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->color('gray')
                ->label('Zurück')
                ->url($this->backUrl),
            DeleteAction::make()
                ->button()
                ->label('Entsorgen')
                ->color('danger')
                ->modalHeading('Sack entsorgen?')
                ->modalDescription('Der Sack kann danach nicht mehr in der Abfüllung verwenden werden.')
                ->requiresConfirmation()
                ->successNotificationTitle('Sack entsorgt'),
            RestoreAction::make()
                ->button()
                ->label('Wiederherstellen')
                ->successNotificationTitle('Sack wiederhergestellt')
        ];
    }
}
