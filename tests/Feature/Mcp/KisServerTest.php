<?php

use App\Mcp\Servers\KisServer;
use App\Mcp\Tools\CreateCertificateTool;
use App\Mcp\Tools\CreateControlBodyTool;
use App\Mcp\Tools\CreateDeliveryTool;
use App\Mcp\Tools\CreateHerbTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreateSupplierTool;
use App\Mcp\Tools\DiscardBagsTool;
use App\Mcp\Tools\FindBagsByHerbTool;
use App\Mcp\Tools\GetDeliveryTool;
use App\Mcp\Tools\GetHerbTool;
use App\Mcp\Tools\ListControlBodiesTool;
use App\Mcp\Tools\ListHerbsTool;
use App\Mcp\Tools\ListSuppliersTool;
use App\Mcp\Tools\RequestUploadUrlTool;
use App\Models\Bag;
use App\Models\BioInspector;
use App\Models\Certificate;
use App\Models\Delivery;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Traceability\CertificateSnapshotter;

beforeEach(function () {
    User::create([
        'name' => 'Marcus Wagner',
        'email' => 'marcus@example.test',
        'password' => bcrypt('secret'),
    ]);
});

/**
 * Create an active (non-discarded) bag for a herb, defeating the factory's
 * random afterCreating discard so tests are deterministic.
 */
function activeBag(Herb $herb, array $attributes = []): Bag
{
    $bag = Bag::factory()->create(['herb_id' => $herb->id, ...$attributes]);

    if ($bag->trashed()) {
        $bag->restore();
    }

    return $bag;
}

describe('herbs', function () {
    it('lists herbs with their stock', function () {
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);

        KisServer::tool(ListHerbsTool::class, [])
            ->assertOk()
            ->assertSee('Kamille')
            ->assertSee('Galke');
    });

    it('resolves a herb by name', function () {
        Herb::factory()->create(['name' => 'Pfefferminze', 'fullname' => 'Pfefferminzblätter']);

        KisServer::tool(GetHerbTool::class, ['herb' => 'Pfefferminze'])
            ->assertOk()
            ->assertSee('Pfefferminzblätter');
    });

    it('errors clearly when the herb is not found', function () {
        KisServer::tool(GetHerbTool::class, ['herb' => 'Nichtvorhanden'])
            ->assertHasErrors();
    });

    it('creates a herb and links its supplier', function () {
        $supplier = Supplier::factory()->create(['shortname' => 'Dragonspice']);

        KisServer::tool(CreateHerbTool::class, [
            'name' => 'Salbei',
            'fullname' => 'Salbeiblätter',
            'supplier' => 'Dragonspice',
        ])->assertOk()->assertSee('Salbei');

        $this->assertDatabaseHas('herbs', ['name' => 'Salbei', 'supplier_id' => $supplier->id]);
    });

    it('rejects a duplicate herb', function () {
        Herb::factory()->create(['name' => 'Thymian']);

        KisServer::tool(CreateHerbTool::class, ['name' => 'Thymian'])
            ->assertHasErrors();
    });

    it('errors on an unknown supplier without writing the herb', function () {
        KisServer::tool(CreateHerbTool::class, ['name' => 'Rosmarin', 'supplier' => 'DoesNotExist'])
            ->assertHasErrors();

        $this->assertDatabaseMissing('herbs', ['name' => 'Rosmarin']);
    });
});

describe('suppliers', function () {
    it('shows the current control body for each supplier', function () {
        $inspector = BioInspector::factory()->create(['label' => 'DE-ÖKO-001']);
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Certificate::factory()->for($supplier)->create([
            'bio_inspector_id' => $inspector->id,
            'valid_from' => now()->subMonth(),
            'valid_until' => now()->addYear(),
            'issued_at' => now()->subMonth(),
        ]);

        KisServer::tool(ListSuppliersTool::class, [])
            ->assertOk()
            ->assertSee('Galke')
            ->assertSee('DE-ÖKO-001');
    });

    it('persists a new supplier', function () {
        KisServer::tool(CreateSupplierTool::class, [
            'company' => 'Test Kräuter GmbH',
            'shortname' => 'Testkr',
        ])->assertOk();

        $this->assertDatabaseHas('suppliers', ['shortname' => 'Testkr']);
    });
});

describe('control bodies', function () {
    it('shows the code, company and country', function () {
        BioInspector::factory()->create([
            'label' => 'DE-ÖKO-021',
            'company' => 'Grünstempel Ökoprüfstelle e.V.',
            'country' => 'DE',
        ]);

        KisServer::tool(ListControlBodiesTool::class, [])
            ->assertOk()
            ->assertSee('DE-ÖKO-021')
            ->assertSee('Grünstempel Ökoprüfstelle e.V.')
            ->assertSee('Deutschland');
    });

    it('persists a new control body', function () {
        KisServer::tool(CreateControlBodyTool::class, [
            'oeko_code' => 'DE-ÖKO-013',
            'company' => 'QC&I GmbH',
            'country' => 'DE',
        ])->assertOk();

        $this->assertDatabaseHas('bio_inspectors', [
            'label' => 'DE-ÖKO-013',
            'company' => 'QC&I GmbH',
            'country' => 'DE',
        ]);
    });

    it('rejects a duplicate öko-code regardless of casing', function () {
        BioInspector::factory()->create(['label' => 'DE-ÖKO-013']);

        KisServer::tool(CreateControlBodyTool::class, [
            'oeko_code' => 'de-öko-013',
            'company' => 'QC&I GmbH',
            'country' => 'DE',
        ])->assertSee('already exists');

        expect(BioInspector::where('label', 'DE-ÖKO-013')->count())->toBe(1);
    });

    it('rejects an unknown country', function () {
        KisServer::tool(CreateControlBodyTool::class, [
            'oeko_code' => 'XX-ÖKO-999',
            'company' => 'Nowhere GmbH',
            'country' => 'ZZ',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('bio_inspectors', ['label' => 'XX-ÖKO-999']);
    });
});

describe('document uploads', function () {
    it('returns a signed url for a delivery invoice', function () {
        $delivery = Delivery::factory()->for(Supplier::factory())->create();

        KisServer::tool(RequestUploadUrlTool::class, [
            'target_type' => 'delivery',
            'target' => (string) $delivery->id,
            'collection' => 'invoice',
        ])
            ->assertOk()
            ->assertSee('/api/uploads/delivery/'.$delivery->id.'/invoice')
            ->assertSee('signature=');
    });

    it('resolves a certificate by its number', function () {
        $certificate = Certificate::factory()->for(Supplier::factory())->create([
            'certificate_number' => 'DE-ÖKO-001.276-0059778.2025.002',
        ]);

        KisServer::tool(RequestUploadUrlTool::class, [
            'target_type' => 'certificate',
            'target' => 'de-öko-001.276-0059778.2025.002',
            'collection' => 'document',
        ])
            ->assertOk()
            ->assertSee('/api/uploads/certificate/'.$certificate->id.'/document');
    });

    it('rejects a collection that does not exist on the target', function () {
        $certificate = Certificate::factory()->for(Supplier::factory())->create();

        KisServer::tool(RequestUploadUrlTool::class, [
            'target_type' => 'certificate',
            'target' => (string) $certificate->id,
            'collection' => 'invoice',
        ])->assertSee('not available');
    });

    it('errors on an unknown record', function () {
        KisServer::tool(RequestUploadUrlTool::class, [
            'target_type' => 'delivery',
            'target' => '999999',
            'collection' => 'invoice',
        ])->assertSee('No delivery matching');
    });
});

describe('certificates', function () {
    it('links the control body by öko-code', function () {
        BioInspector::factory()->create(['label' => 'DE-ÖKO-006', 'company' => 'ABCERT AG']);
        $supplier = Supplier::factory()->create(['shortname' => 'Edelkraut']);

        KisServer::tool(CreateCertificateTool::class, [
            'supplier' => 'Edelkraut',
            'oeko_code' => 'DE-ÖKO-006',
            'certificate_number' => 'ABC-123',
            'valid_from' => '2025-01-01',
            'valid_until' => '2026-01-01',
            'activities' => ['Aufbereitung', 'Einfuhr'],
            'product_categories' => ['a', 'd'],
        ])->assertOk()->assertSee('ABC-123');

        $this->assertDatabaseHas('certificates', [
            'supplier_id' => $supplier->id,
            'certificate_number' => 'ABC-123',
        ]);
    });

    it('errors on an unknown öko-code', function () {
        Supplier::factory()->create(['shortname' => 'Edelkraut']);

        KisServer::tool(CreateCertificateTool::class, [
            'supplier' => 'Edelkraut',
            'oeko_code' => 'XX-ÖKO-999',
            'certificate_number' => 'ABC-123',
            'valid_from' => '2025-01-01',
            'valid_until' => '2026-01-01',
        ])->assertHasErrors();
    });

    it('rejects an invalid activity', function () {
        BioInspector::factory()->create(['label' => 'DE-ÖKO-006']);
        Supplier::factory()->create(['shortname' => 'Edelkraut']);

        KisServer::tool(CreateCertificateTool::class, [
            'supplier' => 'Edelkraut',
            'oeko_code' => 'DE-ÖKO-006',
            'certificate_number' => 'ABC-123',
            'valid_from' => '2025-01-01',
            'valid_until' => '2026-01-01',
            'activities' => ['Frobnicating'],
        ])->assertHasErrors();
    });
});

describe('deliveries', function () {
    it('creates bags and freezes the covering certificate', function () {
        $inspector = BioInspector::factory()->create(['label' => 'DE-ÖKO-001', 'company' => 'Kiwa']);
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Certificate::factory()->for($supplier)->create([
            'bio_inspector_id' => $inspector->id,
            'certificate_number' => 'CERT-2025',
            'valid_from' => '2025-01-01',
            'valid_until' => '2027-01-01',
            'issued_at' => '2025-01-01',
        ]);
        Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);

        KisServer::tool(CreateDeliveryTool::class, [
            'supplier' => 'Galke',
            'delivered_date' => '2026-05-01',
            'bags' => [
                ['herb' => 'Kamille', 'charge' => 'CH-100', 'size_grams' => 5000, 'bio' => true],
            ],
        ])->assertOk()->assertSee('CERT-2025')->assertSee('CH-100');

        $delivery = Delivery::latest('id')->first();

        expect($delivery)->not->toBeNull()
            ->and($delivery->frozenOekoCode())->toBe('DE-ÖKO-001');

        $this->assertDatabaseHas('bags', ['delivery_id' => $delivery->id, 'charge' => 'CH-100']);
    });

    it('warns when no certificate covers the delivery date', function () {
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);

        KisServer::tool(CreateDeliveryTool::class, [
            'supplier' => 'Galke',
            'delivered_date' => '2026-05-01',
            'bags' => [
                ['herb' => 'Kamille', 'charge' => 'CH-200', 'size_grams' => 3000],
            ],
        ])->assertOk()->assertSee('Kein gültiges Zertifikat');

        $delivery = Delivery::latest('id')->first();

        expect($delivery->frozenOekoCode())->toBeNull();

        $this->assertDatabaseHas('bags', ['delivery_id' => $delivery->id, 'charge' => 'CH-200']);
    });

    it('fails before writing anything when a herb is unknown', function () {
        Supplier::factory()->create(['shortname' => 'Galke']);

        KisServer::tool(CreateDeliveryTool::class, [
            'supplier' => 'Galke',
            'delivered_date' => '2026-05-01',
            'bags' => [
                ['herb' => 'Unbekanntkraut', 'charge' => 'CH-300', 'size_grams' => 1000],
            ],
        ])->assertHasErrors();

        $this->assertDatabaseCount('deliveries', 0);
        $this->assertDatabaseMissing('bags', ['charge' => 'CH-300']);
    });

    it('shows the bags and the frozen certificate', function () {
        $inspector = BioInspector::factory()->create(['label' => 'DE-ÖKO-001']);
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Certificate::factory()->for($supplier)->create([
            'bio_inspector_id' => $inspector->id,
            'certificate_number' => 'CERT-XYZ',
            'valid_from' => '2025-01-01',
            'valid_until' => '2027-01-01',
            'issued_at' => '2025-01-01',
        ]);
        $herb = Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);
        $delivery = Delivery::create([
            'supplier_id' => $supplier->id,
            'user_id' => User::first()->id,
            'delivered_date' => '2026-05-01',
            'bio_inspection' => ['approved' => true],
        ]);
        $delivery->bags()->create([
            'herb_id' => $herb->id, 'charge' => 'CH-999', 'size' => 4000, 'bio' => true,
            'specification' => '', 'bestbefore' => now()->addYear(),
        ]);
        app(CertificateSnapshotter::class)->snapshotFromSupplier($delivery);

        KisServer::tool(GetDeliveryTool::class, ['delivery_id' => $delivery->id])
            ->assertOk()
            ->assertSee('CH-999')
            ->assertSee('CERT-XYZ');
    });
});

describe('products', function () {
    it('creates a product with a recipe', function () {
        $type = ProductType::create(['name' => 'Einzelkraut', 'compound' => false]);
        Herb::factory()->create(['name' => 'Kamille']);

        KisServer::tool(CreateProductTool::class, [
            'name' => 'Kamillentee',
            'type' => 'Einzelkraut',
            'recipe' => [
                ['herb' => 'Kamille', 'percentage' => 100],
            ],
        ])->assertOk()->assertSee('Kamillentee');

        $product = Product::where('name', 'Kamillentee')->first();

        expect($product)->not->toBeNull()
            ->and($product->product_type_id)->toBe($type->id)
            ->and($product->herbs()->count())->toBe(1);
    });

    it('warns when the recipe does not sum to 100', function () {
        ProductType::create(['name' => 'Mischung', 'compound' => true]);
        Herb::factory()->create(['name' => 'Kamille']);
        Herb::factory()->create(['name' => 'Minze']);

        KisServer::tool(CreateProductTool::class, [
            'name' => 'Halbe Mischung',
            'type' => 'Mischung',
            'recipe' => [
                ['herb' => 'Kamille', 'percentage' => 30],
                ['herb' => 'Minze', 'percentage' => 40],
            ],
        ])->assertOk()->assertSee('nicht 100');
    });
});

describe('bags', function () {
    it('lists active bags only by default', function () {
        $herb = Herb::factory()->create(['name' => 'Salbei']);
        activeBag($herb, ['charge' => 'AKTIV1']);
        activeBag($herb, ['charge' => 'WEG1'])->discard();

        KisServer::tool(FindBagsByHerbTool::class, ['herb' => 'Salbei'])
            ->assertOk()
            ->assertSee('AKTIV1')
            ->assertDontSee('WEG1');
    });

    it('includes discarded bags when asked', function () {
        $herb = Herb::factory()->create(['name' => 'Thymian']);
        activeBag($herb, ['charge' => 'AKTIV2']);
        activeBag($herb, ['charge' => 'WEG2'])->discard();

        KisServer::tool(FindBagsByHerbTool::class, [
            'herb' => 'Thymian',
            'include_discarded' => true,
        ])
            ->assertOk()
            ->assertSee('AKTIV2')
            ->assertSee('WEG2')
            ->assertSee('ENTSORGT');
    });

    it('errors when the herb is unknown', function () {
        KisServer::tool(FindBagsByHerbTool::class, ['herb' => 'Gibtsnicht'])
            ->assertHasErrors();
    });

    it('discards multiple bags by id', function () {
        $herb = Herb::factory()->create(['name' => 'Lavendel']);
        $a = activeBag($herb);
        $b = activeBag($herb);

        KisServer::tool(DiscardBagsTool::class, ['bag_ids' => [$a->id, $b->id]])
            ->assertOk()
            ->assertSee('Entsorgt (2)');

        expect($a->fresh()->trashed())->toBeTrue()
            ->and($b->fresh()->trashed())->toBeTrue();
    });

    it('skips bags that are unknown or already discarded', function () {
        $herb = Herb::factory()->create(['name' => 'Ringelblume']);
        $active = activeBag($herb);
        $already = activeBag($herb);
        $already->discard();

        KisServer::tool(DiscardBagsTool::class, [
            'bag_ids' => [$active->id, $already->id, 999999],
        ])
            ->assertOk()
            ->assertSee('Entsorgt (1)')
            ->assertSee('Übersprungen (2)')
            ->assertSee('bereits entsorgt')
            ->assertSee('nicht gefunden');

        expect($active->fresh()->trashed())->toBeTrue();
    });

    it('errors when nothing could be discarded', function () {
        KisServer::tool(DiscardBagsTool::class, ['bag_ids' => [999999]])
            ->assertHasErrors();
    });
});
