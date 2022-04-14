<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Traits\ReadsCSVData;
use App\Models\{ProductType, Variant, Product};

class RuthSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typeRuth = ProductType::find(3);

        // Creating all Ruth Mischungen
        $row = self::getCSV('ruthsmischungen.csv', ';');
        foreach ($row as $ruthmix) {
            $prod = Product::create([
                'name' => $ruthmix['name'],
                'mainnumber' => $ruthmix['ordernumber'],
                'product_type_id' => $typeRuth->id
            ]);

            Variant::create([
                'size' => 50,
                'ordernumber' => '',
                'product_id' => $prod->id
            ]);

            Variant::create([
                'size' => 100,
                'ordernumber' => '.1',
                'product_id' => $prod->id
            ]);
        }
    }
}
