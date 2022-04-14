<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Http\Traits\ReadsCSVData;

use App\Models\{Product, ProductType, Variant};

class DrahtSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typeDraht = ProductType::find(1);

        // Creating all Draht Mischungen
        $draht = self::getCSV('mischungen.csv', ';');
        foreach ($draht as $drahtmix) {
            $prod = Product::create([
                'name' => $drahtmix['name'],
                'mainnumber' => $drahtmix['ordernumber'],
                'product_type_id' => $typeDraht->id
            ]);

            Variant::create([
                'size' => 100,
                'product_id' => $prod->id
            ]);
        }
    }
}
