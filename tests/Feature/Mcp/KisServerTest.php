<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\KisServer;
use App\Mcp\Tools\CreateCertificateTool;
use App\Mcp\Tools\CreateDeliveryTool;
use App\Mcp\Tools\CreateHerbTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreateSupplierTool;
use App\Mcp\Tools\GetDeliveryTool;
use App\Mcp\Tools\GetHerbTool;
use App\Mcp\Tools\ListHerbsTool;
use App\Mcp\Tools\ListSuppliersTool;
use App\Models\BioInspector;
use App\Models\Certificate;
use App\Models\Delivery;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Traceability\CertificateSnapshotter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::create([
            'name' => 'Marcus Wagner',
            'email' => 'marcus@example.test',
            'password' => bcrypt('secret'),
        ]);
    }

    // ---- Herbs -------------------------------------------------------------

    public function test_list_herbs_returns_herbs_with_stock(): void
    {
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);

        KisServer::tool(ListHerbsTool::class, [])
            ->assertOk()
            ->assertSee('Kamille')
            ->assertSee('Galke');
    }

    public function test_get_herb_resolves_by_name(): void
    {
        Herb::factory()->create(['name' => 'Pfefferminze', 'fullname' => 'Pfefferminzblätter']);

        KisServer::tool(GetHerbTool::class, ['herb' => 'Pfefferminze'])
            ->assertOk()
            ->assertSee('Pfefferminzblätter');
    }

    public function test_get_herb_errors_clearly_when_not_found(): void
    {
        KisServer::tool(GetHerbTool::class, ['herb' => 'Nichtvorhanden'])
            ->assertHasErrors();
    }

    public function test_create_herb_creates_and_links_supplier(): void
    {
        $supplier = Supplier::factory()->create(['shortname' => 'Dragonspice']);

        KisServer::tool(CreateHerbTool::class, [
            'name' => 'Salbei',
            'fullname' => 'Salbeiblätter',
            'supplier' => 'Dragonspice',
        ])->assertOk()->assertSee('Salbei');

        $this->assertDatabaseHas('herbs', ['name' => 'Salbei', 'supplier_id' => $supplier->id]);
    }

    public function test_create_herb_rejects_duplicates(): void
    {
        Herb::factory()->create(['name' => 'Thymian']);

        KisServer::tool(CreateHerbTool::class, ['name' => 'Thymian'])
            ->assertHasErrors();
    }

    public function test_create_herb_errors_on_unknown_supplier(): void
    {
        KisServer::tool(CreateHerbTool::class, ['name' => 'Rosmarin', 'supplier' => 'DoesNotExist'])
            ->assertHasErrors();

        $this->assertDatabaseMissing('herbs', ['name' => 'Rosmarin']);
    }

    // ---- Suppliers ---------------------------------------------------------

    public function test_list_suppliers_shows_current_control_body(): void
    {
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
    }

    public function test_create_supplier_persists(): void
    {
        KisServer::tool(CreateSupplierTool::class, [
            'company' => 'Test Kräuter GmbH',
            'shortname' => 'Testkr',
        ])->assertOk();

        $this->assertDatabaseHas('suppliers', ['shortname' => 'Testkr']);
    }

    // ---- Certificates ------------------------------------------------------

    public function test_create_certificate_links_control_body_by_oeko_code(): void
    {
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
    }

    public function test_create_certificate_errors_on_unknown_oeko_code(): void
    {
        $supplier = Supplier::factory()->create(['shortname' => 'Edelkraut']);

        KisServer::tool(CreateCertificateTool::class, [
            'supplier' => 'Edelkraut',
            'oeko_code' => 'XX-ÖKO-999',
            'certificate_number' => 'ABC-123',
            'valid_from' => '2025-01-01',
            'valid_until' => '2026-01-01',
        ])->assertHasErrors();
    }

    public function test_create_certificate_rejects_invalid_activity(): void
    {
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
    }

    // ---- Deliveries (the compound write) -----------------------------------

    public function test_create_delivery_creates_bags_and_freezes_certificate(): void
    {
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

        $response = KisServer::tool(CreateDeliveryTool::class, [
            'supplier' => 'Galke',
            'delivered_date' => '2026-05-01',
            'bags' => [
                ['herb' => 'Kamille', 'charge' => 'CH-100', 'size_grams' => 5000, 'bio' => true],
            ],
        ]);

        $response->assertOk()->assertSee('CERT-2025')->assertSee('CH-100');

        $delivery = Delivery::latest('id')->first();
        $this->assertNotNull($delivery);
        $this->assertSame('DE-ÖKO-001', $delivery->frozenOekoCode());
        $this->assertDatabaseHas('bags', ['delivery_id' => $delivery->id, 'charge' => 'CH-100']);
    }

    public function test_create_delivery_warns_when_no_certificate_covers_the_date(): void
    {
        $supplier = Supplier::factory()->create(['shortname' => 'Galke']);
        Herb::factory()->create(['name' => 'Kamille', 'supplier_id' => $supplier->id]);

        $response = KisServer::tool(CreateDeliveryTool::class, [
            'supplier' => 'Galke',
            'delivered_date' => '2026-05-01',
            'bags' => [
                ['herb' => 'Kamille', 'charge' => 'CH-200', 'size_grams' => 3000],
            ],
        ]);

        $response->assertOk()->assertSee('Kein gültiges Zertifikat');

        $delivery = Delivery::latest('id')->first();
        $this->assertNull($delivery->frozenOekoCode());
        $this->assertDatabaseHas('bags', ['delivery_id' => $delivery->id, 'charge' => 'CH-200']);
    }

    public function test_create_delivery_fails_before_writing_when_a_herb_is_unknown(): void
    {
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
    }

    public function test_get_delivery_shows_bags_and_certificate(): void
    {
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
    }

    // ---- Products ----------------------------------------------------------

    public function test_create_product_with_recipe(): void
    {
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
        $this->assertNotNull($product);
        $this->assertSame($type->id, $product->product_type_id);
        $this->assertEquals(1, $product->herbs()->count());
    }

    public function test_create_product_warns_when_recipe_does_not_sum_to_100(): void
    {
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
    }
}
