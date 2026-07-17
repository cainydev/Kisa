<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Services\DocumentExtraction\CertificateSnapshotter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\View\View;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * Auto-attach the supplier's valid organic certificate as a frozen
     * snapshot on the new delivery. A missing certificate is expected for
     * many deliveries, so it is silently skipped.
     */
    protected function afterCreate(): void
    {
        app(CertificateSnapshotter::class)->snapshotFromSupplier($this->record);
    }

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
            $this->getCancelFormAction()->formId('form'),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.settings.DeliverySaveFirst');
    }
}
