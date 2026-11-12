<?php

use App\Filament\Widgets\Reorder;
use App\Mcp\Servers\KisServer;
use App\Mcp\Tools\HerbsToReorderTool;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Models\Variant;
use App\Services\Stats\OpenOrderReservations;
use App\Support\Stats\HerbStats;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'Tester',
        'email' => 'tester@example.test',
        'password' => bcrypt('secret'),
    ]));

    CarbonImmutable::setTestNow('2026-11-12 10:00:00');
});

/**
 * A herb with stored stats: the given stock, used up at the given rate per day.
 */
function herbWithStock(string $name, float $stock, float $dailyUsage = 10): Herb
{
    $herb = Herb::factory()->create(['name' => $name]);
    $herb->stats = new HerbStats(
        generatedAt: CarbonImmutable::now(),
        currentStock: $stock,
        averageDailyUsage: $dailyUsage,
        depletionDate: CarbonImmutable::now()->addDays($stock / $dailyUsage),
        forecastDailyUsage: $dailyUsage,
    );
    $herb->saveQuietly();

    return $herb;
}

/**
 * A variant of a product made from the given herbs (herb id => recipe %),
 * at the given Billbee stock. Negative stock = sold but not yet bottled.
 *
 * @param  array<int, float>  $recipe
 */
function variantOf(string $name, array $recipe, int $size, int $stock): Variant
{
    $type = ProductType::firstOrCreate(['name' => 'Tee'], ['compound' => true]);
    $product = Product::create(['name' => $name, 'product_type_id' => $type->id]);

    foreach ($recipe as $herbId => $percentage) {
        $product->herbs()->attach($herbId, ['percentage' => $percentage]);
    }

    $variant = Variant::create(['product_id' => $product->id, 'sku' => 'SKU-'.$product->id, 'size' => $size]);
    $variant->updateQuietly(['stock' => $stock]);

    return $variant;
}

describe('open order reservations', function () {
    it('reserves the herbs of sold but not yet bottled units', function () {
        $kamille = herbWithStock('Kamille', 5000);
        $minze = herbWithStock('Minze', 5000);
        variantOf('Abendtee', [$kamille->id => 60, $minze->id => 40], size: 100, stock: -10);

        $reservations = app(OpenOrderReservations::class)->perHerb();

        expect($reservations->get($kamille->id)->grams)->toBe(600.0)
            ->and($reservations->get($minze->id)->grams)->toBe(400.0)
            ->and($reservations->get($kamille->id)->lines)->toBe([
                ['variant' => 'Abendtee 100g', 'missing' => 10, 'grams' => 600.0],
            ]);
    });

    it('adds up reservations across products', function () {
        $kamille = herbWithStock('Kamille', 5000);
        variantOf('Kamillentee', [$kamille->id => 100], size: 50, stock: -4);
        variantOf('Abendtee', [$kamille->id => 50], size: 100, stock: -2);

        expect(app(OpenOrderReservations::class)->perHerb()->get($kamille->id)->grams)->toBe(300.0);
    });

    it('ignores products that are still in stock', function () {
        $kamille = herbWithStock('Kamille', 5000);
        variantOf('Kamillentee', [$kamille->id => 100], size: 50, stock: 0);
        variantOf('Abendtee', [$kamille->id => 100], size: 50, stock: 12);

        expect(app(OpenOrderReservations::class)->perHerb())->toBeEmpty();
    });
});

describe('depletion with reservations', function () {
    it('keeps the stored date when nothing is reserved', function () {
        $stats = HerbStats::for(herbWithStock('Kamille', 1000));

        expect($stats->depletionDateWith(0))->toEqual($stats->estimatedDepletionDate());
    });

    it('pulls the date forward by the reserved grams', function () {
        $stats = HerbStats::for(herbWithStock('Kamille', 1000, dailyUsage: 10));

        expect($stats->depletionDateWith(400)->toDateString())->toBe('2027-01-11');
    });

    it('is today when the reservation exceeds the stock', function () {
        $stats = HerbStats::for(herbWithStock('Kamille', 1000));

        expect($stats->depletionDateWith(1200)->isToday())->toBeTrue()
            ->and($stats->availableStock(1200))->toBe(-200.0);
    });

    it('falls back to the all-time average for stats without a forecast rate', function () {
        $stats = new HerbStats(currentStock: 1000, averageDailyUsage: 20);

        expect($stats->forecastDailyUsage())->toBe(20.0);
    });
});

describe('reorder table', function () {
    it('shows the reservation and flags herbs that cannot cover their orders', function () {
        $kamille = herbWithStock('Kamille', 1000);
        variantOf('Kamillentee', [$kamille->id => 100], size: 100, stock: -12);

        Livewire::test(Reorder::class)
            ->assertSee('1,200.0 g')
            ->assertSee('200.0 g fehlen')
            ->assertSee('Reicht nicht');
    });

    it('sorts herbs short for orders to the top', function () {
        herbWithStock('Ruhig', 1000, dailyUsage: 1);
        $knapp = herbWithStock('Knapp', 5000, dailyUsage: 1);
        variantOf('Großbestellung', [$knapp->id => 100], size: 1000, stock: -6);

        Livewire::test(Reorder::class)
            ->assertSeeInOrder(['Knapp', 'Ruhig']);
    });

    it('ranks the bigger shortfall first among herbs that cannot cover their orders', function () {
        $wenig = herbWithStock('Wenig', 1000);
        $viel = herbWithStock('Viel', 1000);
        variantOf('Kleine Bestellung', [$wenig->id => 100], size: 100, stock: -11);
        variantOf('Große Bestellung', [$viel->id => 100], size: 100, stock: -50);

        Livewire::test(Reorder::class)
            ->assertSeeInOrder(['Viel', 'Wenig']);
    });

    it('leaves herbs without open orders unchanged', function () {
        herbWithStock('Kamille', 1000);

        Livewire::test(Reorder::class)
            ->assertDontSee('Reicht nicht')
            ->assertDontSee('fehlen');
    });
});

describe('herbs-to-reorder tool', function () {
    it('includes herbs that only run out because of open orders', function () {
        $kamille = herbWithStock('Kamille', 5000, dailyUsage: 1);
        variantOf('Kamillentee', [$kamille->id => 100], size: 1000, stock: -6);

        KisServer::tool(HerbsToReorderTool::class, ['within_days' => 30])
            ->assertOk()
            ->assertSee('Kamille')
            ->assertSee('für offene, noch nicht abgefüllte Bestellungen reserviert');
    });
});
