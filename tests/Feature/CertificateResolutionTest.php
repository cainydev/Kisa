<?php

use App\Models\Certificate;
use App\Models\Supplier;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->supplier = Supplier::factory()->create();
});

/**
 * Issue a certificate for the supplier under test. Dates are passed as plain
 * strings to keep the validity windows readable at a glance.
 */
function certificate(string $number, string $validFrom, string $validUntil, ?string $issuedAt = null): Certificate
{
    return Certificate::factory()->for(test()->supplier)->create([
        'certificate_number' => $number,
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'issued_at' => $issuedAt,
    ]);
}

/**
 * Resolve the certificate the supplier held on the given date.
 */
function certificateOn(string $date): ?Certificate
{
    return test()->supplier->load('certificates')->certificateForDate(Carbon::parse($date));
}

describe('certificate resolution', function () {
    it('returns null when no certificate covers the date', function () {
        certificate('EXPIRED', '2024-01-01', '2024-12-31');

        expect(certificateOn('2025-06-15'))->toBeNull();
    });

    it('resolves the certificate covering the date', function () {
        $covering = certificate('COVERING', '2025-01-01', '2025-12-31', '2025-01-01');
        certificate('OTHER-YEAR', '2024-01-01', '2024-12-31', '2024-01-01');

        expect(certificateOn('2025-06-15'))->toBeCertificate($covering);
    });

    it('lets a reissued certificate supersede an earlier one covering the same date', function () {
        certificate('ORIGINAL', '2025-01-01', '2025-12-31', '2025-01-01');
        $reissued = certificate('REISSUED', '2025-01-01', '2025-12-31', '2025-05-20');

        // The most recently issued certificate must win when both cover the date.
        expect(certificateOn('2025-06-15'))->toBeCertificate($reissued);
    });

    it('handles a renewal issued during the previous certificates validity', function () {
        certificate('CURRENT', '2025-01-01', '2025-12-31', '2025-01-01');
        $renewal = certificate('RENEWAL', '2026-01-01', '2026-12-31', '2025-11-15');

        // A date only the renewal covers resolves to the renewal, even though
        // the renewal was issued while the current certificate was still valid.
        expect(certificateOn('2026-03-01'))->toBeCertificate($renewal);
    });

    it('falls back to validity start when the issue date is missing', function () {
        certificate('EARLIER-START', '2025-01-01', '2025-12-31');
        $laterStart = certificate('LATER-START', '2025-03-01', '2025-12-31');

        expect(certificateOn('2025-06-15'))->toBeCertificate($laterStart);
    });
});
