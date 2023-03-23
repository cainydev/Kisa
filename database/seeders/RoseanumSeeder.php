<?php

namespace Database\Seeders;

use App\Http\Traits\ReadsCSVData;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Variant;
use Illuminate\Database\Seeder;

class RoseanumSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typeRoseanum = ProductType::find(4);
        $prod = Product::create([
            'name' => 'Frauenstarktee',
            'mainnumber' => 'tm1201',
            'product_type_id' => $typeRoseanum->id,
        ]);

        Variant::create([
            'size' => 50,
            'ordernumber' => '',
            'product_id' => $prod->id,
        ]);
    }
}
