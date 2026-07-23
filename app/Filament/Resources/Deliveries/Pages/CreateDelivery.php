<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Models\Supplier;
use App\Services\Traceability\CertificateSnapshotter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class CreateDelivery extends CreateRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * Auto-attach the supplier's valid organic certificate as a frozen
     * snapshot on the new delivery. A missing certificate is expected for
     * many deliveries, so it is silently skipped and can be backfilled later.
     */
    protected function afterCreate(): void
    {
        app(CertificateSnapshotter::class)->snapshotFromSupplier($this->record);
    }

    /**
     * Whether the picked supplier + date lack a covering organic certificate.
     * Drives the "create anyway?" confirmation.
     */
    protected function lacksCoveringCertificate(): bool
    {
        $supplierId = $this->data['supplier_id'] ?? null;
        $date = $this->data['delivered_date'] ?? null;

        if (blank($supplierId) || blank($date)) {
            return false;
        }

        $certificate = Supplier::with('certificates')
            ->find($supplierId)
            ?->certificateForDate(Carbon::parse($date));

        return $certificate === null;
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
            $this->getCreateFormAction()
                ->formId('form')
                ->requiresConfirmation(fn (): bool => $this->lacksCoveringCertificate())
                ->modalHeading('Kein gültiges Zertifikat')
                ->modalDescription('Für den gewählten Lieferanten und das Lieferdatum ist kein gültiges Bio-Zertifikat hinterlegt. Die Lieferung wird ohne Zertifikats-Snapshot angelegt und kann später nachgetragen werden. Trotzdem anlegen?')
                ->modalSubmitActionLabel('Trotzdem anlegen'),
            $this->getCreateAnotherFormAction()->formId('form'),
            $this->getCancelFormAction()->formId('form'),
        ];
    }

    public function getFooter(): ?View
    {
        return view('filament.settings.DeliverySaveFirst');
    }
}
