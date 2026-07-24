<?php

namespace Tests\Feature;

use App\Models\Bottle;
use App\Models\BottlePosition;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BottlePositionChargeTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(int $herbCount, int $size = 100): Variant
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

    private function makeBottle(Carbon $date): Bottle
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

    public function test_multi_ingredient_charge_is_date_prefix_plus_sequence(): void
    {
        $date = Carbon::parse('2026-07-24');
        $bottle = $this->makeBottle($date);
        $variant = $this->makeVariant(herbCount: 2);

        $position = BottlePosition::create([
            'bottle_id' => $bottle->id,
            'variant_id' => $variant->id,
            'count' => 5,
        ]);

        // Expected: date "ymd" (260724) + 1-based sequence for the first multi-ingredient position that day.
        $this->assertSame('2607241', $position->fresh()->charge);
    }

    public function test_multi_ingredient_charges_increment_and_do_not_collide(): void
    {
        $date = Carbon::parse('2026-07-24');
        $bottle = $this->makeBottle($date);
        $variant = $this->makeVariant(herbCount: 2);

        $first = BottlePosition::create(['bottle_id' => $bottle->id, 'variant_id' => $variant->id, 'count' => 5]);
        $second = BottlePosition::create(['bottle_id' => $bottle->id, 'variant_id' => $variant->id, 'count' => 3]);
        $third = BottlePosition::create(['bottle_id' => $bottle->id, 'variant_id' => $variant->id, 'count' => 1]);

        $charges = [$first->fresh()->charge, $second->fresh()->charge, $third->fresh()->charge];

        $this->assertSame(['2607241', '2607242', '2607243'], $charges);
        $this->assertCount(3, array_unique($charges), 'Charges must be unique within a day');
    }

    public function test_charge_is_a_string_not_a_coerced_integer(): void
    {
        $date = Carbon::parse('2026-07-24');
        $bottle = $this->makeBottle($date);
        $variant = $this->makeVariant(herbCount: 2);

        $charge = BottlePosition::create([
            'bottle_id' => $bottle->id,
            'variant_id' => $variant->id,
            'count' => 5,
        ])->fresh()->charge;

        // The date prefix must survive verbatim; the old "concat then + 1" bug
        // coerced the whole thing to an int and dropped the format.
        $this->assertIsString($charge);
        $this->assertStringStartsWith('260724', $charge);
    }
}
