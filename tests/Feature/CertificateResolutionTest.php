<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CertificateResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_when_no_certificate_covers_the_date(): void
    {
        $supplier = Supplier::factory()->create();
        Certificate::factory()->for($supplier)->create([
            'valid_from' => '2024-01-01',
            'valid_until' => '2024-12-31',
        ]);

        $supplier->load('certificates');

        $this->assertNull($supplier->certificateForDate(Carbon::parse('2025-06-15')));
    }

    public function test_it_resolves_the_certificate_covering_the_date(): void
    {
        $supplier = Supplier::factory()->create();
        $covering = Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'COVERING',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'issued_at' => '2025-01-01',
        ]);
        Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'OTHER-YEAR',
            'valid_from' => '2024-01-01',
            'valid_until' => '2024-12-31',
            'issued_at' => '2024-01-01',
        ]);

        $supplier->load('certificates');

        $this->assertTrue(
            $covering->is($supplier->certificateForDate(Carbon::parse('2025-06-15')))
        );
    }

    public function test_a_reissued_certificate_supersedes_an_earlier_one_covering_the_same_date(): void
    {
        $supplier = Supplier::factory()->create();
        Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'ORIGINAL',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'issued_at' => '2025-01-01',
        ]);
        $reissued = Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'REISSUED',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'issued_at' => '2025-05-20',
        ]);

        $supplier->load('certificates');

        $this->assertTrue(
            $reissued->is($supplier->certificateForDate(Carbon::parse('2025-06-15'))),
            'The most recently issued certificate must win when both cover the date.'
        );
    }

    public function test_it_handles_a_renewal_issued_during_the_previous_certificates_validity(): void
    {
        $supplier = Supplier::factory()->create();
        Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'CURRENT',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'issued_at' => '2025-01-01',
        ]);
        $renewal = Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'RENEWAL',
            'valid_from' => '2026-01-01',
            'valid_until' => '2026-12-31',
            'issued_at' => '2025-11-15',
        ]);

        $supplier->load('certificates');

        // A date only the renewal covers resolves to the renewal, even though
        // the renewal was issued while the current certificate was still valid.
        $this->assertTrue(
            $renewal->is($supplier->certificateForDate(Carbon::parse('2026-03-01')))
        );
    }

    public function test_it_falls_back_to_validity_start_when_issue_date_is_missing(): void
    {
        $supplier = Supplier::factory()->create();
        Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'EARLIER-START',
            'valid_from' => '2025-01-01',
            'valid_until' => '2025-12-31',
            'issued_at' => null,
        ]);
        $laterStart = Certificate::factory()->for($supplier)->create([
            'certificate_number' => 'LATER-START',
            'valid_from' => '2025-03-01',
            'valid_until' => '2025-12-31',
            'issued_at' => null,
        ]);

        $supplier->load('certificates');

        $this->assertTrue(
            $laterStart->is($supplier->certificateForDate(Carbon::parse('2025-06-15')))
        );
    }
}
