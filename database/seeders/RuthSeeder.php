<?php

namespace Database\Seeders;

use App\Http\Traits\ReadsCSVData;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class RuthSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $typeRuth = ProductType::find(3);

        // Creating all Ruth Mischungen
        $row = self::getCSV('ruthsmischungen.csv', ';');
        foreach ($row as $ruthmix) {
            $prod = Product::create([
                'name' => $ruthmix['name'],
                'product_type_id' => $typeRuth->id,
            ]);

            Variant::create([
                'size' => 50,
                'sku' => $ruthmix['ordernumber'],
                'product_id' => $prod->id,
            ]);

            Variant::create([
                'size' => 100,
                'sku' => $ruthmix['ordernumber'] . '.1',
                'product_id' => $prod->id,
            ]);
        }
    }
}
