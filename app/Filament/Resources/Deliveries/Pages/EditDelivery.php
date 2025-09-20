<?php

namespace App\Filament\Resources\Deliveries\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Deliveries\DeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

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
