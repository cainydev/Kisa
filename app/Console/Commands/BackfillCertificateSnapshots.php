<?php

namespace App\Console\Commands;

use App\Models\Delivery;
use App\Services\Traceability\CertificateSnapshotter;
use Illuminate\Console\Command;

class BackfillCertificateSnapshots extends Command
{
    protected $signature = 'certificates:backfill
        {--apply : Persist the snapshots. Without this flag the command only reports what would change.}';

    protected $description = 'Attach the supplier certificate valid at intake to deliveries that have none.';

    public function handle(CertificateSnapshotter $snapshotter): int
    {
        $apply = (bool) $this->option('apply');

        $deliveries = Delivery::query()
            ->whereNull('certificate_snapshot')
            ->with('supplier.certificates.bioInspector')
            ->get();

        if ($deliveries->isEmpty()) {
            $this->info('No deliveries without a certificate snapshot.');

            return self::SUCCESS;
        }

        $this->info(($apply ? 'Applying' : 'Dry run —').' backfill for '.$deliveries->count().' deliveries.');

        $matched = 0;

        /** @var array<int, array<string, string>> $gaps Deliveries with no covering certificate. */
        $gaps = [];

        foreach ($deliveries as $delivery) {
            $certificate = $delivery->supplier?->certificateForDate($delivery->delivered_date);

            if ($certificate === null) {
                $gaps[] = [
                    'supplier' => $delivery->supplier?->shortname ?? $delivery->supplier?->company ?? '?',
                    'date' => $delivery->delivered_date->toDateString(),
                    'delivery' => (string) $delivery->id,
                ];

                continue;
            }

            $matched++;

            $this->line(sprintf(
                '  Delivery #%d (%s, %s) → certificate %s [%s]',
                $delivery->id,
                $delivery->supplier->shortname,
                $delivery->delivered_date->toDateString(),
                $certificate->certificate_number ?? '?',
                $certificate->bioInspector?->label ?? '?',
            ));

            if ($apply) {
                $snapshotter->snapshotFromSupplier($delivery);
            }
        }

        $this->newLine();
        $this->info("Backfilled: {$matched}   Gaps (no covering certificate): ".count($gaps));

        if ($gaps !== []) {
            $this->newLine();
            $this->warn('Deliveries missing a certificate for their intake date — upload the covering certificate for these suppliers:');

            $bySupplier = collect($gaps)->groupBy('supplier')->sortKeys();

            foreach ($bySupplier as $supplier => $rows) {
                $dates = collect($rows)->pluck('date')->sort()->values();
                $this->line(sprintf('  %s — %d Lieferung(en): %s', $supplier, $dates->count(), $dates->implode(', ')));
            }
        }

        if (! $apply && $matched > 0) {
            $this->newLine();
            $this->comment('Re-run with --apply to persist these snapshots.');
        }

        return self::SUCCESS;
    }
}
