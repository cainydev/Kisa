<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Jobs\ExtractDocument;
use App\Models\Herb;
use App\Models\Supplier;
use App\Services\DocumentExtraction\DeliveryNoteExtractionAgent;
use App\Services\DocumentExtraction\ExtractionStatus;
use App\Services\DocumentExtraction\HerbMatcher;
use App\Services\DocumentExtraction\InvoiceExtractionAgent;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
     * Extracted delivery-note positions awaiting review, pre-matched to herbs.
     * Seeded on poll completion, consumed by the reviewPositions modal.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $pendingPositions = [];

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
            $this->reviewPositionsAction(),
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
     * The review modal for extracted positions. Opened programmatically via
     * mountAction('reviewPositions') once extraction completes; its repeater is
     * seeded from $pendingPositions. On submit it creates the Bag records — the
     * human confirmation is the gate before anything is written to the database.
     */
    private function reviewPositionsAction(): Action
    {
        return Action::make('reviewPositions')
            ->label('Positionen prüfen')
            ->modalHeading('Erkannte Positionen prüfen')
            ->modalDescription('Bitte die aus dem Lieferschein erkannten Positionen prüfen und Rohstoffe zuordnen, bevor sie angelegt werden.')
            ->modalSubmitActionLabel('Positionen anlegen')
            ->fillForm(fn (): array => ['positions' => $this->pendingPositions])
            ->schema([
                Repeater::make('positions')
                    ->label('Positionen')
                    ->schema([
                        Select::make('herb_id')
                            ->label('Rohstoff')
                            ->options(fn (): array => Herb::query()->orderBy('fullname')->pluck('fullname', 'id')->all())
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('specification')
                            ->label('Spezifikation'),
                        TextInput::make('charge')
                            ->label('Charge')
                            ->required(),
                        TextInput::make('size')
                            ->label('Gebindegröße')
                            ->numeric()
                            ->suffix('g')
                            ->required(),
                        DatePicker::make('bestbefore')
                            ->label('Haltbar bis'),
                        Toggle::make('bio')
                            ->label('Bio')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->defaultItems(0),
            ])
            ->action(function (array $data): void {
                $this->createPositions($data['positions'] ?? []);
            });
    }

    /**
     * Called by the footer view's wire:poll. When the extraction finishes,
     * fill the header fields and, for delivery notes, open the positions
     * review modal, then stop polling.
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

        $result = $status->result ?? [];
        $this->extractionStatusId = null;

        $filled = $this->applyHeader($result);

        Notification::make()
            ->title('Dokument ausgewertet. Bitte im Reiter „Allgemein" prüfen.')
            ->body($filled === [] ? 'Keine Kopfdaten erkannt.' : implode("\n", $filled))
            ->success()
            ->send();

        $positions = $this->preparePositions($result['positions'] ?? []);

        if ($positions !== []) {
            $this->pendingPositions = $positions;
            $this->mountAction('reviewPositions');
        }
    }

    /**
     * Fill the delivery header (date + supplier) from extracted data. Uses
     * fillPartially so the DatePicker/Select components re-hydrate and render.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function applyHeader(array $data): array
    {
        $values = [];
        $filled = [];

        $date = $data['delivered_date'] ?? $data['invoice_date'] ?? null;

        if (! empty($date)) {
            $values['delivered_date'] = $date;
            $filled[] = "Lieferdatum: {$date}";
        }

        $supplierName = $data['supplier_name'] ?? null;

        if (! empty($supplierName)) {
            $supplier = Supplier::query()
                ->where('company', 'like', "%{$supplierName}%")
                ->orWhere('shortname', 'like', "%{$supplierName}%")
                ->first();

            if ($supplier !== null) {
                $values['supplier_id'] = $supplier->id;
                $filled[] = "Lieferant: {$supplier->shortname}";
            } else {
                $filled[] = "Lieferant erkannt: {$supplierName} (kein Treffer — bitte manuell wählen)";
            }
        }

        if ($values !== []) {
            $this->form->fillPartially($values, array_keys($values));
        }

        return $filled;
    }

    /**
     * Map extracted positions to repeater rows, pre-matching each herb name to
     * an existing Herb. Fields align with the BagsRelationManager form so the
     * created records are consistent with manual entry.
     *
     * @param  array<int, array<string, mixed>>  $positions
     * @return array<int, array<string, mixed>>
     */
    private function preparePositions(array $positions): array
    {
        $matcher = app(HerbMatcher::class);

        return collect($positions)
            ->map(fn (array $position): array => [
                'herb_id' => $matcher->match($position['herb_name'] ?? null),
                'specification' => $position['specification'] ?? null,
                'charge' => $position['charge'] ?? null,
                'size' => $position['size_grams'] ?? null,
                'bestbefore' => $position['best_before'] ?? null,
                'bio' => (bool) ($position['bio'] ?? true),
            ])
            ->all();
    }

    /**
     * Create the reviewed positions as Bag records on this delivery.
     *
     * @param  array<int, array<string, mixed>>  $positions
     */
    private function createPositions(array $positions): void
    {
        $created = 0;

        foreach ($positions as $position) {
            if (empty($position['herb_id']) || empty($position['charge'])) {
                continue;
            }

            $this->record->bags()->create([
                'herb_id' => $position['herb_id'],
                'specification' => $position['specification'] ?? '',
                'charge' => $position['charge'],
                'size' => $position['size'] ?? 0,
                'bestbefore' => $position['bestbefore'] ?? now()->addYears(2),
                'steamed' => null,
                'bio' => $position['bio'] ?? true,
            ]);

            $created++;
        }

        $this->pendingPositions = [];

        Notification::make()
            ->title("{$created} Position(en) angelegt.")
            ->success()
            ->send();

        // Refresh so the Bags relation manager shows the new records.
        $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->record]));
    }
}
