<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Services\Traceability\CertificateSnapshotter;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * On save, if the delivery has no certificate snapshot, try to resolve and
     * attach the one valid for its supplier + date. This covers deliveries
     * created before a covering certificate existed: once the certificate is
     * added to the supplier, simply re-saving the delivery attaches it. When
     * the supplier or date changed, the model already re-resolves the snapshot,
     * so this only fills a genuinely empty one.
     */
    protected function afterSave(): void
    {
        if ($this->record->certificateSummary() !== null) {
            return;
        }

        app(CertificateSnapshotter::class)->snapshotFromSupplier($this->record);
        $this->refreshFormData(['certificate_snapshot']);
    }

    /**
     * Remove the actions from the bottom of the form.
     *
     * @return array|Action[]|Actions\ActionGroup[]
     */
    public function getFormActions(): array
    {
        return [];
    }

    /**
     * @return array|Action[]|Actions\ActionGroup[]
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
