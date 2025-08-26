<?php

namespace App\Filament\Resources\Bags\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Filament\Resources\Bags\BagResource;
use App\Models\Bag;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Livewire\Component;

class EditBag extends EditRecord
{
    protected static string $resource = BagResource::class;

    protected function afterSave(): void
    {
        $this->dispatch("bag.{$this->record->id}.updated");
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('discard')
                ->label('Entsorgen')
                ->color('danger')
                ->modalHeading('Sack entsorgen?')
                ->modalDescription('Der Sack kann danach nicht mehr in der Abfüllung verwenden werden.')
                ->requiresConfirmation()
                ->action(function (Bag $record, Component $livewire) {
                    $record->discard();
                    $this->redirectIntended(route('filament.admin.resources.bags.index'));
                }),
        ];
    }
}
