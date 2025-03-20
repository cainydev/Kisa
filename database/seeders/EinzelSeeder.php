<?php

namespace Database\Seeders;

use App\Http\Traits\ReadsCSVData;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Variant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EinzelSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $typeEinzel = ProductType::find(2);

        // Adding the Einzelkräuter
        $einzel = self::getCSV('einzel.csv', ';');
        foreach ($einzel as $variant) {
            $order = trim($variant['ordernumber']);
            $main = trim($variant['mainnumber']);

            if (Str::contains($order, '.')) { // Variant
                $prod = Product::where('mainnumber', $main)->first();
                $prod->variants()->create([
                    'size' => intval($variant['weight']),
                    'ordernumber' => $order
                ]);
            } else { // Main Article
                $prod = Product::create([
                    'name' => $variant['name'],
                    'mainnumber' => $order,
                    'product_type_id' => $typeEinzel->id,
                ]);

                Variant::create([
                    'size' => intval($variant['weight']),
                    'product_id' => $prod->id,
                    'ordernumber' => $order,
                ]);

                foreach (Herb::all() as $herb) {
                    if (Str::of($prod->name)->contains($herb->name)) {
                        DB::table('herb_product')->insert([
                            'herb_id' => $herb->id,
                            'product_id' => $prod->id,
                            'percentage' => 100,
                        ]);
                        break;
                    }
                }
            }
        }
    }
}
