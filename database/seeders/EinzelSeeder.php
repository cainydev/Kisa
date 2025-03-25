<?php

namespace Database\Seeders;

use App\Http\Traits\ReadsCSVData;
use App\Models\Herb;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use function trim;

class EinzelSeeder extends Seeder
{
    use ReadsCSVData;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $typeEinzel = ProductType::find(2);

        // Adding the Einzelkräuter
        $einzel = collect(self::getCSV('einzel.csv', ';'))->groupBy('mainnumber');
        foreach ($einzel as $mainnumber => $variants) {
            $product = Product::create([
                'name' => $variants[0]['name'],
                'product_type_id' => $typeEinzel->id,
            ]);

            foreach ($variants as $variant) {
                $ordernumber = trim($variant['ordernumber']);

                $product->variants()->create([
                    'size' => intval($variant['weight']),
                    'sku' => $ordernumber
                ]);
            }

            foreach (Herb::all() as $herb) {
                if (Str::of($product->name)->contains($herb->name)) {
                    DB::table('herb_product')->insert([
                        'herb_id' => $herb->id,
                        'product_id' => $product->id,
                        'percentage' => 100,
                    ]);
                    break;
                }
            }
        }
    }
}
