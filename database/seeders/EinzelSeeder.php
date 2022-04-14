<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ReadsCSVData;

use App\Models\{ProductType, Product, Variant, Herb};

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
                Variant::create([
                    'size' => intval($variant['weight']),
                    'ordernumber' => Str::after($order, $main),
                    'product_id' => $prod->id
                ]);
            } else { // Main Article
                $prod = Product::create([
                    'name' => $variant['name'],
                    'mainnumber' => $order,
                    'product_type_id' => $typeEinzel->id
                ]);

                Variant::create([
                    'size' => intval($variant['weight']),
                    'product_id' => $prod->id
                ]);

                foreach (Herb::all() as $herb) {
                    if (Str::of($prod->name)->contains($herb->name)) {
                        DB::table('herb_product')->insert([
                            'herb_id' => $herb->id,
                            'product_id' => $prod->id,
                            'percentage' => 100
                        ]);
                        break;
                    }
                }
            }
        }
    }
}
