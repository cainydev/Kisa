<?php

use App\Models\Bottle;
use App\Models\BottlePosition;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Support\Carbon;

/**
 * Build a variant whose product is blended from the given number of herbs.
 * Multi-ingredient variants are the ones that get a generated charge.
 */
function makeVariant(int $herbCount, int $size = 100): Variant
{
    $type = ProductType::create(['name' => 'Tee', 'compound' => false]);
    $product = Product::create(['name' => 'Testtee', 'product_type_id' => $type->id]);

    $herbs = Herb::factory()->count(max($herbCount, 1))->create();
    foreach ($herbs->take($herbCount) as $herb) {
        $product->herbs()->attach($herb->id, ['percentage' => 100 / $herbCount]);
    }

    return Variant::create([
        'product_id' => $product->id,
        'sku' => 'TEST-'.$product->id,
        'size' => $size,
    ]);
}

function makeBottle(Carbon $date): Bottle
{
    $user = User::create([
        'name' => 'Tester',
        'email' => 'tester'.uniqid().'@example.test',
        'password' => bcrypt('secret'),
    ]);

    return Bottle::factory()->create([
        'user_id' => $user->id,
        'date' => $date,
    ]);
}

/**
 * Bottle the variant under test and return the position's generated charge.
 */
function bottleCharge(int $count = 5): string
{
    return BottlePosition::create([
        'bottle_id' => test()->bottle->id,
        'variant_id' => test()->variant->id,
        'count' => $count,
    ])->fresh()->charge;
}

beforeEach(function () {
    $this->bottle = makeBottle(Carbon::parse('2026-07-24'));
    $this->variant = makeVariant(herbCount: 2);
});

describe('multi-ingredient charge generation', function () {
    it('builds the charge from the date prefix plus a sequence', function () {
        // Date "ymd" (260724) + the 1-based sequence for the first
        // multi-ingredient position bottled that day.
        expect(bottleCharge())->toBe('2607241');
    });

    it('increments the sequence so charges do not collide within a day', function () {
        $charges = [bottleCharge(5), bottleCharge(3), bottleCharge(1)];

        expect($charges)
            ->toBe(['2607241', '2607242', '2607243'])
            ->and(array_unique($charges))->toHaveCount(3);
    });

    it('keeps the charge a string rather than a coerced integer', function () {
        // The date prefix must survive verbatim; the old "concat then + 1" bug
        // coerced the whole thing to an int and dropped the format.
        expect(bottleCharge())
            ->toBeString()
            ->toStartWith('260724');
    });
});
