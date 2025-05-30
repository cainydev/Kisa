<?php

namespace App\Filament\Resources\BottleResource\Pages;

use App\Filament\Resources\BottleResource;
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
            Actions\DeleteAction::make(),
        ];
    }
}
