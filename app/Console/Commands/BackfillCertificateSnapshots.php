<?php

namespace App\Console\Commands;

use App\Models\Delivery;
use App\Services\DocumentExtraction\CertificateSnapshotter;
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
            ->with('supplier.certificates')
            ->get();

        if ($deliveries->isEmpty()) {
            $this->info('No deliveries without a certificate snapshot.');

            return self::SUCCESS;
        }

        $this->info(($apply ? 'Applying' : 'Dry run —').' backfill for '.$deliveries->count().' deliveries.');

        $matched = 0;
        $unmatched = 0;

        foreach ($deliveries as $delivery) {
            $certificate = $delivery->supplier?->certificateForDate($delivery->delivered_date);

            if ($certificate === null) {
                $unmatched++;

                continue;
            }

            $matched++;

            $this->line(sprintf(
                '  Delivery #%d (%s, %s) → certificate %s [%s]',
                $delivery->id,
                $delivery->supplier->shortname,
                $delivery->delivered_date->toDateString(),
                $certificate->certificate_number ?? '?',
                $certificate->control_body_code ?? '?',
            ));

            if ($apply) {
                $snapshotter->snapshotFromSupplier($delivery);
            }
        }

        $this->newLine();
        $this->info("Matched: {$matched}   Unmatched (no valid certificate): {$unmatched}");

        if (! $apply && $matched > 0) {
            $this->comment('Re-run with --apply to persist these snapshots.');
        }

        return self::SUCCESS;
    }
}
