<?php

namespace App\Filament\Resources\Bottles\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Bottles\BottleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBottle extends EditRecord
{
    protected static string $resource = BottleResource::class;

    /**
     * Remove the actions from the bottom of the form.
     *
     * @return array|Actions\Action[]|Actions\ActionGroup[]
     */
    public function getFormActions(): array
    {
        return [];
    }

    /**
     * Add the actions to the header of the form. 'form' is
     * the default ID of the form.
     *
     * @return array|Actions\Action[]|Actions\ActionGroup[]
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()->formId('form'),
            $this->getCancelFormAction()->formId('form'),
            DeleteAction::make(),
        ];
    }
}
