<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Jobs\ExtractDocument;
use App\Models\Supplier;
use App\Services\DocumentExtraction\DeliveryNoteExtractionAgent;
use App\Services\DocumentExtraction\ExtractionStatus;
use App\Services\DocumentExtraction\InvoiceExtractionAgent;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * Cache id of the in-flight extraction, or null when idle. Public so the
     * Livewire page can poll it via the footer view's wire:poll.
     */
    public ?string $extractionStatusId = null;

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
            $this->fillFromDocumentAction('deliveryNote', 'Aus Lieferschein befüllen', DeliveryNoteExtractionAgent::class),
            $this->fillFromDocumentAction('invoice', 'Aus Rechnung befüllen', InvoiceExtractionAgent::class),
            DeleteAction::make(),
        ];
    }

    /**
     * A footer view that polls while an extraction is running. When idle it
     * renders nothing and does not poll.
     */
    public function getFooter(): ?View
    {
        return view('filament.deliveries.extraction-poll', [
            'processing' => $this->extractionStatusId !== null,
        ]);
    }

    /**
     * @param  class-string  $agentClass
     */
    private function fillFromDocumentAction(string $collection, string $label, string $agentClass): Action
    {
        return Action::make("fillFrom_{$collection}")
            ->label($label)
            ->icon('heroicon-o-sparkles')
            ->disabled(fn (): bool => $this->extractionStatusId !== null)
            ->action(function () use ($collection, $agentClass): void {
                $media = $this->record->getFirstMedia($collection);

                if ($media === null) {
                    Notification::make()
                        ->title('Kein Dokument vorhanden. Bitte zuerst hochladen und speichern.')
                        ->warning()
                        ->send();

                    return;
                }

                $status = ExtractionStatus::start();
                $this->extractionStatusId = $status->id;

                ExtractDocument::dispatch($agentClass, $media, $status->id);

                Notification::make()
                    ->title('Dokument wird verarbeitet…')
                    ->info()
                    ->send();
            });
    }

    /**
     * Called by the footer view's wire:poll. When the extraction finishes,
     * map the fields onto the form for review, then stop polling.
     */
    public function pollExtraction(): void
    {
        if ($this->extractionStatusId === null) {
            return;
        }

        $status = ExtractionStatus::find($this->extractionStatusId);

        if ($status === null || ! $status->isFinished()) {
            return;
        }

        if ($status->state === ExtractionStatus::STATE_FAILED) {
            $error = $status->error;
            $this->extractionStatusId = null;

            Notification::make()
                ->title('Extraktion fehlgeschlagen')
                ->body($error)
                ->danger()
                ->send();

            return;
        }

        $this->applyExtraction($status->result ?? []);
        $this->extractionStatusId = null;

        Notification::make()
            ->title('Felder aus dem Dokument befüllt. Bitte prüfen.')
            ->success()
            ->send();
    }

    /**
     * Map the extracted document fields onto the delivery form. Only the
     * delivery date is set directly; the detected supplier name is matched to
     * an existing supplier when possible, otherwise surfaced as a hint.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyExtraction(array $data): void
    {
        $date = $data['delivered_date'] ?? $data['invoice_date'] ?? null;

        if (! empty($date)) {
            $this->fillFormField('delivered_date', $date);
        }

        $supplierName = $data['supplier_name'] ?? null;

        if (! empty($supplierName)) {
            $this->suggestSupplier($supplierName);
        }
    }

    private function fillFormField(string $field, mixed $value): void
    {
        $state = $this->form->getRawState();
        $state[$field] = $value;
        $this->form->fill($state);
    }

    /**
     * Match the detected supplier name to an existing supplier. On a match set
     * the relation; otherwise surface the name for manual selection.
     */
    private function suggestSupplier(string $supplierName): void
    {
        $supplier = Supplier::query()
            ->where('company', 'like', "%{$supplierName}%")
            ->orWhere('shortname', 'like', "%{$supplierName}%")
            ->first();

        if ($supplier !== null) {
            $this->fillFormField('supplier_id', $supplier->id);

            return;
        }

        Notification::make()
            ->title("Lieferant erkannt: {$supplierName}")
            ->body('Kein passender Lieferant gefunden — bitte manuell wählen.')
            ->warning()
            ->send();
    }
}
