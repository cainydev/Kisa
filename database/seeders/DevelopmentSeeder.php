<?php

namespace Database\Seeders;

use App\Models\Bottle;
use App\Models\Product;
use Illuminate\Database\Seeder;
use function now;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Example Product
        $product = Product::create([
            'name' => 'Testprodukt',
            'mainnumber' => 'test1000',
            'product_type_id' => 1,
        ]);

        $product->recipeIngredients()->createMany([
            ['herb_id' => 1, 'percentage' => 58],
            ['herb_id' => 2, 'percentage' => 28],
            ['herb_id' => 3, 'percentage' => 14],
        ]);

        $variant = $product->variants()->create([
            'size' => 100,
            'ordernumber' => 'test1000',
            'stock' => 0,
        ]);

        // Example Bottle
        $bottle = Bottle::create([
            'user_id' => 1,
            'note' => '',
            'date' => now()->format('Y-m-d'),
        ]);

        $bottle->positions()->createMany([
            ['variant_id' => 2, 'count' => 15],
            ['variant_id' => 3, 'count' => 10],
            ['variant_id' => 32, 'count' => 8],
            ['variant_id' => 109, 'count' => 12],
            ['variant_id' => $variant->id, 'count' => 50]
        ]);
    }
}
