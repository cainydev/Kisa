<?php

namespace App\Filament\Resources\Deliveries\Pages;

use Illuminate\Contracts\View\View;
use App\Filament\Resources\Deliveries\DeliveryResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * Remove the actions from the bottom of the form.
     *
     * @return array|Action[]|ActionGroup[]
     */
    public function getFormActions(): array
    {
        return [];
    }

    /**
     * Add the actions to the header of the form. 'form' is
     * the default ID of the form.
     *
     * @return array|Action[]|ActionGroup[]
     */
    public function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()->formId('form'),
            $this->getCreateAnotherFormAction()->formId('form'),
            $this->getCancelFormAction()->formId('form')
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.settings.DeliverySaveFirst');
    }
}
