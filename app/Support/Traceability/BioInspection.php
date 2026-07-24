<?php

namespace App\Support\Traceability;

use Illuminate\Support\Collection;

/**
 * The goods-inbound organic inspection (Wareneingangskontrolle) recorded on a
 * delivery. Wraps the schemaless `deliveries.bio_inspection` JSON so the audit
 * checklist — which keys exist, which are positive vs. negative checks, whether
 * the delivery counts as released — has a single source of truth instead of
 * being re-derived from raw array keys across the graph, PDF and MCP layers.
 */
readonly class BioInspection
{
    /**
     * Positive checks: the recorded value should be true to pass.
     *
     * @var array<string, string>
     */
    private const POSITIVE_CHECKS = [
        'certificateValid' => 'Kontrollstellen-Nr. gültig',
        'goodsMatchValidity' => 'Ware entspricht Zertifizierungsbereich',
        'hasInvoice' => 'Rechnung vorhanden',
        'codeOnInvoice' => 'Kontrollstellen-Nr. auf Rechnung',
        'hasDeliveryNote' => 'Lieferschein vorhanden',
        'codeOnDeliveryNote' => 'Kontrollstellen-Nr. auf Lieferschein',
        'codeOnBag' => 'Kontrollstellen-Nr. auf Gebinde',
    ];

    /**
     * Negative checks: the recorded value should be false to pass.
     *
     * @var array<string, string>
     */
    private const NEGATIVE_CHECKS = [
        'damaged' => 'Keine Beschädigung',
        'pestInfection' => 'Kein Schädlingsbefall',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(private array $data) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): self
    {
        return new self($data ?? []);
    }

    public function isApproved(): bool
    {
        return (bool) ($this->data['approved'] ?? false);
    }

    /**
     * Every check with its resolved pass/fail state, in checklist order.
     *
     * @return list<array{label: string, ok: bool}>
     */
    public function checks(): array
    {
        $checks = [];

        foreach (self::POSITIVE_CHECKS as $key => $label) {
            $checks[] = ['label' => $label, 'ok' => (bool) ($this->data[$key] ?? false)];
        }

        foreach (self::NEGATIVE_CHECKS as $key => $label) {
            $checks[] = ['label' => $label, 'ok' => ! (bool) ($this->data[$key] ?? false)];
        }

        return $checks;
    }

    /**
     * The checks that did not pass.
     *
     * @return Collection<int, array{label: string, ok: bool}>
     */
    public function openFindings(): Collection
    {
        return collect($this->checks())->reject(fn (array $check) => $check['ok'])->values();
    }

    public function openFindingCount(): int
    {
        return $this->openFindings()->count();
    }
}
