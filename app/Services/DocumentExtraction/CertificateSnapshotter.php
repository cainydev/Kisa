<?php

namespace App\Services\DocumentExtraction;

use App\Models\Certificate;
use App\Models\Delivery;

class CertificateSnapshotter
{
    /**
     * Resolve the supplier's certificate valid on the delivery date and
     * snapshot it onto the delivery (frozen JSON fields + a copy of the PDF).
     *
     * Returns the certificate that was snapshotted, or null when the supplier
     * has no certificate covering the delivery date.
     */
    public function snapshotFromSupplier(Delivery $delivery): ?Certificate
    {
        $delivery->loadMissing('supplier.certificates');

        $certificate = $delivery->supplier?->certificateForDate($delivery->delivered_date);

        if ($certificate === null) {
            return null;
        }

        $this->applySnapshot($delivery, [
            'source_certificate_id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'operator_name' => $certificate->operator_name,
            'control_body' => $certificate->control_body,
            'control_body_code' => $certificate->control_body_code,
            'activities' => $certificate->activities,
            'product_categories' => $certificate->product_categories,
            'valid_from' => optional($certificate->valid_from)->toDateString(),
            'valid_until' => optional($certificate->valid_until)->toDateString(),
            'issued_at' => optional($certificate->issued_at)->toDateString(),
            'issued_place' => $certificate->issued_place,
        ]);

        $this->copyDocument($certificate, $delivery);

        return $certificate;
    }

    /**
     * Store an arbitrary snapshot (e.g. from a one-off document extraction)
     * directly onto the delivery. source_certificate_id stays null: this
     * snapshot did not originate from a supplier Certificate row.
     *
     * @param  array<string, mixed>  $fields
     */
    public function applySnapshot(Delivery $delivery, array $fields): void
    {
        $delivery->certificate_snapshot = array_merge(
            ['source_certificate_id' => null],
            $fields,
        );
        $delivery->save();
    }

    /**
     * Copy the certificate's PDF into the delivery's certificate collection,
     * so the delivery carries a frozen copy of the document at intake time.
     */
    private function copyDocument(Certificate $certificate, Delivery $delivery): void
    {
        $media = $certificate->getFirstMedia('document');

        if ($media === null) {
            return;
        }

        $delivery->clearMediaCollection('certificate');

        $media->copy($delivery, 'certificate');
    }
}
